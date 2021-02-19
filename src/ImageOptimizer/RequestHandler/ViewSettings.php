<?php

namespace A3020\ImageOptimizer\RequestHandler;

use A3020\ImageOptimizer\Statistics\Month;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\Connection\Connection;

class ViewSettings
{
    /**
     * @var Repository
     */
    public $config;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Month
     */
    private $monthStatistics;

    public function __construct(Repository $config, Connection $connection, Month $monthStatistics)
    {
        $this->config = $config;
        $this->connection = $connection;
        $this->monthStatistics = $monthStatistics;
    }

    /**
     * @return int
     */
    public function getNumberOfProcessedFiles()
    {
        return (int) $this->connection->fetchColumn('
            SELECT COUNT(1) FROM ImageOptimizerProcessedFiles 
        ');
    }

    public function getNumberOfOptimizationsThisMonth()
    {
        return $this->monthStatistics->total();
    }
}
