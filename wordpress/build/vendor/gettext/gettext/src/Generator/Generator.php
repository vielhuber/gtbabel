<?php

declare (strict_types=1);
namespace ScopedGtbabel\Gettext\Generator;

use ScopedGtbabel\Gettext\Translations;
abstract class Generator implements \ScopedGtbabel\Gettext\Generator\GeneratorInterface
{
    public function generateFile(\ScopedGtbabel\Gettext\Translations $translations, string $filename) : bool
    {
        $content = $this->generateString($translations);
        return \file_put_contents($filename, $content) !== \false;
    }
    public abstract function generateString(\ScopedGtbabel\Gettext\Translations $translations) : string;
}
