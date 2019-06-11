# Database based translations for your Eloquent models

[![Latest Version on Packagist](https://img.shields.io/packagist/v/signifly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/signifly/laravel-translator)
[![Build Status](https://img.shields.io/travis/signifly/laravel-translator/master.svg?style=flat-square)](https://travis-ci.org/signifly/laravel-translator)
[![StyleCI](https://styleci.io/repos/174323285/shield?branch=master)](https://styleci.io/repos/174323285)
[![Quality Score](https://img.shields.io/scrutinizer/g/signifly/laravel-translator.svg?style=flat-square)](https://scrutinizer-ci.com/g/signifly/laravel-translator)
[![Total Downloads](https://img.shields.io/packagist/dt/signifly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/signifly/laravel-translator)

The `signifly/laravel-translator` package allows you to easily add database based translations to your Eloquent models.

Below is a small example of how to use it:

```php
// Add the trait to your translatable models
use Signifly\Translator\Concerns\Translatable;

class Post extends Model
{
    use Translatable;

    public function getTranslatableAttributes() : array
    {
        return ['title', 'description'];
    }
}
```

In order to store translations, you can do the following:

```php
$post = Post::find(1);
$post->translate('en', [
    'title' => 'Some title',
    'description' => 'description',
]);
// returns a Illuminate\Support\Collection of translations
```

You can also translate a single attribute:

```php
$post->translateAttribute('en', 'title', 'Some title');
// returns Signifly\Translator\Contracts\Translation
```

If you want to update the model's attributes as well, it can be accomplished using:

```php
Post::createAndTranslate('en', [
    'title' => 'Some title',
    'description' => 'description',
]);

// or when updating
$post->updateAndTranslate('en', [
    'title' => 'New title',
    'description' => 'New description',
]);
```

The `updateAndTranslate` method will detect if it is the default language and update accordingly.

## Documentation

To get started follow the installation instructions below.

## Installation

You can install the package via composer:

```bash
composer require signifly/laravel-translator
```

The package will automatically register itself.

You can publish the migration with:
```bash
php artisan vendor:publish --tag="translator-migrations"
```

*Note*: The default migration assumes you are using integers for your model IDs. If you are using UUIDs, or some other format, adjust the migration accordingly.


```bash
php artisan migrate
```

You can optionally publish the config file with:
```bash
php artisan vendor:publish --tag="translator-config"
```

This is the contents of the published config file:

```php
return [

    /*
     * The active language code that is used by the package
     * to return the correct language for a model.
     */
    'active_language_code' => null,

    /*
     * By default the package will not translate model attributes automatically.
     * It should be used with caution as it performs extra requests.
     * Remember to eager load the translations
     * in order to optimize performance.
     */
    'auto_translate_attributes' => false,

    /*
     * The default language code that is used by the package
     * to make comparisons against other languages
     * in order to provide statistics.
     */
    'default_language_code' => 'en',

    /*
     * By default the package will use the `lang` paramater
     * to set the active language code.
     */
    'language_parameter' => 'lang',

    /*
     * This determines if the translations can be soft deleted.
     */
    'soft_deletes' => false,

    /*
     * This is the name of the table that will be created by the migration and
     * used by the Translation model shipped with this package.
     */
    'table_name' => 'translations',

    /*
     * This model will be used to store translations.
     * It should be implements the Signifly\Translator\Contracts\Translation interface
     * and extend Illuminate\Database\Eloquent\Model.
     */
    'translation_model' => \Signifly\Translator\Models\Translation::class,

];
```

## Testing
```bash
composer test
```

## Security

If you discover any security issues, please email dev@signifly.com instead of using the issue tracker.

## Credits

- [Morten Poul Jensen](https://github.com/pactode)
- [All contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
