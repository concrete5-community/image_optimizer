<?php

namespace A3020\ImageOptimizer\Queue;

use A3020\ImageOptimizer\Entity\ProcessedCacheFilesRepository;
use A3020\ImageOptimizer\Entity\ProcessedFilesRepository;
use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Config\Repository\Repository;
use Exception;
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

        $totalSavedDiskSpace = $this->getTotalSavedDiskSpace();

        if ($totalSavedDiskSpace === 0) {
            throw new Exception(t("Do you have any of the optimizers installed or configured? The Image Optimizer couldn't gain any file size. Read more: %s",
                '<a href="https://www.concrete5.org/marketplace/addons/image-optimizer/faq/" target="_blank">https://www.concrete5.org/marketplace/addons/image-optimizer/faq/</a>'
            ));
        }

        return t('All images have been optimized. Image Optimizer has saved you %s of disk space.',
            $nh->formatSize($totalSavedDiskSpace)
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
