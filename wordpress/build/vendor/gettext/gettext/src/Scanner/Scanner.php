<?php

declare (strict_types=1);
namespace ScopedGtbabel\Gettext\Scanner;

use Exception;
use ScopedGtbabel\Gettext\Translation;
use ScopedGtbabel\Gettext\Translations;
/**
 * Base class with common funtions for all scanners.
 */
abstract class Scanner implements \ScopedGtbabel\Gettext\Scanner\ScannerInterface
{
    protected $translations;
    protected $defaultDomain;
    public function __construct(\ScopedGtbabel\Gettext\Translations ...$allTranslations)
    {
        foreach ($allTranslations as $translations) {
            $domain = $translations->getDomain();
            $this->translations[$domain] = $translations;
        }
    }
    public function setDefaultDomain(string $defaultDomain) : void
    {
        $this->defaultDomain = $defaultDomain;
    }
    public function getDefaultDomain() : string
    {
        return $this->defaultDomain;
    }
    public function getTranslations() : array
    {
        return $this->translations;
    }
    public function scanFile(string $filename) : void
    {
        $string = static::readFile($filename);
        $this->scanString($string, $filename);
    }
    public abstract function scanString(string $string, string $filename) : void;
    protected function saveTranslation(?string $domain, ?string $context, string $original, string $plural = null) : ?\ScopedGtbabel\Gettext\Translation
    {
        if (\is_null($domain)) {
            $domain = $this->defaultDomain;
        }
        if (!isset($this->translations[$domain])) {
            return null;
        }
        $translation = $this->translations[$domain]->addOrMerge(\ScopedGtbabel\Gettext\Translation::create($context, $original));
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
