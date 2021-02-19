<?php

namespace A3020\ImageOptimizer\Queue;

use A3020\ImageOptimizer\Entity\ProcessedFile;
use A3020\ImageOptimizer\Exception\MonthlyLimitReached;
use A3020\ImageOptimizer\Handler\HandlerInterface;
use A3020\ImageOptimizer\MonthlyLimit;
use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Logging\Logger;
use Exception;
use ZendQueue\Message as ZendQueueMessage;

class Process implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    /**
     * @var MonthlyLimit
     */
    private $monthlyLimit;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Repository
     */
    private $config;

    public function __construct(MonthlyLimit $monthlyLimit, Logger $logger, Repository $config)
    {
        $this->monthlyLimit = $monthlyLimit;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @param ZendQueueMessage $msg
     *
     * @return ProcessedFile|null
     */
    public function process(ZendQueueMessage $msg)
    {
        if ($this->monthlyLimit->reached()) {
            return null;
        }

        try {
            $body = json_decode($msg->body, true);

            $handler = $this->makeHandler($body);

            if ((bool)$this->config->get('image_optimizer::settings.enable_log')) {
                $handler->useLogger($this->logger);
            }

            return $handler->process($body);
        } catch (Exception $e) {
            $this->logger->addDebug($e->getMessage() . $e->getFile() . $e->getLine() . $e->getTraceAsString());

            return null;
        }
    }

    /**
     * @param array $body
     *
     * @return HandlerInterface
     */
    private function makeHandler($body)
    {
        // Thumbnail
        if (isset($body['fileId']) && isset($body['fileVersionId']) && isset($body['thumbnailTypeHandle'])) {
            return $this->app->make(\A3020\ImageOptimizer\Handler\Thumbnail::class);
        }

        // Original / normal file
        if (isset($body['fileId'])) {
           return $this->app->make(\A3020\ImageOptimizer\Handler\Original::class);
        }

        // Cache file
        if (isset($body['path'])) {
            return $this->app->make(\A3020\ImageOptimizer\Handler\CacheFile::class);
        }
    }
}
