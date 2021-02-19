<?php

namespace A3020\ImageOptimizer\Ajax;

use A3020\ImageOptimizer\Controller\AjaxController;
use A3020\ImageOptimizer\Repository\ProcessedFilesRepository;
use Concrete\Core\File\File;
use Concrete\Core\Http\ResponseFactory;

class Files extends AjaxController
{
    /** @var \Concrete\Core\Utility\Service\Number */
    protected $numberHelper;

    public function view()
    {
        $this->numberHelper = $this->app->make('helper/number');

        return $this->app->make(ResponseFactory::class)->json([
            'data' => $this->getFiles(),
        ]);
    }

    private function getFiles()
    {
        $records = [];

        /** @var ProcessedFilesRepository $repository */
        $repository = $this->app->make(ProcessedFilesRepository::class);
        foreach ($repository->findAll() as $processedFile) {
            $record = [];
            if ($processedFile->getOriginalFileId()) {
                $file = File::getByID($processedFile->getOriginalFileId());
                if (!$file || $file->isError()) {
                    continue;
                }

                $record['path'] = $file->getVersion()->getRelativePath();
                $record['is_original'] = true;
            } else {
                $record['path'] = REL_DIR_FILES_UPLOADED_STANDARD.$processedFile->getPath();
                $record['is_original'] = false;
            }

            $sizeKb = $this->size($processedFile->getFileSizeReduction());

            $record['id'] = $processedFile->getId();
            $record['size_reduction'] = $this->size($processedFile->getFileSizeReduction());
            $record['size_reduction_human'] = $sizeKb > 1024 ? $this->numberHelper->formatSize($processedFile->getFileSizeReduction()) : '';
            $record['skip_reason'] = $processedFile->getSkipReason();

            $records[] = $record;
        }

        return $records;
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
