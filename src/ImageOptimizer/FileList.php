<?php

namespace A3020\ImageOptimizer;

use Concrete\Core\Attribute\Key\FileKey;
use Concrete\Core\Config\Repository\Repository;

class FileList
{
    /** @var Repository */
    private $config;

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    public function get()
    {
        $fl = new \Concrete\Core\File\FileList();
        $fl->ignorePermissions();
        $fl->filter('f.fslID', 1);
        $fl->filterByType(\Concrete\Core\File\Type\Type::T_IMAGE);

        if ($this->getMaxSize()) {
            $fl->filterBySize(0, $this->getMaxSize());
        }

        $akHandle = 'exclude_from_image_optimizer';
        $ak = FileKey::getByHandle($akHandle);
        if ($ak) {
            $fl->filterByAttribute($akHandle, false);
        }

        return $fl->executeGetResults();
    }

    /**
     * @return int max size in KB
     */
    private function getMaxSize()
    {
        return (int) $this->config->get('image_optimizer.max_image_size');
    }
}
