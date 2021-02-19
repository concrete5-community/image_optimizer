<?php

namespace A3020\ImageOptimizer;

use A3020\ImageOptimizer\Statistics\Month;
use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Config\Repository\Repository;

class MonthlyLimit implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    /**
     * @var Repository
     */
    private $config;

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Return true if we've reached a monthly limit of image optimizations.
     *
     * @return bool
     */
    public function reached()
    {
        $max = (int) $this->config->get('image_optimizer.max_optimizations_per_month');

        if (empty($max)) {
            return false;
        }

        if ($this->getNumberOfOptimizationsThisMonth() < $max) {
            return false;
        }

        return true;
    }

    private function getNumberOfOptimizationsThisMonth()
    {
        $statistics = $this->app->make(Month::class);

        return $statistics->total();
    }
}
