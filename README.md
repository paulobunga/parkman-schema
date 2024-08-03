# Parkman Schema: Prisma to Laravel Migration Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/paulobunga/parkman-schema.svg?style=flat-square)](https://packagist.org/packages/paulobunga/parkman-schema)
[![Total Downloads](https://img.shields.io/packagist/dt/paulobunga/parkman-schema.svg?style=flat-square)](https://packagist.org/packages/paulobunga/parkman-schema)
![GitHub Actions](https://github.com/paulobunga/parkman-schema/actions/workflows/main.yml/badge.svg)

Parkman Schema is a powerful Laravel package that generates Laravel migrations and models from Prisma schema files. It simplifies the process of transitioning from Prisma to Laravel by automatically creating the necessary database structure and Eloquent models.

## Installation

You can install the package via composer:

```bash
composer require paulobunga/parkman-schema
```

## Usage

### Generate Migrations

To generate Laravel migrations from your Prisma schema:

```php
use Paulobunga\ParkmanSchema\Parkman;

$parkman = new Parkman();
$parkman->setSchema(file_get_contents('path/to/your/schema.prisma'));
$parkman->generateMigrations();
```

This will create migration files in your Laravel project's `database/migrations` directory.

### Generate Models

To generate Eloquent models from your Prisma schema:

```php
use Paulobunga\ParkmanSchema\Parkman;

$parkman = new Parkman();
$parkman->setSchema(file_get_contents('path/to/your/schema.prisma'));
$parkman->generateModels();
```

This will create model files in your Laravel project's `app/Models` directory.

### Using Artisan Commands

The package also provides Artisan commands for easy use:

```bash
# Generate migrations
php artisan parkman:migrations path/to/your/schema.prisma

# Generate models
php artisan parkman:models path/to/your/schema.prisma
```

### Customizing Stubs

You can publish the stub files to customize the generated migrations and models:

```bash
php artisan vendor:publish --provider="Paulobunga\ParkmanSchema\ParkmanSchemaServiceProvider" --tag="stubs"
```

This will copy the stub files to `resources/stubs/vendor/parkman-schema` in your Laravel project, where you can modify them to fit your needs.

## Features

- Generates Laravel migrations from Prisma schema
- Creates Eloquent models with relationships
- Supports table creation, modification, and deletion
- Handles column additions, removals, and renames
- Manages foreign key constraints
- Automatically reorders migrations based on dependencies

## Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email paulobunga.one@gmail.com instead of using the issue tracker.

## Credits

- [Paul Obunga](https://github.com/paulobunga)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.