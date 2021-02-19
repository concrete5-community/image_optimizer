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

    public function get()
    {
        if (version_compare($this->config->get('concrete.version_installed'), '8.3.0')) {
            // If current version is below 8.2.1, the isBuilt column is missing or not functional
            // See also https://github.com/concrete5/concrete5/commit/71dc1f40bad2c8fa2a46b6b4d62e33e034de7a4c#diff-6393ea510f4fee84506b1e0b1e32ca87
            return $this->db->fetchAll('SELECT path FROM FileImageThumbnailPaths');
        }

        return $this->db->fetchAll('SELECT path FROM FileImageThumbnailPaths WHERE isBuilt=1');
    }
}
