<?php

namespace ScopedGtbabel\Dotenv\Loader;

use ScopedGtbabel\Dotenv\Repository\RepositoryInterface;
interface LoaderInterface
{
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
    public function load(\ScopedGtbabel\Dotenv\Repository\RepositoryInterface $repository, $content);
}
