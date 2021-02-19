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
     * @see https://github.com/concrete5/concrete5/issues/3999
     * TinyPNG might return an 8 bit PNG, however, concrete5 / Imagine
     * can't handle PNG-8 properly as it can loose transparency.
     */
    const SKIP_REASON_PNG_8_BUG = 1;

    /**
     * @ORM\Id @ORM\Column(type="integer", options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $originalFileId;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $fileVersionId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $path;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $processedAt;

    /**
     * The file size before optimization
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $fileSizeOriginal = 0;

    /**
     * The saved / gained file size after optimization
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $fileSizeReduction = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $skipReason;

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
    public function getFileSizeOriginal()
    {
        return (int) $this->fileSizeOriginal;
    }

    /**
     * @return int
     */
    public function getFileSizeNew()
    {
        return max($this->getFileSizeOriginal() - $this->getFileSizeReduction(), 0);
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

    /**
     * @param int $fileSizeOriginal
     */
    public function setFileSizeOriginal($fileSizeOriginal)
    {
        $this->fileSizeOriginal = max((int) $fileSizeOriginal, 0);
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = (string) $path;
    }

    /**
     * @return int|null
     */
    public function getSkipReason()
    {
        return $this->skipReason;
    }

    /**
     * @param int|null $skipReason
     */
    public function setSkipReason($skipReason)
    {
        $this->skipReason = $skipReason;
    }
}
