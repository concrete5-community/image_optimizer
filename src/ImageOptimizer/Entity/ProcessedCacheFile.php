<?php

namespace A3020\ImageOptimizer\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *   name="ImageOptimizerProcessedCacheFiles",
 * )
 */
class ProcessedCacheFile
{
    /**
     * @ORM\Id @ORM\Column(type="integer", options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $cacheIdentifier;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $processedAt = null;

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

    /**
     * @return string
     */
    public function getCacheIdentifier()
    {
        return $this->cacheIdentifier;
    }

    /**
     * @param string $cacheIdentifier
     */
    public function setCacheIdentifier($cacheIdentifier)
    {
        $this->cacheIdentifier = (string) $cacheIdentifier;
    }
}
