<?php

namespace A3020\ImageOptimizer\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *   name="ImageOptimizerProcessedFiles",
 * )
 */
class ProcessedFile
{
    /**
     * @ORM\Id @ORM\Column(type="integer", options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $originalFileId;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $fileVersionId;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $processedAt;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $fileSizeReduction = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return (int) $this->id;
    }

    /**
     * @return integer
     */
    public function getOriginalFileId()
    {
        return (int) $this->originalFileId;
    }

    /**
     * @param integer $originalFileId
     */
    public function setOriginalFileId($originalFileId)
    {
        $this->originalFileId = (int) $originalFileId;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getProcessedAt()
    {
        return $this->processedAt;
    }

    /**
     * @param \DateTimeImmutable $processedAt
     */
    public function setProcessedAt($processedAt)
    {
        $this->processedAt = $processedAt;
    }

    /**
     * @return int
     */
    public function getFileVersionId()
    {
        return (int) $this->fileVersionId;
    }

    /**
     * @param int $fileVersionId
     */
    public function setFileVersionId($fileVersionId)
    {
        $this->fileVersionId = (int) $fileVersionId;
    }

    /**
     * @return bool
     */
    public function isProcessed()
    {
        return (bool) $this->processedAt;
    }

    /**
     * @return int
     */
    public function getFileSizeReduction()
    {
        return (int) $this->fileSizeReduction;
    }

    /**
     * @param int $fileSizeReduction
     */
    public function setFileSizeReduction($fileSizeReduction)
    {
        $this->fileSizeReduction = max((int) $fileSizeReduction, 0);
    }
}
