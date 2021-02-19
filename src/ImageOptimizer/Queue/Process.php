<?php

namespace A3020\ImageOptimizer\Queue;

use A3020\ImageOptimizer\Entity\ProcessedCacheFilesRepository;
use A3020\ImageOptimizer\Entity\ProcessedFilesRepository;
use A3020\ImageOptimizer\MonthlyLimit;
use A3020\ImageOptimizer\OptimizerChain;
use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Cache\Level\ExpensiveCache;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\File\File;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Exception;
use League\Flysystem\Cached\Storage\Psr6Cache;
use ZendQueue\Message as ZendQueueMessage;

class Process implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    /** @var \A3020\ImageOptimizer\OptimizerChain */
    protected $optimizerChain;

    /** @var Repository */
    private $config;

    /** @var EntityManager */
    private $entityManager;

    /** @var MonthlyLimit */
    private $monthlyLimit;

    public function __construct(Repository $config, EntityManager $entityManager, OptimizerChain $optimizerChain, MonthlyLimit $monthlyLimit)
    {
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->optimizerChain = $optimizerChain;
        $this->monthlyLimit = $monthlyLimit;
    }

    public function process(ZendQueueMessage $msg)
    {
        if ($this->monthlyLimit->reached()) {
            return;
        }

        try {
            $body = json_decode($msg->body, true);

            if (isset($body['fID'])) {
                $this->processFile(File::getByID($body['fID']));
            }

            if (isset($body['path'])) {
                $this->processCachedFile($body['path']);
            }
        } catch (Exception $e) {
            $logger = $this->app->make('log');
            $logger->addDebug($e->getMessage() . $e->getFile() . $e->getLine() . $e->getTraceAsString());
        }
    }

    /**
     * Optimizes files from File Manager.
     *
     * @param \Concrete\Core\Entity\File\File $file
     */
    private function processFile($file)
    {
        $fileVersion = $file->getVersion();

        /** @var ProcessedFilesRepository $repo */
        $repo = $this->app->make(ProcessedFilesRepository::class);
        $record = $repo->findOrCreate($file->getFileID(), $fileVersion->getFileVersionID());

        if ($record->isProcessed()) {
            return;
        }

        $relativePath = $fileVersion->getRelativePath();
        $relativePath = str_replace(DIR_REL, '', $relativePath);
        $pathToImage = DIR_BASE.$relativePath;

        $fileSizeBeforeOptimization = $fileVersion->getFullSize();

        // Only optimize if the file is still on the file system
        if (file_exists($pathToImage)) {
            $this->optimizerChain->optimize($pathToImage);

            $this->clearFlysystemCache($file);

            $fileVersion->refreshAttributes(true);
        }

        $fileSizeDiff = $fileSizeBeforeOptimization - $fileVersion->getFullSize();
        $record->setFileSizeReduction($fileSizeDiff);

        $this->save($record);
    }

    /**
     * Optimize images in /application/files/cache directory.
     *
     * @param string $path
     */
    private function processCachedFile($path)
    {
        /** @var ProcessedCacheFilesRepository $repo */
        $repo = $this->app->make(ProcessedCacheFilesRepository::class);
        $record = $repo->findOrCreate($path);

        if ($record->isProcessed()) {
            return;
        }

        // We stored a relative path in the database.
        $path = $this->config->get('concrete.cache.directory').'/'.$path;

        // Only optimize if the file is still on the file system
        if (file_exists($path)) {
            $fileSizeBeforeOptimization = filesize($path);
            $this->optimizerChain->optimize($path);

            // Results of filesize can be cached
            clearstatcache();

            $fileSizeAfterOptimization = filesize($path);

            $fileSizeDiff = $fileSizeBeforeOptimization - $fileSizeAfterOptimization;
            $record->setFileSizeReduction($fileSizeDiff);
        }

        $this->save($record);
    }

    /**
     * Clears cache for flysystem, needed to get updated filesize.
     *
     * Only applies to c5 v8.2.x or higher.
     *
     * @param \Concrete\Core\Entity\File\File $file
     */
    private function clearFlysystemCache($file)
    {
        if (!class_exists('\League\Flysystem\Cached\Storage\Psr6Cache')) {
            return;
        }

        $fslId = $file->getFileStorageLocationObject()->getID();
        $pool = $this->app->make(ExpensiveCache::class)->pool;
        $cache = new Psr6Cache($pool, 'flysystem-id-' . $fslId);
        $cache->flush();
    }

    private function save($record)
    {
        // We'll mark as processed even if the file can't be found.
        $record->setProcessedAt(new DateTimeImmutable('now'));

        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }
}
