<?php

namespace Signifly\Translator\Tests;

use Signifly\Translator\Facades\Translator;

class TranslatorTest extends TestCase
{
    /** @test */
    public function it_activates_a_language()
    {
        $this->assertEquals('en', Translator::activeLanguageCode());

        Translator::activateLanguage('da');

        $this->assertEquals('da', Translator::activeLanguageCode());
    }

    /** @test */
    public function it_disables_auto_translation()
    {
        Translator::enableAutoTranslation();
        $this->assertTrue(Translator::autoTranslates());

        Translator::disableAutoTranslation();

        $this->assertFalse(Translator::autoTranslates());
    }

    /** @test */
    public function it_enables_auto_translation()
    {
        $this->assertFalse(Translator::autoTranslates());

        Translator::enableAutoTranslation();

        $this->assertTrue(Translator::autoTranslates());
    }
}
