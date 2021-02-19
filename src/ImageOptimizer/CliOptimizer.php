<?php

namespace A3020\ImageOptimizer;

use A3020\ImageOptimizer\Queue\Process;
use Concrete\Core\Application\Application;
use Concrete\Core\Foundation\Queue\QueueService;

class CliOptimizer
{
    /**
     * @var \Concrete\Core\Application\Application
     */
    protected $app;

    /**
     * @var QueueService
     */
    protected $queueService;

    /**
     * @var Process
     */
    private $processor;

    public function __construct(Application $app, QueueService $queueService, Process $processor)
    {
        $this->app = $app;
        $this->queueService = $queueService;
        $this->processor = $processor;
    }

    /**
     * Optimize an image and report back to the CLI progress bar
     *
     * @param callable|null $pulse Every time an image is optimized, we report back to the command
     */
    public function optimize(callable $pulse = null)
    {
        $queueObject = $this->getQueueObject();

        while(true) {
            $message = $queueObject->receive()->current();

            if (!$message) {
                break;
            }

            // Process one image, then delete it from the queue
            $processedFile = $this->processor->process($message);
            $queueObject->deleteMessage($message);

            // Can be null e.g. if monthly limit has been reached
            if (!$processedFile) {
                continue;
            }

            // Report back to CLI
            $pulse($processedFile);
        }
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return (int) $this->getQueueObject()->count();
    }

    /**
     * @return \ZendQueue\Queue
     */
    public function getQueueObject()
    {
        // It's basically 'job_' + pkg handle
        return $this->queueService->get('job_image_optimizer');
    }
}
