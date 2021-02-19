<?php

namespace A3020\ImageOptimizer\Finder;

use Concrete\Core\Config\Repository\Repository;
use DirectoryIterator;

class Finder
{
    /**
     * @var Repository
     */
    private $config;

    /**
     * Finder constructor.
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * @return \Iterator
     */
    public function cacheImages()
    {
        $dir = $this->config->get('concrete.cache.directory');
        if (!is_dir($dir)) {
            return new \EmptyIterator();
        }

        $iterator = new DirectoryIterator($dir);
        return new CacheImageFilterIterator($iterator);
    }
}
