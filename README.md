# Database based translations for your Eloquent models

[![Latest Version on Packagist](https://img.shields.io/packagist/v/signifly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/signifly/laravel-translator)
[![Build Status](https://img.shields.io/travis/signifly/laravel-translator/master.svg?style=flat-square)](https://travis-ci.org/signifly/laravel-translator)
[![StyleCI](https://styleci.io/repos/174323285/shield?branch=master)](https://styleci.io/repos/174323285)
[![Quality Score](https://img.shields.io/scrutinizer/g/signifly/laravel-translator.svg?style=flat-square)](https://scrutinizer-ci.com/g/signifly/laravel-translator)
[![Total Downloads](https://img.shields.io/packagist/dt/signifly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/signifly/laravel-translator)

The `signifly/laravel-translator` package allows you to easily add database based translations to your Eloquent models.

Below is a small example of how to use it:

```php
// Inside a model
use Signifly\Translator\Concerns\Translatable;

class Post extends Model
{
    use Translatable;

    public function getTranslatableFields() : array
    {
        return ['title', 'description'];
    }
}
```

## Documentation

To get started follow the installation instructions below.

## Installation

You can install the package via composer:

```bash
$ composer require signifly/laravel-translator
```

The package will automatically register itself.

## Testing
```bash
$ composer test
```

## Security

If you discover any security issues, please email dev@signifly.com instead of using the issue tracker.

## Credits

- [Morten Poul Jensen](https://github.com/pactode)
- [All contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
