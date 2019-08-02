# Lighthouse Model Migrator

This is an experimental proof of concept to try to automagically generate GraphQL types & queries for 
[Lighthouse PHP][lighthouse-php] from Laravel Eloquent models.

## Installation

To install this package, you'll have to update the `composer.json` to pull from the github repository.

``` json
// composer.json

"require": {
	"danielwilhelmsen/lighthouse-model-migrator": "dev-master"
},
"repositories": [
    {
        "type": "vcs",
    	"url": "https://github.com/dpwilhelmsen/lighthouse-model-migrator"
	}
]
```

## Usage

This package will attempt to generate graphql types and queries from existing Eloquent models. It will append the
results to `schema.graphql` file created by [Lighthouse PHP][lighthouse-php]

To run, use the following artisan command.

```bash
$ php artisan lighthouse:migrate
```

## How it works

This package uses the models namespaces defined in `config/lighthouse.php`. It finds all Eloquent models, figures out
the table names and derives the attributes from the table schema. Then it analyzes the php code and looks for methods
that implement Laravel relationship methods. Then, it generates basic types and queries for the models.

## Known issues

This package is experimental and hasn't been thoroughly tested. There are likely many use cases where this will fail.

* This relies on a database connection. Not all column types are supported or properly mapped to graphQL types
* Doesn't currently support polymorphic relations
* Traits & inheritance has not been tested and likely won't work

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.


## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.


## Credits

- [Daniel Wilhelmsen][link-author]
- [All Contributors][link-contributors]

## License

license. Please see the [license file](license.md) for more information.

[link-author]: https://github.com/dpwilhelmsen
[link-contributors]: ../../contributors
[lighthouse-php]: https://github.com/nuwave/lighthouse
