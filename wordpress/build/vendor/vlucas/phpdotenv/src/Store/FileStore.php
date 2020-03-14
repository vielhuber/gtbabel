<?php

namespace ScopedGtbabel\Dotenv\Store;

use ScopedGtbabel\Dotenv\Exception\InvalidPathException;
use ScopedGtbabel\Dotenv\Store\File\Reader;
class FileStore implements \ScopedGtbabel\Dotenv\Store\StoreInterface
{
    /**
     * The file paths.
     *
     * @var string[]
     */
    protected $filePaths;
    /**
     * Should file loading short circuit?
     *
     * @var bool
     */
    protected $shortCircuit;
    /**
     * Create a new file store instance.
     *
     * @param string[] $filePaths
     * @param bool     $shortCircuit
     *
     * @return void
     */
    public function __construct(array $filePaths, $shortCircuit)
    {
        $this->filePaths = $filePaths;
        $this->shortCircuit = $shortCircuit;
    }
    /**
     * Read the content of the environment file(s).
     *
     * @throws \Dotenv\Exception\InvalidPathException
     *
     * @return string
     */
    public function read()
    {
        if ($this->filePaths === []) {
            throw new \ScopedGtbabel\Dotenv\Exception\InvalidPathException('At least one environment file path must be provided.');
        }
        $contents = \ScopedGtbabel\Dotenv\Store\File\Reader::read($this->filePaths, $this->shortCircuit);
        if ($contents) {
            return \implode("\n", $contents);
        }
        throw new \ScopedGtbabel\Dotenv\Exception\InvalidPathException(\sprintf('Unable to read any of the environment file(s) at [%s].', \implode(', ', $this->filePaths)));
    }
}
