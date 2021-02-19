<?php

namespace A3020\ImageOptimizer;

use A3020\ImageOptimizer\Finder\Finder;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\Connection\Connection;

class CacheImageList
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var Repository
     */
    private $config;

    /**
     * @var Finder
     */
    private $finder;

    public function __construct(Connection $db, Repository $config, Finder $finder)
    {
        $this->db = $db;
        $this->config = $config;
        $this->finder = $finder;
    }

    /**
     * Return a list of cache image paths that haven't been optimized
     *
     * @return array
     */
    public function get()
    {
        $paths = [];
        foreach ($this->finder->cacheImages() as $file) {
            $paths[] = '/cache/'.(string) $file->getRelativePathname();
        }

        $processed = $this->db->fetchAll('SELECT path FROM ImageOptimizerProcessedFiles
            WHERE path LIKE "/cache/%"
        ');
        $processed = !empty($processed) ? array_column($processed, 'path') : [];
        
        return array_diff($paths, $processed);
    }
}
