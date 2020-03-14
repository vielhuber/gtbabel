<?php

declare (strict_types=1);
namespace ScopedGtbabel\Gettext\Loader;

use ScopedGtbabel\Gettext\Translations;
interface LoaderInterface
{
    public function loadFile(string $filename, \ScopedGtbabel\Gettext\Translations $translations = null) : \ScopedGtbabel\Gettext\Translations;
    public function loadString(string $string, \ScopedGtbabel\Gettext\Translations $translations = null) : \ScopedGtbabel\Gettext\Translations;
}
