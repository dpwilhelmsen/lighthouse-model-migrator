<?php

namespace DanielWilhelmsen\LighthouseModelMigrator\Console;

use DanielWilhelmsen\LighthouseModelMigrator\RelationshipFinder;
use Doctrine\DBAL\DBALException;
use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class LighthouseMigrateModel extends Command
{
    const INT = 'Int';
    const ID = 'ID';
    const FLOAT = 'Float';
    const STRING = 'String';
    const BOOLEAN = 'Boolean';
    const DATE = 'Date';
    const DATETIME = 'DateTime';

    private $typeMap = [
        'id'       => self::ID,
        'integer'  => self::INT,
        'string'   => self::STRING,
        'boolean'  => self::BOOLEAN,
        'datetime' => self::DATETIME,
        'date'     => self::DATE,
        'text'     => self::STRING,
        'smallint' => self::INT,
        'bigint'   => self::INT,
        'time'     => self::DATETIME,
        'json'     => self::STRING,
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lighthouse:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate models to GraphQL types';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $namespaces = config('lighthouse.namespaces.models');
        $classesWithErrors = [];
        $types = '';
        $queries = '';
        $path = base_path('graphql/schema.graphql');
        $schema = file_get_contents($path);

        foreach ($namespaces as $namespace) {
            $this->comment("Finding models in $namespace namespace");
            $classes = ClassFinder::getClassesInNamespace($namespace);
            $classes = array_filter($classes, function ($class) {
                $model = new \ReflectionClass($class);
                return $model->isSubclassOf(Model::class);
            });
            $numberOfModels = count($classes);
            $this->comment("Found $numberOfModels models. Generating graphql");
            $bar = $this->output->createProgressBar($numberOfModels);
            foreach ($classes as $class) {
                $model = new \ReflectionClass($class);
                $table = $this->getTableName($model, $class);

                $columnsMap = $this->getColumnDefinitions($table, $classesWithErrors);
                $output = $this->generateTypeForClass($class, $columnsMap, $model);
                $types .= $output;

                $queries .= $this->generateQueries($class);
                $bar->advance();
            }
        }
        $this->line("\n-------------------------------------");
        $this->info('Types created.');
        if (count($classesWithErrors) > 0) {
            $this->error('There were errors importing the fields for the following tables: ' .
                implode(', ', array_keys($classesWithErrors)));
        }

        $re = '/type Query \{([^}]+)}/s';
        $replaced = preg_replace($re, 'type Query { ${1}' . $queries . ' }', $schema);
        $replaced .= $types;
        file_put_contents(base_path('graphql/schema.graphql'), $replaced);
        $this->info('GraphQL created!');
    }

    /**
     * @param string $fullClassName
     * @return string
     */
    protected function generateQueries(string $fullClassName) : string
    {
        $modelName = class_basename($fullClassName);
        $querySingle = Str::snake($modelName);
        $queryAll = Str::plural($querySingle);

        $typeTemplate = str_replace(
            ['{{modelName}}', '{{queryAll}}', '{{querySingle}}'],
            [$modelName, $queryAll, $querySingle],
            $this->getStub('Query')
        );

        return $typeTemplate;
    }

    /**
     * @param string $type
     * @return false|string
     */
    protected function getStub($type)
    {
        return file_get_contents(__DIR__ . "/stubs/$type.stub");
    }

    /**
     * @param \ReflectionClass $model
     * @param string           $class
     * @return string
     */
    private function getTableName(\ReflectionClass $model, string $class) : string
    {
        $defaultProperties = $model->getDefaultProperties();
        if (isset($defaultProperties['table'])) {
            $table = $defaultProperties['table'];
        } else {
            $table = Str::snake(Str::plural(class_basename($class)));
        }
        return $table;
    }

    /**
     * @param string $table
     * @param array  $classesWithErrors
     * @return array
     */
    private function getColumnDefinitions(string $table, array &$classesWithErrors) : array
    {
        $columns = Schema::getColumnListing($table);
        $columnsMap = [];
        foreach ($columns as $columnName) {
            try {
                $type = ($columnName === 'id') ? 'id' : Schema::getColumnType($table, $columnName);
                $required = Schema::getConnection()->getDoctrineColumn($table, $columnName)->getNotNull();
                $columnsMap[$columnName] = ['type' => $type, 'required' => $required];
            } catch (DBALException $e) {
                $classesWithErrors[$table] = true;
            }
        }
        return $columnsMap;
    }

    /**
     * @param string           $class
     * @param array            $columnDefinitions
     * @param \ReflectionClass $reflectionClass
     * @return string
     */
    private function generateTypeForClass(string $class, array $columnDefinitions, \ReflectionClass $reflectionClass) : string
    {
        $fields = $this->generateFields($columnDefinitions);
        $relations = $this->generateRelations($reflectionClass);

        $typeTemplate = str_replace(
            ['{{modelName}}', '{{fields}}', '{{relations}}'],
            [class_basename($class), $fields, $relations],
            $this->getStub('Type')
        );

        return $typeTemplate;
    }

    /**
     * @param array $columnDefinitions
     * @return string
     */
    private function generateFields(array $columnDefinitions) : string
    {
        $fields = '';
        foreach ($columnDefinitions as $field => $fieldData) {
            $type = isset($this->typeMap[$fieldData['type']]) ? $this->typeMap[$fieldData['type']] : 'Unknown';
            $required = $fieldData['required'] ? '!' : '';
            $fieldTemplate = str_replace(
                ['{{fieldName}}', '{{fieldType}}', '{{required}}'],
                [$field, $type, $required],
                $this->getStub('Field')
            );
            $fields .= $fieldTemplate;
            //file_put_contents(app_path("/{$name}.php"), $modelTemplate);
        }
        return $fields;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @return string
     */
    private function generateRelations(\ReflectionClass $reflectionClass) : ?string
    {
        $relations = '';
        $file = $reflectionClass->getFileName();
        $code = file_get_contents($file);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $traverser = new NodeTraverser;
        $visitor = new RelationshipFinder;
        $traverser->addVisitor($visitor);
        try {
            $ast = $parser->parse($code);

            $stmts = $traverser->traverse($ast);
        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()}\n";
            return null;
        }

        foreach ($visitor->relations as $methodName => $relationData) {
            $fieldName = Str::snake($methodName);
            if ($relationData['type'] === 'belongsTo') {
                $relationsTemplate = str_replace(
                    ['{{fieldName}}', '{{typeName}}'],
                    [$fieldName, class_basename($relationData['model'])],
                    $this->getStub('BelongsTo')
                );
            } else {
                $relationsTemplate = str_replace(
                    ['{{fieldName}}', '{{typeName}}', '{{relationType}}'],
                    [$fieldName, class_basename($relationData['model']), $relationData['type']],
                    $this->getStub('Many')
                );
            }

            $relations .= $relationsTemplate;
        }

        return $relations;
    }
}
