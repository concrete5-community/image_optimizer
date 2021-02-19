<?php

namespace A3020\ImageOptimizer\Queue;

use A3020\ImageOptimizer\FileList;
use A3020\ImageOptimizer\Finder\Finder;
use A3020\ImageOptimizer\MonthlyLimit;
use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Config\Repository\Repository;
use Symfony\Component\Finder\SplFileInfo;
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

    public function create(ZendQueue $q)
    {
        if ($this->monthlyLimit->reached()) {
            return;
        }

        if ($this->config->get('image_optimizer.include_filemanager_images')) {
            /** @var FileList $fl */
            $fl = $this->app->make(FileList::class);
            foreach ($fl->get() as $row) {
                $q->send(json_encode([
                    'fID' => $row['fID'],
                ]));
            }
        }

        if ($this->config->get('image_optimizer.include_cached_images')) {
            /** @var Finder $finder */
            $finder = $this->app->make(Finder::class);
            foreach ($finder->cacheImages() as $file) {
                /** @var SplFileInfo $file */
                $q->send(json_encode([
                    'path' => (string) $file->getRelativePathname(),
                ]));
            }
        }
    }
}