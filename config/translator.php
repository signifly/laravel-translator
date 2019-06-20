<?php

return [

    /*
     * The active language code that is used by the package
     * to return the correct language for a model.
     */
    'active_language_code' => null,

    /*
     * By default the package will not translate model attributes automatically.
     *
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
     *
     * It should implement the Signifly\Translator\Contracts\Translation interface
     * and extend Illuminate\Database\Eloquent\Model.
     */
    'translation_model' => \Signifly\Translator\Models\Translation::class,

];
