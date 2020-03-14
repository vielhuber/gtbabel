<?php

namespace ScopedGtbabel\Dotenv\Loader;

use ScopedGtbabel\Dotenv\Regex\Regex;
use ScopedGtbabel\Dotenv\Repository\RepositoryInterface;
use ScopedGtbabel\PhpOption\Option;
class Loader implements \ScopedGtbabel\Dotenv\Loader\LoaderInterface
{
    /**
     * The variable name whitelist.
     *
     * @var string[]|null
     */
    protected $whitelist;
    /**
     * Create a new loader instance.
     *
     * @param string[]|null $whitelist
     *
     * @return void
     */
    public function __construct(array $whitelist = null)
    {
        $this->whitelist = $whitelist;
    }
    /**
     * Load the given environment file content into the repository.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string                                 $content
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array<string,string|null>
     */
    public function load(\ScopedGtbabel\Dotenv\Repository\RepositoryInterface $repository, $content)
    {
        return $this->processEntries($repository, \ScopedGtbabel\Dotenv\Loader\Lines::process(\ScopedGtbabel\Dotenv\Regex\Regex::split("/(\r\n|\n|\r)/", $content)->getSuccess()));
    }
    /**
     * Process the environment variable entries.
     *
     * We'll fill out any nested variables, and acually set the variable using
     * the underlying environment variables instance.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string[]                               $entries
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array<string,string|null>
     */
    private function processEntries(\ScopedGtbabel\Dotenv\Repository\RepositoryInterface $repository, array $entries)
    {
        $vars = [];
        foreach ($entries as $entry) {
            list($name, $value) = \ScopedGtbabel\Dotenv\Loader\Parser::parse($entry);
            if ($this->whitelist === null || \in_array($name, $this->whitelist, \true)) {
                $vars[$name] = self::resolveNestedVariables($repository, $value);
                $repository->set($name, $vars[$name]);
            }
        }
        return $vars;
    }
    /**
     * Resolve the nested variables.
     *
     * Look for ${varname} patterns in the variable value and replace with an
     * existing environment variable.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param \Dotenv\Loader\Value|null              $value
     *
     * @return string|null
     */
    private static function resolveNestedVariables(\ScopedGtbabel\Dotenv\Repository\RepositoryInterface $repository, \ScopedGtbabel\Dotenv\Loader\Value $value = null)
    {
        return \ScopedGtbabel\PhpOption\Option::fromValue($value)->map(function (\ScopedGtbabel\Dotenv\Loader\Value $v) use($repository) {
            return \array_reduce($v->getVars(), function ($s, $i) use($repository) {
                return \substr($s, 0, $i) . self::resolveNestedVariable($repository, \substr($s, $i));
            }, $v->getChars());
        })->getOrElse(null);
    }
    /**
     * Resolve a single nested variable.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string                                 $str
     *
     * @return string
     */
    private static function resolveNestedVariable(\ScopedGtbabel\Dotenv\Repository\RepositoryInterface $repository, $str)
    {
        return \ScopedGtbabel\Dotenv\Regex\Regex::replaceCallback('/\\A\\${([a-zA-Z0-9_.]+)}/', function (array $matches) use($repository) {
            return \ScopedGtbabel\PhpOption\Option::fromValue($repository->get($matches[1]))->getOrElse($matches[0]);
        }, $str, 1)->success()->getOrElse($str);
    }
}
