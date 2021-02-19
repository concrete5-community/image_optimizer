<?php

namespace A3020\ImageOptimizer;

use A3020\ImageOptimizer\Optimizers\Svgo;
use A3020\ImageOptimizer\Optimizers\Optipng;
use A3020\ImageOptimizer\Optimizers\Gifsicle;
use A3020\ImageOptimizer\Optimizers\Pngquant;
use A3020\ImageOptimizer\Optimizers\Jpegoptim;

class OptimizerChainFactory
{
    /**
     * @return OptimizerChain
     */
    public static function create()
    {
        return (new OptimizerChain())
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
    }
}
