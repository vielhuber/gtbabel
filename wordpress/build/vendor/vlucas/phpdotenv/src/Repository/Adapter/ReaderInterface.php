<?php

namespace ScopedGtbabel\Dotenv\Repository\Adapter;

interface ReaderInterface extends \ScopedGtbabel\Dotenv\Repository\Adapter\AvailabilityInterface
{
    /**
     * Get an environment variable, if it exists.
     *
     * @param string $name
     *
     * @return \PhpOption\Option<string|null>
     */
    public function get($name);
}
