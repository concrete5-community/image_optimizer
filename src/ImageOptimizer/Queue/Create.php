<?php

namespace A3020\ImageOptimizer\Queue;

use A3020\ImageOptimizer\CacheImageList;
use A3020\ImageOptimizer\Exception\MonthlyLimitReached;
use A3020\ImageOptimizer\FileList;
use A3020\ImageOptimizer\MonthlyLimit;
use A3020\ImageOptimizer\ThumbnailFileList;
use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Config\Repository\Repository;
use ZendQueue\Queue as ZendQueue;

class Create implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    /**
     * @var Repository
     */
    private $config;

    /**
     * @var MonthlyLimit
     */
    private $monthlyLimit;

    public function __construct(Repository $config, MonthlyLimit $monthlyLimit)
    {
        $this->config = $config;
        $this->monthlyLimit = $monthlyLimit;
    }

    /**
     * @param ZendQueue $queue
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws MonthlyLimitReached
     *
     * @return ZendQueue
     */
    public function create(ZendQueue $queue)
    {
        if ($this->monthlyLimit->reached()) {
            throw new MonthlyLimitReached('Monthly limit reached.');
        }

        if ($this->config->get('image_optimizer::settings.include_filemanager_images')) {
            /** @var FileList $list */
            $list = $this->app->make(FileList::class);
            foreach ($list->get() as $row) {
                $queue->send(json_encode($row));
            }
        }

        if ($this->config->get('image_optimizer::settings.include_thumbnail_images', true)) {
            /** @var ThumbnailFileList $list */
            $list = $this->app->make(ThumbnailFileList::class);
            foreach ($list->get() as $row) {
                $queue->send(json_encode($row));
            }
        }

        if ($this->config->get('image_optimizer::settings.include_cached_images')) {
            /** @var CacheImageList $list */
            $list = $this->app->make(CacheImageList::class);
            foreach ($list->get() as $path) {
                $queue->send(json_encode([
                    'path' => $path,
                ]));
            }
        }

        return $queue;
    }
}

