<?php

namespace A3020\ImageOptimizer\Ajax;

use A3020\ImageOptimizer\Controller\AjaxController;
use A3020\ImageOptimizer\Repository\ProcessedCacheFilesRepository;
use A3020\ImageOptimizer\Repository\ProcessedFilesRepository;
use Concrete\Core\File\File;
use Concrete\Core\Http\ResponseFactory;

class Files extends AjaxController
{
    protected $records = [];

    /** @var \Concrete\Core\Utility\Service\Number */
    protected $numberHelper;

    public function view()
    {
        $this->numberHelper = $this->app->make('helper/number');

        $this->addOriginalFiles();
        $this->addDerivedFiles();

        return $this->app->make(ResponseFactory::class)->json([
            'data' => $this->records,
        ]);
    }

    private function addOriginalFiles()
    {
        /** @var ProcessedFilesRepository $repository */
        $repository = $this->app->make(ProcessedFilesRepository::class);
        foreach ($repository->findAll() as $processedFile) {
            $file = File::getByID($processedFile->getOriginalFileId());
            if (!$file || $file->isError()) {
                continue;
            }

            $sizeKb = $this->size($processedFile->getFileSizeReduction());

            $this->records[] = [
                'path' => $file->getVersion()->getRelativePath(),
                'id' => $processedFile->getId(),
                'is_original' => true,
                'size_reduction' => $this->size($processedFile->getFileSizeReduction()),
                'size_reduction_human' => $sizeKb > 1024 ? $this->numberHelper->formatSize($processedFile->getFileSizeReduction()) : '',
            ];
        }
    }

    private function addDerivedFiles()
    {
        /** @var ProcessedCacheFilesRepository $repository */
        $repository = $this->app->make(ProcessedCacheFilesRepository::class);
        foreach ($repository->findAll() as $processedFile) {
            $sizeKb = $this->size($processedFile->getFileSizeReduction());

            $this->records[] = [
                'path' => REL_DIR_FILES_UPLOADED_STANDARD.'/'.$processedFile->getCacheIdentifier(),
                'id' => $processedFile->getId(),
                'is_original' => false,
                'size_reduction' => $sizeKb,
                'size_reduction_human' => $sizeKb > 1024 ? $this->numberHelper->formatSize($processedFile->getFileSizeReduction()) : '',
            ];
        }
    }

    /**
     * @param int $size in bytes
     *
     * @return float
     */
    private function size($size)
    {
        return max(0, round($size / 1024, 2));
    }
}
