<?php

namespace Signifly\Translator\Tests;

use Signifly\Translator\Facades\Translator;
use Signifly\Translator\Tests\Models\Product;

class TranslatableTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->product = Product::first();
    }

    /** @test */
    public function it_can_translate_an_attribute()
    {
        $this->assertCount(0, $this->product->translations);

        $this->product->translateAttribute('en', 'name', $this->product->name);
        $this->assertEquals(1, $this->product->translations()->count());
    }

    /** @test */
    public function it_can_translate_multiple_attributes()
    {
        $this->assertCount(0, $this->product->translations);

        $this->product->translate('en', [
            'name' => $this->product->name,
            'description' => $this->product->description,
        ]);
        $this->assertEquals(2, $this->product->translations()->count());
    }

    /** @test */
    public function it_returns_the_active_language_with_auto_translation_enabled()
    {
        Translator::activateLanguage('da');
        Translator::enableAutoTranslation();

        $this->assertCount(0, $this->product->translations);

        $this->product->translate('en', [
            'name' => 'shoes',
            'description' => 'some shoes',
        ]);

        $this->product->translate('da', [
            'name' => 'sko',
            'description' => 'nogle sko',
        ]);
        $this->assertEquals(4, $this->product->translations()->count());

        tap($this->product->fresh(), function ($product) {
            $this->assertEquals('sko', $product->name);
            $this->assertEquals('nogle sko', $product->description);
        });
    }

    /** @test */
    public function it_converts_to_an_array_with_auto_translation_enabled()
    {
        Translator::activateLanguage('da');
        Translator::enableAutoTranslation();

        $this->assertCount(0, $this->product->translations);

        $this->product->updateAndTranslate('en', [
            'name' => 'shoes',
            'description' => 'some shoes',
        ]);

        $this->product->updateAndTranslate('da', [
            'name' => 'sko',
            'description' => 'nogle sko',
        ]);

        tap($this->product->fresh(), function ($product) {
            $data = $product->toArray();
            $this->assertEquals('sko', $data['name']);
            $this->assertEquals('nogle sko', $data['description']);
        });
    }

    /** @test */
    public function it_returns_the_default_language_with_auto_translation_enabled_and_no_active_language()
    {
        Translator::enableAutoTranslation();

        $this->assertCount(0, $this->product->translations);

        $this->product->updateAndTranslate('en', [
            'name' => 'shoes',
            'description' => 'some shoes',
        ]);

        $this->product->translate('da', [
            'name' => 'sko',
            'description' => 'nogle sko',
        ]);
        $this->assertEquals(4, $this->product->translations()->count());

        tap($this->product->fresh(), function ($product) {
            $this->assertEquals('shoes', $product->name);
            $this->assertEquals('some shoes', $product->description);
        });
    }

    /** @test */
    public function it_creates_and_translates_a_product()
    {
        $product = Product::createAndTranslate('en', [
            'name' => 'Name',
            'description' => 'Description',
        ]);

        $this->assertTrue($product->exists());
        $this->assertCount(2, $product->translations);
        $this->assertTrue($product->hasTranslation('en', 'name'));
        $this->assertTrue($product->hasTranslation('en', 'description'));
    }

    /** @test */
    public function it_updates_and_translates_the_model_if_its_the_default_language()
    {
        $this->assertEquals('name 1', $this->product->name);
        $this->assertEquals('description 1', $this->product->description);

        $this->product->updateAndTranslate('en', [
            'name' => 'new name',
            'description' => 'new description',
        ]);

        tap($this->product->fresh(), function ($product) {
            $this->assertEquals('new name', $product->name);
            $this->assertEquals('new description', $product->description);
            $this->assertTrue($product->hasTranslation('en', 'name'));
            $this->assertTrue($product->hasTranslation('en', 'description'));
        });
    }

    /** @test */
    public function it_only_translates_the_model_if_it_is_not_the_default_language()
    {
        $this->assertEquals('name 1', $this->product->name);
        $this->assertEquals('description 1', $this->product->description);

        $this->product->updateAndTranslate('da', [
            'name' => 'new name',
            'description' => 'new description',
        ]);

        tap($this->product->fresh(), function ($product) {
            $this->assertEquals('name 1', $product->name);
            $this->assertEquals('description 1', $product->description);
            $this->assertTrue($product->hasTranslation('da', 'name'));
            $this->assertTrue($product->hasTranslation('da', 'description'));
        });
    }

    /** @test */
    public function it_retrieves_translation_stats()
    {
        $this->markTestIncomplete('Need to rewrite scope to support sqlite!');

        $this->product->updateAndTranslate('en', [
            'name' => 'new name',
        ]);
        $this->product->updateAndTranslate('da', [
            'name' => 'nyt navn',
            'description' => 'beskrivelse',
        ]);

        $danish = Product::withTranslationStats('da')->first();
        $english = Product::withTranslationStats('en')->first();

        $this->assertEquals(100, $danish->translations_percentage);
        $this->assertEquals(50, $english->translations_percentage);
    }

    /** @test */
    public function it_reads_columns_from_the_schema()
    {
        $columns = $this->product->getTableColumns();

        $this->assertTrue($columns->contains('id'));
        $this->assertTrue($columns->contains('name'));
        $this->assertTrue($columns->contains('description'));
        $this->assertTrue($columns->contains('created_at'));
        $this->assertTrue($columns->contains('updated_at'));
    }

    /** @test */
    public function it_deletes_the_translation_if_an_empty_value_is_provided()
    {
        // Given a product with translations
        $this->product->updateAndTranslate('da', [
            'name' => 'new name',
            'description' => 'new description',
        ]);

        tap($this->product->fresh(), function ($product) {
            $this->assertEquals('name 1', $product->name);
            $this->assertEquals('description 1', $product->description);
            $this->assertTrue($product->hasTranslation('da', 'name'));
            $this->assertTrue($product->hasTranslation('da', 'description'));
            $this->assertEquals(
                'new name',
                $product->getTranslationValue('da', 'name')
            );
            $this->assertEquals(
                'new description',
                $product->getTranslationValue('da', 'description')
            );
        });

        // Then set the description to an empty value
        $this->product->updateAndTranslate('da', [
            'description' => '',
        ]);

        // Assert that the attribute is empty and
        // the translation has been deleted
        tap($this->product->fresh(), function ($product) {
            $this->assertEquals('description 1', $product->description);
            $this->assertFalse($product->hasTranslation('da', 'description'));
            $this->assertNull($product->getTranslationValue('da', 'description'));
        });
    }

    /** @test */
    public function it_translates_json_values()
    {
        $daData = ['person' => ['navn' => 'John Doe', 'alder' => 75]];
        $enData = ['person' => ['name' => 'John Doe', 'age' => 75]];

        $this->product->updateAndTranslate('en', [
            'data' => $enData,
        ]);

        $this->product->updateAndTranslate('da', [
            'data' => $daData,
        ]);

        tap($this->product->fresh(), function ($product) use ($daData, $enData) {
            $this->assertEquals($daData, $product->getTranslationValue('da', 'data'));
            $this->assertEquals($enData, $product->getTranslationValue('en', 'data'));
        });
    }
}
