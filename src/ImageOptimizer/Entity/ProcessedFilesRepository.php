<?php

namespace A3020\ImageOptimizer\Entity;

use Doctrine\ORM\EntityManager;

final class ProcessedFilesRepository
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
     * @param int $fileId
     * @param int $versionId
     *
     * @return ProcessedFile
     */
    public function findOrCreate($fileId, $versionId)
    {
        /** @var ProcessedFile $record */
        $record = $this->em->getRepository(ProcessedFile::class)
            ->findOneBy([
                'originalFileId' => $fileId,
                'fileVersionId' => $versionId,
            ]);

        if (!$record) {
            $record = $this->create($fileId, $versionId);
        }

        return $record;
    }

    /**
     * @param int $fileId
     * @param int $versionId
     *
     * @return ProcessedFile
     */
    public function create($fileId, $versionId)
    {
        $record = new ProcessedFile();
        $record->setOriginalFileId($fileId);
        $record->setFileVersionId($versionId);

        $this->em->persist($record);
        $this->em->flush();

        return $record;
    }

    public function totalFileSize()
    {
        return (float) $this->em->getRepository(ProcessedFile::class)
            ->createQueryBuilder('pf')
            ->select('SUM(pf.fileSizeReduction) as fileSize')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
