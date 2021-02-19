<?php

namespace Concrete\Package\ImageOptimizer\Controller\SinglePage\Dashboard\System\Files;

use A3020\ImageOptimizer\Statistics\Month;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Page\Controller\DashboardPageController;

final class ImageOptimizer extends DashboardPageController
{
    /** @var Repository $config */
    protected $config;

    public function view()
    {
        $this->config = $this->app->make(Repository::class);

        $this->set('cacheDirectory', $this->config->get('concrete.cache.directory'));
        $this->set('numberOfProcessedFiles', $this->getNumberOfProcessedFiles());
        $this->set('enableLog', (bool) $this->config->get('image_optimizer.enable_log'));
        $this->set('includeFilemanagerImages', (bool) $this->config->get('image_optimizer.include_filemanager_images'));
        $this->set('includeCachedImages', (bool) $this->config->get('image_optimizer.include_cached_images'));
        $this->set('batchSize', max((int) $this->config->get('image_optimizer.batch_size'), 1));
        $this->set('maxOptimizationsPerMonth', $this->config->get('image_optimizer.max_optimizations_per_month'));

        $this->set('tinyPngEnabled', (bool) $this->config->get('image_optimizer.tiny_png.enabled'));
        $this->set('tinyPngApiKey', $this->config->get('image_optimizer.tiny_png.api_key'));

        $this->set('numberOfOptimizationsThisMonth', $this->getNumberOfOptimizationsThisMonth());
    }

    public function save()
    {
        if (!$this->token->validate('a3020.image_optimizer.settings')) {
            $this->error->add($this->token->getErrorMessage());
            return $this->view();
        }

        /** @var Repository $enableLog */
        $config = $this->app->make(Repository::class);

        $config->save('image_optimizer.enable_log', (bool) $this->post('enableLog'));
        $config->save('image_optimizer.include_filemanager_images', (bool) $this->post('includeFilemanagerImages'));
        $config->save('image_optimizer.include_cached_images', (bool) $this->post('includeCachedImages'));
        $config->save('image_optimizer.batch_size', (int) $this->post('batchSize'));

        $maxOptimizationsPerMonth = (int) $this->post('maxOptimizationsPerMonth');
        if (empty($maxOptimizationsPerMonth)) {
            $maxOptimizationsPerMonth = null;
        }

        $config->save('image_optimizer.max_optimizations_per_month', $maxOptimizationsPerMonth);

        $config->save('image_optimizer.tiny_png.enabled', (bool) $this->post('tinyPngEnabled'));
        $config->save('image_optimizer.tiny_png.api_key', $this->post('tinyPngApiKey'));

        $this->flash('success', t('Your settings have been saved.'));

        return $this->redirect('/dashboard/system/files/image_optimizer');
    }

    public function clear_processed_files()
    {
        if (!$this->token->validate('a3020.image_optimizer.clear_processed_files')) {
            $this->error->add($this->token->getErrorMessage());
            return $this->view();
        }

        $db = $this->app->make('database')->connection();
        $db->executeQuery("TRUNCATE ImageOptimizerProcessedFiles");
        $db->executeQuery("TRUNCATE ImageOptimizerProcessedCacheFiles");

        $this->flash('success', t('The log of processed files has been cleared.'));

        return $this->redirect('/dashboard/system/files/image_optimizer');
    }

    /**
     * @return int
     */
    private function getNumberOfProcessedFiles()
    {
        $db = $this->app->make('database')->connection();
        return (int) $db->fetchColumn('
            SELECT
              (SELECT COUNT(1) FROM ImageOptimizerProcessedFiles) +
              (SELECT COUNT(1) FROM ImageOptimizerProcessedCacheFiles) 
        ');
    }

    private function getNumberOfOptimizationsThisMonth()
    {
        $statistics = $this->app->make(Month::class);

        return $statistics->total();
    }
}
