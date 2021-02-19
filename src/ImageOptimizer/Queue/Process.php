<?php

namespace A3020\ImageOptimizer\Queue;

use A3020\ImageOptimizer\Entity\ProcessedFile;
use A3020\ImageOptimizer\Repository\ProcessedFilesRepository;
use A3020\ImageOptimizer\MonthlyLimit;
use A3020\ImageOptimizer\OptimizerChain;
use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Cache\Level\ExpensiveCache;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\File\File;
use Concrete\Core\Logging\Logger;
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

    /** @var Logger */
    private $logger;

    public function __construct(Repository $config, EntityManager $entityManager, OptimizerChain $optimizerChain, MonthlyLimit $monthlyLimit, Logger $logger)
    {
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->optimizerChain = $optimizerChain;
        $this->monthlyLimit = $monthlyLimit;
        $this->logger = $logger;
    }

    /**
     * @param ZendQueueMessage $msg
     *
     * @return ProcessedFile|null
     */
    public function process(ZendQueueMessage $msg)
    {
        $processedFile = null;

        if ($this->monthlyLimit->reached()) {
            return null;
        }

        try {
            $body = json_decode($msg->body, true);

            if (isset($body['fID'])) {
                $processedFile = $this->processFile(File::getByID($body['fID']));
            }

            if (isset($body['path'])) {
                $processedFile = $this->processStaticFile($body['path']);
            }
        } catch (Exception $e) {
            $this->logger->addDebug($e->getMessage() . $e->getFile() . $e->getLine() . $e->getTraceAsString());

            return null;
        }

        return $processedFile;
    }

    /**
     * Optimizes files from File Manager.
     *
     * @param \Concrete\Core\Entity\File\File $file
     *
     * @return ProcessedFile
     */
    private function processFile($file)
    {
        $fileVersion = $file->getVersion();

        /** @var \A3020\ImageOptimizer\Repository\ProcessedFilesRepository $repo */
        $repo = $this->app->make(ProcessedFilesRepository::class);
        $processedFile = $repo->findOrCreateOriginal($file->getFileID(), $fileVersion->getFileVersionID());

        if ($processedFile->isProcessed()) {
            return null;
        }

        $relativePath = $fileVersion->getRelativePath();
        $relativePath = str_replace(DIR_REL, '', $relativePath);
        $pathToImage = DIR_BASE.$relativePath;

        $shouldSkip = $this->getSkipReason($pathToImage);
        $processedFile->setSkipReason($shouldSkip);

        $fileSizeBeforeOptimization = $fileVersion->getFullSize();
        $processedFile->setFileSizeOriginal($fileSizeBeforeOptimization);

        // Only optimize if the file is still on the file system
        if (file_exists($pathToImage) && $shouldSkip === null) {
            $this->optimizerChain->optimize($pathToImage);

            $this->clearFlysystemCache($file);

            $fileVersion->refreshAttributes(true);
        }

        $fileSizeDiff = $fileSizeBeforeOptimization - $fileVersion->getFullSize();
        $processedFile->setFileSizeReduction($fileSizeDiff);

        $this->save($processedFile);

        return $processedFile;
    }

    /**
     * Optimize images in /application/files/* directories.
     *
     * @param string $path
     *
     * @return ProcessedFile|null
     */
    private function processStaticFile($path)
    {
        /** @var \A3020\ImageOptimizer\Repository\ProcessedFilesRepository $repo */
        $repo = $this->app->make(ProcessedFilesRepository::class);
        $processedFile = $repo->findOrCreateDerived($path);

        if ($processedFile->isProcessed()) {
            return null;
        }

        $shouldSkip = $this->getSkipReason($path);
        $processedFile->setSkipReason($shouldSkip);

        // We stored a relative path in the queue table.
        // Let's make it absolute now.
        $path = DIR_FILES_UPLOADED_STANDARD.$path;

        // Only optimize if the file is still on the file system
        if (file_exists($path) && !$shouldSkip) {
            $makeTime = filemtime($path);
            $fileSizeBeforeOptimization = filesize($path);
            $processedFile->setFileSizeOriginal($fileSizeBeforeOptimization);

            if ($this->getMaxSize() && $fileSizeBeforeOptimization >= $this->getMaxSize()) {
                // Image is too big, let's skip this one
                return null;
            }

            $this->optimizerChain->optimize($path);

            // the md5 hash of the cache files also uses the modification date...
            touch($path, $makeTime);

            // Results of file size can be cached
            clearstatcache();

            $fileSizeAfterOptimization = filesize($path);

            $fileSizeDiff = $fileSizeBeforeOptimization - $fileSizeAfterOptimization;
            $processedFile->setFileSizeReduction($fileSizeDiff);
        }

        $this->save($processedFile);

        return $processedFile;
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

    /**
     * @return int max size in bytes
     */
    private function getMaxSize()
    {
        return (int) $this->config->get('image_optimizer.max_image_size') * 1024;
    }

    /**
     * @param string $path
     *
     * @return int|null
     */
    private function getSkipReason($path)
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ((bool) $this->config->get('image_optimizer.tiny_png.enabled') && $extension === 'png') {
            return ProcessedFile::SKIP_REASON_PNG_8_BUG;
        }

        return null;
    }
}
