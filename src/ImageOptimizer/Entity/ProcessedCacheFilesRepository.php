<?php

namespace A3020\ImageOptimizer\Entity;

use Doctrine\ORM\EntityManager;

final class ProcessedCacheFilesRepository
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     *
     * @param string $cacheIdentifier
     *
     * @return ProcessedCacheFile
     */
    public function findOrCreate($cacheIdentifier)
    {
        /** @var ProcessedCacheFile $record */
        $record = $this->em->getRepository(ProcessedCacheFile::class)
            ->findOneBy([
                'cacheIdentifier' => $cacheIdentifier,
            ]);

        if (!$record) {
            $record = $this->create($cacheIdentifier);
        }

        return $record;
    }

    /**
     * @param string $cacheIdentifier
     *
     * @return ProcessedCacheFile
     */
    public function create($cacheIdentifier)
    {
        $record = new ProcessedCacheFile();
        $record->setCacheIdentifier($cacheIdentifier);

        $this->em->persist($record);
        $this->em->flush();

        return $record;
    }
}
