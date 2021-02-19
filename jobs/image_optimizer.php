<?php

namespace Concrete\Package\ImageOptimizer\Job;

use A3020\ImageOptimizer\Entity\ProcessedCacheFilesRepository;
use A3020\ImageOptimizer\Entity\ProcessedFilesRepository;
use A3020\ImageOptimizer\Finder\Finder;
use A3020\ImageOptimizer\OptimizerChain;
use A3020\ImageOptimizer\OptimizerChainFactory;
use Concrete\Core\Attribute\Key\FileKey;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\File\File;
use Concrete\Core\File\FileList;
use Concrete\Core\Job\QueueableJob;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Support\Facade\Application;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Process\Process;
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
    }

    /**
     * @inheritdoc
     */
    public function executeBatch($batch, ZendQueue $queue)
    {
        $this->loadOptimizer();

        parent::executeBatch($batch, $queue);
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

            $results = $fl->executeGetResults();

            foreach ($results as $row) {
                $payload = json_encode([
                    'fID' => $row['fID'],
                ]);
                $q->send($payload);
            }
        }

        if ($this->config->get('image_optimizer.include_cached_images')) {
            $finder = $this->appInstance->make(Finder::class);
            foreach ($finder->cacheImages() as $fileName) {
                $fileName = (string) $fileName;
                $payload = json_encode([
                    'fileName' => $fileName,
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

        if (isset($body['fileName'])) {
            $this->processCachedFile($body['fileName']);
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

            // Results of filesize can be cached
            clearstatcache();

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
     * @param string $fileName
     */
    private function processCachedFile($fileName)
    {
        /** @var ProcessedCacheFilesRepository $repo */
        $repo = $this->appInstance->make(ProcessedCacheFilesRepository::class);
        $record = $repo->findOrCreate($fileName);

        if ($record->isProcessed()) {
            return;
        }

        $pathToImage = $this->config->get('concrete.cache.directory') . '/'. $fileName;

        // Only optimize if the file is still on the file system
        if (file_exists($pathToImage)) {
            $fileSizeBeforeOptimization = filesize($pathToImage);
            $this->optimizerChain->optimize($pathToImage);

            // Results of filesize can be cached
            clearstatcache();

            $fileSizeAfterOptimization = filesize($pathToImage);

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
     * Do not put this in the constructor, otherwise it will load each time the
     * jobs page is opened. Instead, once the queue runs, it will load.
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
}
