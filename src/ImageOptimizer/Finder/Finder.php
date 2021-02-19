<?php

namespace A3020\ImageOptimizer\Finder;

use Concrete\Core\Config\Repository\Repository;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;

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

    public function cacheImages()
    {
        $dir = $this->config->get('concrete.cache.directory');
        if (!is_dir($dir)) {
            return new \EmptyIterator();
        }

        $finder = new \Symfony\Component\Finder\Finder();

        return $finder->files()->name('/\.(?:jpe?g|png|gif)$/')->in($dir);
    }
}
