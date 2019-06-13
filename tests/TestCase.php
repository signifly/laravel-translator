<?php

namespace Signifly\Translator\Tests;

use CreateTranslationsTable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Signifly\Translator\Tests\Models\Product;
use Orchestra\Testbench\TestCase as Orchestra;
use Signifly\Translator\TranslatorServiceProvider;
use Signifly\BuilderMacros\BuilderMacroServiceProvider;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('app.key', 'base64:9e0yNQB60wgU/cqbP09uphPo3aglW3iQJy+u4JQgnQE=');
    }

    protected function getPackageProviders($app)
    {
        return [
            BuilderMacroServiceProvider::class,
            TranslatorServiceProvider::class,
        ];
    }

    protected function setUpDatabase(): void
    {
        $this->createProductsTable();
        $this->createTranslationsTable();
        $this->seedProductsTable();
    }

    protected function createProductsTable(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('description');
            $table->timestamps();
        });
    }

    protected function createTranslationsTable()
    {
        include_once __DIR__.'/../migrations/create_translations_table.php.stub';
        (new CreateTranslationsTable())->up();
    }

    protected function seedProductsTable(): void
    {
        foreach (range(1, 10) as $index) {
            Product::create([
                'name' => "name {$index}",
                'description' => "description {$index}",
            ]);
        }
    }
}
