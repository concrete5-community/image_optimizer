<?php

namespace A3020\ImageOptimizer;

use Concrete\Core\Database\Connection\Connection;

class ThumbnailFileList
{
    /**
     * @var Connection
     */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function get()
    {
        return $this->db->fetchAll('SELECT path FROM FileImageThumbnailPaths WHERE isBuilt=1');
    }
}
