<?php

namespace A3020\ImageOptimizer\Repository;

use A3020\ImageOptimizer\Entity\ProcessedFile;
use Doctrine\ORM\EntityManager;

final class ProcessedFilesRepository
{
    /** @var EntityManager */
    private $entityManager;

    /** @var \Doctrine\ORM\EntityRepository */
    protected $repository;

    public function __construct(EntityManager $em)
    {
        $this->entityManager = $em;
        $this->repository = $this->entityManager->getRepository(ProcessedFile::class);
    }

    /**
     * @return ProcessedFile[]
     */
    public function findAll()
    {
        return $this->repository->findAll();
    }

    /**
     * @param int $fileId
     * @param int $versionId
     *
     * @return ProcessedFile
     */
    public function findOrCreateOriginal($fileId, $versionId)
    {
        /** @var ProcessedFile $record */
        $record = $this->repository->findOneBy([
            'originalFileId' => $fileId,
            'fileVersionId' => $versionId,
        ]);

        if (!$record) {
            $record = $this->createOriginal($fileId, $versionId);
        }

        return $record;
    }

    /**
     * @param string $path
     *
     * @return ProcessedFile
     */
    public function findOrCreateDerived($path)
    {
        /** @var ProcessedFile $record */
        $record = $this->repository->findOneBy([
            'path' => $path,
        ]);

        if (!$record) {
            $record = $this->createDerived($path);
        }

        return $record;
    }

    /**
     * @param int $fileId
     * @param int $versionId
     *
     * @return ProcessedFile
     */
    public function createOriginal($fileId, $versionId)
    {
        $record = new ProcessedFile();
        $record->setOriginalFileId($fileId);
        $record->setFileVersionId($versionId);

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $record;
    }

    /**
     * @param string $path
     *
     * @return ProcessedFile
     */
    public function createDerived($path)
    {
        $record = new ProcessedFile();
        $record->setPath($path);

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $record;
    }


    public function totalFileSize()
    {
        return (float) $this->repository
            ->createQueryBuilder('pf')
            ->select('SUM(pf.fileSizeReduction) as fileSize')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function removeOne($id)
    {
        /** @var ProcessedFile $record */
        $record = $this->repository->find($id);
        if ($record) {
            $this->entityManager->remove($record);
            $this->entityManager->flush();

            return true;
        }

        return false;
    }

    public function removeByPath($path)
    {
        /** @var ProcessedFile $record */
        $record = $this->repository->findOneBy([
            'path' => $path,
        ]);
        if ($record) {
            $this->entityManager->remove($record);
            $this->entityManager->flush();

            return true;
        }

        return false;
    }

    public function removeAll()
    {
        $this->entityManager
            ->getConnection()
            ->executeQuery("TRUNCATE TABLE ImageOptimizerProcessedFiles");
    }
}
