<?php

namespace A3020\ImageOptimizer\Provider;

use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Config\Repository\Repository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ServiceProvider implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
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
                    TRUNCATE TABLE ImageOptimizerProcessedCacheFiles
                ");
            } else {
                // Keep the thumbnail records
                $db->executeQuery("
                    DELETE FROM ImageOptimizerProcessedCacheFiles WHERE cacheIdentifier NOT LIKE 'thumbnails%' 
                ");
            }
        });
    }
}
