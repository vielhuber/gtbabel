<?php

namespace ScopedGtbabel\Dotenv;

use ScopedGtbabel\Dotenv\Exception\InvalidPathException;
use ScopedGtbabel\Dotenv\Loader\Loader;
use ScopedGtbabel\Dotenv\Loader\LoaderInterface;
use ScopedGtbabel\Dotenv\Repository\RepositoryBuilder;
use ScopedGtbabel\Dotenv\Repository\RepositoryInterface;
use ScopedGtbabel\Dotenv\Store\FileStore;
use ScopedGtbabel\Dotenv\Store\StoreBuilder;
class Dotenv
{
    /**
     * The loader instance.
     *
     * @var \Dotenv\Loader\LoaderInterface
     */
    protected $loader;
    /**
     * The repository instance.
     *
     * @var \Dotenv\Repository\RepositoryInterface
     */
    protected $repository;
    /**
     * The store instance.
     *
     * @var \Dotenv\Store\StoreInterface
     */
    protected $store;
    /**
     * Create a new dotenv instance.
     *
     * @param \Dotenv\Loader\LoaderInterface         $loader
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param \Dotenv\Store\StoreInterface|string[]  $store
     *
     * @return void
     */
    public function __construct(\ScopedGtbabel\Dotenv\Loader\LoaderInterface $loader, \ScopedGtbabel\Dotenv\Repository\RepositoryInterface $repository, $store)
    {
        $this->loader = $loader;
        $this->repository = $repository;
        $this->store = \is_array($store) ? new \ScopedGtbabel\Dotenv\Store\FileStore($store, \true) : $store;
    }
    /**
     * Create a new dotenv instance.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string|string[]                        $paths
     * @param string|string[]|null                   $names
     * @param bool                                   $shortCircuit
     *
     * @return \Dotenv\Dotenv
     */
    public static function create(\ScopedGtbabel\Dotenv\Repository\RepositoryInterface $repository, $paths, $names = null, $shortCircuit = \true)
    {
        $builder = \ScopedGtbabel\Dotenv\Store\StoreBuilder::create()->withPaths($paths)->withNames($names);
        if ($shortCircuit) {
            $builder = $builder->shortCircuit();
        }
        return new self(new \ScopedGtbabel\Dotenv\Loader\Loader(), $repository, $builder->make());
    }
    /**
     * Create a new mutable dotenv instance with default repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     *
     * @return \Dotenv\Dotenv
     */
    public static function createMutable($paths, $names = null, $shortCircuit = \true)
    {
        $repository = \ScopedGtbabel\Dotenv\Repository\RepositoryBuilder::create()->make();
        return self::create($repository, $paths, $names, $shortCircuit);
    }
    /**
     * Create a new immutable dotenv instance with default repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     *
     * @return \Dotenv\Dotenv
     */
    public static function createImmutable($paths, $names = null, $shortCircuit = \true)
    {
        $repository = \ScopedGtbabel\Dotenv\Repository\RepositoryBuilder::create()->immutable()->make();
        return self::create($repository, $paths, $names, $shortCircuit);
    }
    /**
     * Read and load environment file(s).
     *
     * @throws \Dotenv\Exception\InvalidPathException|\Dotenv\Exception\InvalidFileException
     *
     * @return array<string,string|null>
     */
    public function load()
    {
        return $this->loader->load($this->repository, $this->store->read());
    }
    /**
     * Read and load environment file(s), silently failing if no files can be read.
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array<string,string|null>
     */
    public function safeLoad()
    {
        try {
            return $this->load();
        } catch (\ScopedGtbabel\Dotenv\Exception\InvalidPathException $e) {
            // suppressing exception
            return [];
        }
    }
    /**
     * Required ensures that the specified variables exist, and returns a new validator object.
     *
     * @param string|string[] $variables
     *
     * @return \Dotenv\Validator
     */
    public function required($variables)
    {
        return new \ScopedGtbabel\Dotenv\Validator($this->repository, (array) $variables);
    }
    /**
     * Returns a new validator object that won't check if the specified variables exist.
     *
     * @param string|string[] $variables
     *
     * @return \Dotenv\Validator
     */
    public function ifPresent($variables)
    {
        return new \ScopedGtbabel\Dotenv\Validator($this->repository, (array) $variables, \false);
    }
}
