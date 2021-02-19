<?php

namespace A3020\ImageOptimizer;

use A3020\ImageOptimizer\Repository\ProcessedCacheFilesRepository;
use A3020\ImageOptimizer\Repository\ProcessedFilesRepository;

class Resetter
{
    /**
     * @var ProcessedFilesRepository
     */
    private $originalsRepository;

    /**
     * @var ProcessedCacheFilesRepository
     */
    private $derivalsRepository;

    public function __construct(ProcessedFilesRepository $originalsRepository, ProcessedCacheFilesRepository $derivalsRepository)
    {
        $this->originalsRepository = $originalsRepository;
        $this->derivalsRepository = $derivalsRepository;
    }

    public function resetAll()
    {
        $this->originalsRepository->removeAll();
        $this->derivalsRepository->removeAll();
    }

    /**
     * @param int $id
     * @param bool $isOriginal
     *
     * @return bool
     */
    public function reset($id, $isOriginal)
    {
        if ($isOriginal) {
            return $this->originalsRepository->removeOne($id);
        }

        return $this->derivalsRepository->removeOne($id);
    }
}
