<?php

namespace DanielWilhelmsen\LighthouseModelMigrator;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeVisitorAbstract;

class RelationshipFinder extends NodeVisitorAbstract
{
    public $relations = [];

    /**
     * @param Node $node
     * @return integer|Node|Node[]|void|null
     */
    public function leaveNode(Node $node) {
        $nodeFinder = new NodeFinder;
        if ($node instanceof Node\Stmt\ClassMethod) {
            $class = $nodeFinder->findFirst($node->stmts, function (Node $node) {
                return $node instanceof Node\Expr\MethodCall
                    && in_array($node->name->toString(), [
                        'belongsTo',
                        'hasOne',
                        'hasMany',
                        'belongsToMany',
                    ]);
            });
            if ($class) {
                try {
                    $model = ($class->args[0]->value instanceof Node\Expr\ClassConstFetch)
                        ? $class->args[0]->value->class->toCodeString() : $class->args[0]->value->value;
                    $this->relations[$node->name->toString()] = [
                        'type'  => $class->name->toString(),
                        'model' => $model,
                    ];
                } catch (\Exception $e) {
                    dump($class->args[0]);
                    dd($e);
                }
            }
        }
    }
}
