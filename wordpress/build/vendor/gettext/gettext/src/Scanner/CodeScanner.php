<?php

declare (strict_types=1);
namespace ScopedGtbabel\Gettext\Scanner;

use Exception;
use ScopedGtbabel\Gettext\Translation;
use ScopedGtbabel\Gettext\Translations;
/**
 * Base class with common functions to scan files with code and get gettext translations.
 */
abstract class CodeScanner extends \ScopedGtbabel\Gettext\Scanner\Scanner
{
    protected $ignoreInvalidFunctions = \false;
    protected $commentsPrefixes = [];
    protected $functions = ['gettext' => 'gettext', '_' => 'gettext', '__' => 'gettext', 'ngettext' => 'ngettext', 'n__' => 'ngettext', 'pgettext' => 'pgettext', 'p__' => 'pgettext', 'dgettext' => 'dgettext', 'd__' => 'dgettext', 'dngettext' => 'dngettext', 'dn__' => 'dngettext', 'dpgettext' => 'dpgettext', 'dp__' => 'dpgettext', 'npgettext' => 'npgettext', 'np__' => 'npgettext', 'dnpgettext' => 'dnpgettext', 'dnp__' => 'dnpgettext', 'noop' => 'gettext', 'noop__' => 'gettext'];
    /**
     * @param array $functions [fnName => handler]
     */
    public function setFunctions(array $functions) : self
    {
        $this->functions = $functions;
        return $this;
    }
    /**
     * @return array [fnName => handler]
     */
    public function getFunctions() : array
    {
        return $this->functions;
    }
    public function ignoreInvalidFunctions($ignore = \true) : self
    {
        $this->ignoreInvalidFunctions = $ignore;
        return $this;
    }
    public function extractCommentsStartingWith(string ...$prefixes) : self
    {
        $this->commentsPrefixes = $prefixes;
        return $this;
    }
    public function scanString(string $string, string $filename) : void
    {
        $functionsScanner = $this->getFunctionsScanner();
        $functions = $functionsScanner->scan($string, $filename);
        foreach ($functions as $function) {
            $this->handleFunction($function);
        }
    }
    public abstract function getFunctionsScanner() : \ScopedGtbabel\Gettext\Scanner\FunctionsScannerInterface;
    protected function handleFunction(\ScopedGtbabel\Gettext\Scanner\ParsedFunction $function)
    {
        $name = $function->getName();
        $handler = $this->functions[$name] ?? null;
        if (\is_null($handler)) {
            return;
        }
        $translation = \call_user_func([$this, $handler], $function);
        if ($translation) {
            $translation->getReferences()->add($function->getFilename(), $function->getLine());
        }
    }
    protected function gettext(\ScopedGtbabel\Gettext\Scanner\ParsedFunction $function) : ?\ScopedGtbabel\Gettext\Translation
    {
        if (!$this->checkFunction($function, 1)) {
            return null;
        }
        list($original) = $function->getArguments();
        return $this->addComments($function, $this->saveTranslation(null, null, $original));
    }
    protected function ngettext(\ScopedGtbabel\Gettext\Scanner\ParsedFunction $function) : ?\ScopedGtbabel\Gettext\Translation
    {
        if (!$this->checkFunction($function, 2)) {
            return null;
        }
        list($original, $plural) = $function->getArguments();
        return $this->addComments($function, $this->saveTranslation(null, null, $original, $plural));
    }
    protected function pgettext(\ScopedGtbabel\Gettext\Scanner\ParsedFunction $function) : ?\ScopedGtbabel\Gettext\Translation
    {
        if (!$this->checkFunction($function, 2)) {
            return null;
        }
        list($context, $original) = $function->getArguments();
        return $this->addComments($function, $this->saveTranslation(null, $context, $original));
    }
    protected function dgettext(\ScopedGtbabel\Gettext\Scanner\ParsedFunction $function) : ?\ScopedGtbabel\Gettext\Translation
    {
        if (!$this->checkFunction($function, 2)) {
            return null;
        }
        list($domain, $original) = $function->getArguments();
        return $this->addComments($function, $this->saveTranslation($domain, null, $original));
    }
    protected function dpgettext(\ScopedGtbabel\Gettext\Scanner\ParsedFunction $function) : ?\ScopedGtbabel\Gettext\Translation
    {
        if (!$this->checkFunction($function, 3)) {
            return null;
        }
        list($domain, $context, $original) = $function->getArguments();
        return $this->addComments($function, $this->saveTranslation($domain, $context, $original));
    }
    protected function npgettext(\ScopedGtbabel\Gettext\Scanner\ParsedFunction $function) : ?\ScopedGtbabel\Gettext\Translation
    {
        if (!$this->checkFunction($function, 3)) {
            return null;
        }
        list($context, $original, $plural) = $function->getArguments();
        return $this->addComments($function, $this->saveTranslation(null, $context, $original, $plural));
    }
    protected function dngettext(\ScopedGtbabel\Gettext\Scanner\ParsedFunction $function) : ?\ScopedGtbabel\Gettext\Translation
    {
        if (!$this->checkFunction($function, 3)) {
            return null;
        }
        list($domain, $original, $plural) = $function->getArguments();
        return $this->addComments($function, $this->saveTranslation($domain, null, $original, $plural));
    }
    protected function dnpgettext(\ScopedGtbabel\Gettext\Scanner\ParsedFunction $function) : ?\ScopedGtbabel\Gettext\Translation
    {
        if (!$this->checkFunction($function, 4)) {
            return null;
        }
        list($domain, $context, $original, $plural) = $function->getArguments();
        return $this->addComments($function, $this->saveTranslation($domain, $context, $original, $plural));
    }
    protected function addComments(\ScopedGtbabel\Gettext\Scanner\ParsedFunction $function, ?\ScopedGtbabel\Gettext\Translation $translation) : ?\ScopedGtbabel\Gettext\Translation
    {
        if (empty($this->commentsPrefixes) || empty($translation)) {
            return $translation;
        }
        foreach ($function->getComments() as $comment) {
            if ($this->checkComment($comment)) {
                $translation->getExtractedComments()->add($comment);
            }
        }
        return $translation;
    }
    protected function checkFunction(\ScopedGtbabel\Gettext\Scanner\ParsedFunction $function, int $minLength) : bool
    {
        if ($function->countArguments() < $minLength) {
            if ($this->ignoreInvalidFunctions) {
                return \false;
            }
            throw new \Exception(\sprintf('Invalid gettext function in %s:%d. At least %d arguments are required', $function->getFilename(), $function->getLine(), $minLength));
        }
        $arguments = \array_slice($function->getArguments(), 0, $minLength);
        if (\in_array(null, $arguments, \true)) {
            if ($this->ignoreInvalidFunctions) {
                return \false;
            }
            throw new \Exception(\sprintf('Invalid gettext function in %s:%d. Some required arguments are not valid', $function->getFilename(), $function->getLine()));
        }
        return \true;
    }
    protected function checkComment(string $comment) : bool
    {
        foreach ($this->commentsPrefixes as $prefix) {
            if ($prefix === '' || \strpos($comment, $prefix) === 0) {
                return \true;
            }
        }
        return \false;
    }
}
