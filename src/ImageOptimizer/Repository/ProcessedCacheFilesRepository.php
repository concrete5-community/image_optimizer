<?php

namespace A3020\ImageOptimizer\Repository;

use A3020\ImageOptimizer\Entity\ProcessedCacheFile;
use Doctrine\ORM\EntityManager;

final class ProcessedCacheFilesRepository
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $repository;

    public function __construct(EntityManager $em)
    {
        $this->entityManager = $em;
        $this->repository = $this->entityManager->getRepository(ProcessedCacheFile::class);
    }

    /**
     * @return ProcessedCacheFile[]
     */
    public function findAll()
    {
        return $this->repository->findAll();
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
        $record = $this->repository->findOneBy([
            'cacheIdentifier' => $cacheIdentifier,
        ]);

        if (!$record) {
            $record = $this->create($cacheIdentifier);
        }

        return $record;
    }

    public function removeAll()
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete()
            ->from(ProcessedCacheFile::class, 'pcf')
            ->getQuery()
            ->execute();
    }

    public function removeOne($id)
    {
        /** @var ProcessedCacheFile $record */
        $record = $this->repository->find($id);
        if ($record) {
            $this->entityManager->remove($record);
            $this->entityManager->flush();

            return true;
        }

        return false;
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

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $record;
    }

    public function totalFileSize()
    {
        return (float) $this->repository
            ->createQueryBuilder('pcf')
            ->select('SUM(pcf.fileSizeReduction) as fileSize')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
