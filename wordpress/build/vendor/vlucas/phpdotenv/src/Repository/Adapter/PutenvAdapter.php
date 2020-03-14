<?php

namespace ScopedGtbabel\Dotenv\Repository\Adapter;

use ScopedGtbabel\PhpOption\Option;
class PutenvAdapter implements \ScopedGtbabel\Dotenv\Repository\Adapter\AvailabilityInterface, \ScopedGtbabel\Dotenv\Repository\Adapter\ReaderInterface, \ScopedGtbabel\Dotenv\Repository\Adapter\WriterInterface
{
    /**
     * Determines if the adapter is supported.
     *
     * @return bool
     */
    public function isSupported()
    {
        return \function_exists('getenv') && \function_exists('putenv');
    }
    /**
     * Get an environment variable, if it exists.
     *
     * @param string $name
     *
     * @return \PhpOption\Option<string|null>
     */
    public function get($name)
    {
        /** @var \PhpOption\Option<string|null> */
        return \ScopedGtbabel\PhpOption\Option::fromValue(\getenv($name), \false);
    }
    /**
     * Set an environment variable.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return void
     */
    public function set($name, $value = null)
    {
        \putenv("{$name}={$value}");
    }
    /**
     * Clear an environment variable.
     *
     * @param string $name
     *
     * @return void
     */
    public function clear($name)
    {
        \putenv($name);
    }
}
