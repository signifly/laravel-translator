<?php

namespace Signifly\Translatable\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Signifly\Translator\TranslatorServiceProvider;

abstract class TestCase extends Orchestra
{
    public function setUp()
    {
        parent::setUp();
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:9e0yNQB60wgU/cqbP09uphPo3aglW3iQJy+u4JQgnQE=');
    }

    protected function getPackageProviders($app)
    {
        return [TranslatorServiceProvider::class];
    }
}
