<?php

namespace A3020\ImageOptimizer\Console\Command;

use A3020\ImageOptimizer\CliOptimizer;
use A3020\ImageOptimizer\Provider\JobServiceProvider;
use A3020\ImageOptimizer\Queue\Create;
use Concrete\Core\Console\Command;
use Concrete\Core\Support\Facade\Application;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OptimizeImagesCommand extends Command
{
    protected function configure()
    {
        $errExitCode = static::RETURN_CODE_ON_FAILURE;
        $this
            ->setName('image-optimizer:optimize')
            ->setDescription('Optimizes all images.')
            ->setCanRunAsRoot(false)
        ;
        $this->setHelp(<<<EOT
Returns codes:
  0 operation completed successfully
  {$errExitCode} errors occurred
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = Application::getFacadeApplication();

        $provider = $app->make(JobServiceProvider::class);
        $provider->register();

        /** @var CliOptimizer $optimizer **/
        $optimizer = $app->make(CliOptimizer::class);

        $progressBar = new ProgressBar($output, $this->getQueueSize());
        $progressBar->display();

        $bytesSaved = $numImages = 0;
        $optimizer->optimize(function(\A3020\ImageOptimizer\Entity\ProcessedFile $processedFile)
            use ($progressBar, $output, &$numImages, &$bytesSaved) {
            $progressBar->advance();

            $output->writeln(' ' . $processedFile->getComputedPath() . ' '.$processedFile->getFileSizeReduction() .' bytes');
            $bytesSaved += $processedFile->getFileSizeReduction();

            ++$numImages;
        });
        $progressBar->clear();

        $numberHelper = $app->make('helper/number');

        $output->writeln('');
        $output->writeln(sprintf('Number of optimized images: %s', $numImages));
        $output->writeln(sprintf('Size gained: %s (%s)', $bytesSaved, $numberHelper->formatSize($bytesSaved)));
    }

    /**
     * Returns the size of the queue
     *
     * If the queue is empty, we'll start a new one
     *
     * @return int
     */
    private function getQueueSize()
    {
        $app = Application::getFacadeApplication();

        /** @var CliOptimizer $optimizer **/
        $optimizer = $app->make(CliOptimizer::class);

        $queueSize = $optimizer->getSize();
        if ($queueSize !== 0) {
            return $queueSize;
        }

        /** @var Create $queue */
        $queue = $app->make(Create::class);
        $queue->create($optimizer->getQueueObject());

        return $optimizer->getSize();
    }
}
