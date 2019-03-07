<?php

return [

    /*
     * The default language code that is used by the package
     * to make comparisons against other languages
     * in order to provide statistics.
     */
    'default_language_code' => 'en',

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
