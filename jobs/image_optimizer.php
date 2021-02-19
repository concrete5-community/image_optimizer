<?php

namespace Concrete\Package\ImageOptimizer\Job;

use A3020\ImageOptimizer\Provider\JobServiceProvider;
use A3020\ImageOptimizer\Queue\Create;
use A3020\ImageOptimizer\Queue\Finish;
use A3020\ImageOptimizer\Queue\Process;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Job\QueueableJob;
use Concrete\Core\Support\Facade\Application;

final class ImageOptimizer extends QueueableJob
{
    protected $jQueueBatchSize = 5;

    /** @var \Concrete\Core\Application\Application
     * Not named 'app' on purpose because parent class might change
     */
    private $appInstance;

    public function getJobName()
    {
        return t('Image Optimizer');
    }

    public function getJobDescription()
    {
        return t('Optimizes PNGs, JPGs, SVGs, and GIFs.');
    }

    public function __construct()
    {
        $this->appInstance = Application::getFacadeApplication();

        $config = $this->appInstance->make(Repository::class);
        $this->jQueueBatchSize = (int) $config->get('image_optimizer.batch_size', 5);

        $provider = $this->appInstance->make(JobServiceProvider::class);
        $provider->register();

        parent::__construct();
    }

    /**
     * Start the job by creating a queue.
     *
     * @param \ZendQueue\Queue $q
     */
    public function start(\ZendQueue\Queue $q)
    {
        /** @var Create $queue */
        $queue = $this->appInstance->make(Create::class);
        $queue->create($q);
    }

    /**
     * Process a QueueMessage.
     *
     * @param \ZendQueue\Message $msg
     */
    public function processQueueItem(\ZendQueue\Message $msg)
    {
        /** @var Process $queue */
        $queue = $this->appInstance->make(Process::class);
        $queue->process($msg);
    }

    /**
     * Finish processing a queue.
     *
     * @param \ZendQueue\Queue $q
     *
     * @return mixed
     */
    public function finish(\ZendQueue\Queue $q)
    {
        /** @var Finish $queue */
        $queue = $this->appInstance->make(Finish::class);

        return $queue->finish($q);
    }
}
