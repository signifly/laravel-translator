<?php

namespace Signifly\Translator\Tests;

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
    public function it_can_translate_multiple_attribute()
    {
        $this->assertCount(0, $this->product->translations);

        $this->product->translate('en', [
            'name' => $this->product->name,
            'description' => $this->product->description,
        ]);
        $this->assertEquals(2, $this->product->translations()->count());
    }

    /** @test */
    public function it_returns_the_correct_language_with_auto_translate_attributes_enabled()
    {
        config([
            'translator.active_language_code' => 'da',
            'translator.auto_translate_attributes' => true,
        ]);

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
}
