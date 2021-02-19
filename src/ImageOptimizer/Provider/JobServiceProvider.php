<?php

namespace A3020\ImageOptimizer\Provider;

use A3020\ImageOptimizer\OptimizerChain;
use A3020\ImageOptimizer\Optimizers\Gifsicle;
use A3020\ImageOptimizer\Optimizers\Jpegoptim;
use A3020\ImageOptimizer\Optimizers\Optipng;
use A3020\ImageOptimizer\Optimizers\Pngquant;
use A3020\ImageOptimizer\Optimizers\Svgo;
use A3020\ImageOptimizer\Optimizers\TinyPng;
use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Package\PackageService;

class JobServiceProvider implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    /** @var Repository */
    private $config;

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    public function register()
    {
        $this->loadDependencies();

        $this->app->bind(OptimizerChain::class, function($app) {
            $chain = (new OptimizerChain())
                ->addOptimizer(new Jpegoptim([
                    '--strip-all',
                    '--all-progressive',
                ]))

                ->addOptimizer(new Pngquant([
                    '--force',
                ]))

                ->addOptimizer(new Optipng([
                    '-i0',
                    '-o2',
                    '-quiet',
                ]))

                ->addOptimizer(new Svgo([
                    '--disable=cleanupIDs',
                ]))

                ->addOptimizer(new Gifsicle([
                    '-b',
                    '-O3',
                ]));

            if ((bool) $this->config->get('image_optimizer.enable_log')) {
                $chain->useLogger($this->app->make('log'));
            }

            if ((bool) $this->config->get('image_optimizer.tiny_png.enabled') && !empty($this->config->get('image_optimizer.tiny_png.api_key'))) {
                $chain->addOptimizer(new TinyPng([
                    'api_key' => $this->config->get('image_optimizer.tiny_png.api_key'),
                ]));
            }

            return $chain;
        });
    }

    private function loadDependencies()
    {
        $packageService = $this->app->make(PackageService::class);
        $pkg = $packageService->getByHandle('image_optimizer');

        require_once $pkg->getPackagePath().'/vendor/autoload.php';
    }
}
