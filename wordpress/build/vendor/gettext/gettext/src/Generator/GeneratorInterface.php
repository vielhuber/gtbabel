<?php

declare (strict_types=1);
namespace ScopedGtbabel\Gettext\Generator;

use ScopedGtbabel\Gettext\Translations;
interface GeneratorInterface
{
    public function generateFile(\ScopedGtbabel\Gettext\Translations $translations, string $filename) : bool;
    public function generateString(\ScopedGtbabel\Gettext\Translations $translations) : string;
}
