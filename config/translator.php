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
     * It should either be or extend the Signifly\Translator\Models\Translation model.
     */
    'translation_model' => \Signifly\Translator\Models\Translation::class,

];
