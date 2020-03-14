<?php

namespace ScopedGtbabel\Dotenv\Repository\Adapter;

use ScopedGtbabel\PhpOption\None;
use ScopedGtbabel\PhpOption\Some;
class ArrayAdapter implements \ScopedGtbabel\Dotenv\Repository\Adapter\AvailabilityInterface, \ScopedGtbabel\Dotenv\Repository\Adapter\ReaderInterface, \ScopedGtbabel\Dotenv\Repository\Adapter\WriterInterface
{
    /**
     * The variables and their values.
     *
     * @var array<string,string|null>
     */
    private $variables = [];
    /**
     * Determines if the adapter is supported.
     *
     * @return bool
     */
    public function isSupported()
    {
        return \true;
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
        if (\array_key_exists($name, $this->variables)) {
            return \ScopedGtbabel\PhpOption\Some::create($this->variables[$name]);
        }
        return \ScopedGtbabel\PhpOption\None::create();
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
        $this->variables[$name] = $value;
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
        unset($this->variables[$name]);
    }
}
