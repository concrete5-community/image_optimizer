<?php

namespace A3020\ImageOptimizer\RequestHandler;

use A3020\ImageOptimizer\ComposerLoader;
use A3020\ImageOptimizer\Statistics\Month;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\Connection\Connection;
use Exception;

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
     * @var ComposerLoader
     */
    private $composerLoader;

    public function __construct(Repository $config, Connection $connection, ComposerLoader $composerLoader)
    {
        $this->config = $config;
        $this->connection = $connection;
        $this->composerLoader = $composerLoader;
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

    /**
     * @return int|null
     */
    public function getTinyPngNumberOfCompressions()
    {
        if ((bool) $this->config->get('image_optimizer::settings.tiny_png.enabled')
            && $this->config->get('image_optimizer::settings.tiny_png.api_key')
        ) {
            try {
                // The composer dependencies need to be loaded
                // in order to call the TinyPNG API.
                $this->composerLoader->load();

                \Tinify\setKey($this->config->get('image_optimizer::settings.tiny_png.api_key'));
                \Tinify\validate();

                return \Tinify\compressionCount();
            } catch (Exception $e) {}
        }

        return null;
    }
}
