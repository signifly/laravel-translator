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
}
