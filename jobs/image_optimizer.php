<?php

namespace Concrete\Package\ImageOptimizer\Job;

use A3020\ImageOptimizer\Entity\ProcessedCacheFilesRepository;
use A3020\ImageOptimizer\Entity\ProcessedFilesRepository;
use A3020\ImageOptimizer\Finder\Finder;
use A3020\ImageOptimizer\OptimizerChain;
use A3020\ImageOptimizer\OptimizerChainFactory;
use Concrete\Core\Attribute\Key\FileKey;
use Concrete\Core\Cache\Level\ExpensiveCache;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\File\File;
use Concrete\Core\File\FileList;
use Concrete\Core\Job\QueueableJob;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Support\Facade\Application;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use League\Flysystem\Cached\Storage\Psr6Cache;
use Symfony\Component\Finder\SplFileInfo;
use ZendQueue\Message as ZendQueueMessage;
use ZendQueue\Queue as ZendQueue;

final class ImageOptimizer extends QueueableJob
{
    protected $jQueueBatchSize = 5;

    /** @var \Concrete\Core\Application\Application
     * Not named 'app' on purpose because parent class might change
     */
    private $appInstance;

    /** @var Repository */
    private $config;

    /** @var EntityManager */
    private $entityManager;

    /** @var OptimizerChain */
    private $optimizerChain;

    public function getJobName()
    {
        return t('Image Optimizer');
    }

    public function getJobDescription()
    {
        return t('Optimizes PNGs, JPGs, SVGs, and GIFs.');
    }

    public function __construct()
    {
        $this->appInstance = Application::getFacadeApplication();
        $this->entityManager = $this->appInstance->make(EntityManager::class);
        $this->config = $this->appInstance->make(Repository::class);

        $this->jQueueBatchSize = (int) $this->config->get('image_optimizer.batch_size', 5);

        $this->loadOptimizer();

        parent::__construct();
    }

    /**
     * Start processing a queue
     * Typically this is where you would inject new messages into the queue.
     *
     * @param \ZendQueue\Queue $q
     *
     * @return mixed
     */
    public function start(ZendQueue $q)
    {
        if ($this->config->get('image_optimizer.include_filemanager_images')) {
            $fl = new FileList();
            $fl->ignorePermissions();
            $fl->filter('f.fslID', 1);
            $fl->filterByType(\Concrete\Core\File\Type\Type::T_IMAGE);

            $akHandle = 'exclude_from_image_optimizer';
            $ak = FileKey::getByHandle($akHandle);
            if ($ak) {
                $fl->filterByAttribute($akHandle, false);
            }

            foreach ($fl->executeGetResults() as $row) {
                $payload = json_encode([
                    'fID' => $row['fID'],
                ]);
                $q->send($payload);
            }
        }

        if ($this->config->get('image_optimizer.include_cached_images')) {
            /** @var Finder $finder */
            $finder = $this->appInstance->make(Finder::class);
            foreach ($finder->cacheImages() as $file) {
                /** @var SplFileInfo $file */
                $payload = json_encode([
                    'path' => (string) $file->getRelativePathname(),
                ]);
                $q->send($payload);
            }
        }
    }

    /**
     * Process a QueueMessage.
     *
     * @param \ZendQueue\Message $msg
     */
    public function processQueueItem(ZendQueueMessage $msg)
    {
        $body = json_decode($msg->body, true);

        if (isset($body['fID'])) {
            $file = File::getByID($body['fID']);
            $this->processFile($file);
        }

        if (isset($body['path'])) {
            $this->processCachedFile($body['path']);
        }
    }

    /**
     * Optimizes files from Filemanager.
     *
     * @param \Concrete\Core\Entity\File\File $file
     */
    private function processFile($file)
    {
        $fileVersion = $file->getVersion();

        /** @var ProcessedFilesRepository $repo */
        $repo = $this->appInstance->make(ProcessedFilesRepository::class);
        $record = $repo->findOrCreate($file->getFileID(), $fileVersion->getFileVersionID());

        if ($record->isProcessed()) {
            return;
        }

        $relativePath = $fileVersion->getRelativePath();
        $relativePath = str_replace(DIR_REL, '', $relativePath);
        $pathToImage = DIR_BASE . $relativePath;

        $fileSizeBeforeOptimization = $fileVersion->getFullSize();

        // Only optimize if the file is still on the file system
        if (file_exists($pathToImage)) {
            $this->optimizerChain->optimize($pathToImage);

            $this->clearFlysystemCache($file);

            $fileVersion->refreshAttributes(true);
        }

        $fileSizeDiff = $fileSizeBeforeOptimization - $fileVersion->getFullSize();
        $record->setFileSizeReduction($fileSizeDiff);

        // We'll mark as processed even if the file can't be found.
        $record->setProcessedAt(new DateTimeImmutable('now'));

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        $this->updateTotalSavedDiskSpace($fileSizeDiff);
    }

    /**
     * Optimize images in /application/files/cache directory.
     *
     * @param string $path
     */
    private function processCachedFile($path)
    {
        /** @var ProcessedCacheFilesRepository $repo */
        $repo = $this->appInstance->make(ProcessedCacheFilesRepository::class);
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
            $this->updateTotalSavedDiskSpace($fileSizeDiff);
        }

        // We'll mark as processed even if the file can't be found.
        $record->setProcessedAt(new DateTimeImmutable('now'));

        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }

    /**
     * Finish processing a queue.
     *
     * @param \ZendQueue\Queue $q
     *
     * @return mixed
     */
    public function finish(ZendQueue $q)
    {
        $nh = $this->appInstance->make('helper/number');

        return t('All images have been optimized. Image Optimizer has saved you %s of disk space.',
            $nh->formatSize($this->getTotalSavedDiskSpace())
        );
    }

    /**
     * Called in constructor (not ideal), but needed for compatibility reasons with 8.0 and 8.1.
     */
    private function loadOptimizer()
    {
        $this->loadDependencies();

        $this->optimizerChain = OptimizerChainFactory::create();

        /** @var Repository $config */
        $config = $this->appInstance->make(Repository::class);
        if ((bool) $config->get('image_optimizer.enable_log')) {
            $this->optimizerChain->useLogger($this->appInstance->make('log'));
        }
    }

    private function loadDependencies()
    {
        $packageService = $this->appInstance->make(PackageService::class);
        $pkg = $packageService->getByHandle('image_optimizer');
        require_once $pkg->getPackagePath()."/vendor/autoload.php";
    }

    /**
     * @param int $saved
     */
    private function updateTotalSavedDiskSpace($saved)
    {
        $total = $this->getTotalSavedDiskSpace();
        $total += (int) $saved;

        $this->config->save('image_optimizer.saved_disk_space', $total);
    }

    /**
     * @return int
     */
    private function getTotalSavedDiskSpace()
    {
        return (int) $this->config->get('image_optimizer.saved_disk_space');
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
        if (!class_exists('Psr6Cache')) {
            return;
        }

        $fslId = $file->getFileStorageLocationObject()->getID();
        $pool = $this->appInstance->make(ExpensiveCache::class)->pool;
        $cache = new Psr6Cache($pool, 'flysystem-id-' . $fslId);
        $cache->flush();
    }
}
