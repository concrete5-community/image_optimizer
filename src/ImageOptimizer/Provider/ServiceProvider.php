<?php

namespace A3020\ImageOptimizer\Provider;

use A3020\ImageOptimizer\Console\Command\OptimizeImagesCommand;
use A3020\ImageOptimizer\Listener\ThumbnailDelete\RemoveProcessedFile;
use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Routing\RouterInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ServiceProvider implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    private $dispatcher;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function register()
    {
        $this->dispatcher->addListener('on_cache_flush', function () {
            $db = $this->app->make('database')->connection();

            $config = $this->app->make(Repository::class);

            // We have to clear the cache, otherwise it won't reload the most recent value from the config store
            $config->clearCache();

            if (!$config->has('concrete.cache.clear.thumbnails') || $config->get('concrete.cache.clear.thumbnails')) {
                // Remove all records if the thumbnail setting doesn't exist or if we decide to also clear thumbs
                $db->executeQuery("
                    DELETE FROM ImageOptimizerProcessedFiles WHERE path LIKE '/cache%'
                ");
            }
        });

        $this->dispatcher->addListener('on_thumbnail_delete', function($event) {
            /** @var RemoveProcessedFile $listener */
            $listener = $this->app->make(RemoveProcessedFile::class);
            $listener->handle($event);
        });

        /** @var RouterInterface $router */
        $router = $this->app->make(RouterInterface::class);

        $router->registerMultiple([
            '/ccm/system/image_optimizer/files' => [
                '\A3020\ImageOptimizer\Ajax\Files::view',
            ],
            '/ccm/system/image_optimizer/reset' => [
                '\A3020\ImageOptimizer\Ajax\Reset::view',
            ],
            '/ccm/system/image_optimizer/foundation/dismiss_review' => [
                '\A3020\ImageOptimizer\Ajax\Foundation\DismissReview::view',
            ],
        ]);

        if ($this->app->isRunThroughCommandLineInterface()) {
            $console = $this->app->make('console');
            $console->add(new OptimizeImagesCommand());
        }
    }
}
