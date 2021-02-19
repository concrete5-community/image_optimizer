<?php

namespace A3020\ImageOptimizer;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\Connection\Connection;

class ThumbnailFileList
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var Repository
     */
    private $config;

    public function __construct(Connection $db, Repository $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * Return a list of thumbnail paths that haven't been optimized
     *
     * @return array
     */
    public function get()
    {
        return $this->db->fetchAll('SELECT tp.path FROM FileImageThumbnailPaths tp
            LEFT JOIN ImageOptimizerProcessedFiles pf ON pf.path = tp.path
            WHERE pf.path IS NULL AND tp.isBuilt=1');
    }
}
