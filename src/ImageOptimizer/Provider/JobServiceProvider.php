<?php

namespace A3020\ImageOptimizer\Provider;

use A3020\ImageOptimizer\ComposerLoader;
use A3020\ImageOptimizer\Handler\BaseHandler;
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

class JobServiceProvider implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    /**
     * @var Repository
     */
    private $config;

    /**
     * @var ComposerLoader
     */
    private $composerLoader;

    public function __construct(Repository $config, ComposerLoader $composerLoader)
    {
        $this->config = $config;
        $this->composerLoader = $composerLoader;
    }

    public function register()
    {
        $this->composerLoader->load();

        $this->app->bind(OptimizerChain::class, function($app) {
            $chain = (new OptimizerChain());

            if ((bool) $this->config->get('image_optimizer::settings.enable_log')) {
                $chain->useLogger($this->app->make('log'));
            }

            // The `proc_open` and `proc_close` functions are needed to run the optimizers locally
            if (function_exists('proc_open') && function_exists('proc_close')) {
                $chain->addOptimizer(new Jpegoptim([
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
            }

            if ((bool) $this->config->get('image_optimizer::settings.tiny_png.enabled') && !empty($this->config->get('image_optimizer::settings.tiny_png.api_key'))) {
                $chain->addOptimizer(new TinyPng([
                    'api_key' => $this->config->get('image_optimizer::settings.tiny_png.api_key'),
                ]));
            }

            return $chain;
        });
    }
}
