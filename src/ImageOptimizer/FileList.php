<?php

namespace A3020\ImageOptimizer;

use Concrete\Core\Attribute\Key\FileKey;

class FileList
{
    public function get()
    {
        $fl = new \Concrete\Core\File\FileList();
        $fl->ignorePermissions();
        $fl->filter('f.fslID', 1);
        $fl->filterByType(\Concrete\Core\File\Type\Type::T_IMAGE);

        $akHandle = 'exclude_from_image_optimizer';
        $ak = FileKey::getByHandle($akHandle);
        if ($ak) {
            $fl->filterByAttribute($akHandle, false);
        }

        return $fl->executeGetResults();
    }
}
