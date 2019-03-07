# Database based translations for your Eloquent models

The `signifly/laravel-translator` package allows you to easily add database based translations to your Eloquent models.

Below is a small example of how to use it.

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
