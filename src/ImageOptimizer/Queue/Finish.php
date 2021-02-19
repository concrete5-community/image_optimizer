<?php

namespace A3020\ImageOptimizer\Queue;

use A3020\ImageOptimizer\Entity\ProcessedCacheFilesRepository;
use A3020\ImageOptimizer\Entity\ProcessedFilesRepository;
use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Config\Repository\Repository;
use ZendQueue\Queue as ZendQueue;

class Finish implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    /**
     * @var Repository
     */
    private $config;

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    public function finish(ZendQueue $q)
    {
        $nh = $this->app->make('helper/number');

        return t('All images have been optimized. Image Optimizer has saved you %s of disk space.',
            $nh->formatSize($this->getTotalSavedDiskSpace())
        );
    }

    /**
     * @return int
     */
    private function getTotalSavedDiskSpace()
    {
        $total = 0;

        /** @var ProcessedCacheFilesRepository $repo */
        $repo = $this->app->make(ProcessedCacheFilesRepository::class);
        $total += $repo->totalFileSize();

        /** @var ProcessedFilesRepository $repo */
        $repo = $this->app->make(ProcessedFilesRepository::class);
        $total += $repo->totalFileSize();

        return $total;
    }
}
