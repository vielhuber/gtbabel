<?php

declare (strict_types=1);
namespace ScopedGtbabel\Gettext\Loader;

use Exception;
use ScopedGtbabel\Gettext\Translation;
use ScopedGtbabel\Gettext\Translations;
/**
 * Base class with common funtions for all loaders.
 */
abstract class Loader implements \ScopedGtbabel\Gettext\Loader\LoaderInterface
{
    public function loadFile(string $filename, \ScopedGtbabel\Gettext\Translations $translations = null) : \ScopedGtbabel\Gettext\Translations
    {
        $string = static::readFile($filename);
        return $this->loadString($string, $translations);
    }
    public function loadString(string $string, \ScopedGtbabel\Gettext\Translations $translations = null) : \ScopedGtbabel\Gettext\Translations
    {
        return $translations ?: $this->createTranslations();
    }
    protected function createTranslations() : \ScopedGtbabel\Gettext\Translations
    {
        return \ScopedGtbabel\Gettext\Translations::create();
    }
    protected function createTranslation(?string $context, string $original, string $plural = null) : ?\ScopedGtbabel\Gettext\Translation
    {
        $translation = \ScopedGtbabel\Gettext\Translation::create($context, $original);
        if (isset($plural)) {
            $translation->setPlural($plural);
        }
        return $translation;
    }
    /**
     * Reads and returns the content of a file.
     */
    protected static function readFile(string $file) : string
    {
        $length = \filesize($file);
        if (!($fd = \fopen($file, 'rb'))) {
            throw new \Exception("Cannot read the file '{$file}', probably permissions");
        }
        $content = $length ? \fread($fd, $length) : '';
        \fclose($fd);
        return $content;
    }
}
