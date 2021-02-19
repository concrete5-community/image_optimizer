<?php

namespace Concrete\Package\ImageOptimizer\Controller\SinglePage\Dashboard\Files\ImageOptimizer;

use A3020\ImageOptimizer\Statistics\Month;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Page\Controller\DashboardPageController;

final class Settings extends DashboardPageController
{
    /** @var Repository $config */
    protected $config;

    public function view()
    {
        $this->config = $this->app->make(Repository::class);

        $this->set('thumbnailImageDirectory', DIR_FILES_UPLOADED_STANDARD.REL_DIR_FILES_THUMBNAILS);
        $this->set('cacheDirectory', $this->config->get('concrete.cache.directory'));
        $this->set('numberOfProcessedFiles', $this->getNumberOfProcessedFiles());
        $this->set('enableLog', (bool) $this->config->get('image_optimizer.enable_log'));
        $this->set('includeFilemanagerImages', (bool) $this->config->get('image_optimizer.include_filemanager_images'));
        $this->set('includeThumbnailImages', (bool) $this->config->get('image_optimizer.include_thumbnail_images', true));
        $this->set('includeCachedImages', (bool) $this->config->get('image_optimizer.include_cached_images'));
        $this->set('batchSize', max((int) $this->config->get('image_optimizer.batch_size'), 1));
        $this->set('maxOptimizationsPerMonth', $this->config->get('image_optimizer.max_optimizations_per_month'));
        $this->set('maxImageSize', $this->config->get('image_optimizer.max_image_size'));

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
        $config->save('image_optimizer.include_thumbnail_images', (bool) $this->post('includeThumbnailImages'));
        $config->save('image_optimizer.include_cached_images', (bool) $this->post('includeCachedImages'));
        $config->save('image_optimizer.batch_size', (int) $this->post('batchSize'));

        $maxOptimizationsPerMonth = (int) $this->post('maxOptimizationsPerMonth');
        if (empty($maxOptimizationsPerMonth)) {
            $maxOptimizationsPerMonth = null;
        }

        $config->save('image_optimizer.max_optimizations_per_month', $maxOptimizationsPerMonth);

        $maxImageSize = (int) $this->post('maxImageSize');
        if (empty($maxImageSize)) {
            $maxImageSize = null;
        }

        $config->save('image_optimizer.max_image_size', $maxImageSize);
        $config->save('image_optimizer.tiny_png.enabled', (bool) $this->post('tinyPngEnabled'));
        $config->save('image_optimizer.tiny_png.api_key', $this->post('tinyPngApiKey'));

        $this->flash('success', t('Your settings have been saved.'));

        return $this->redirect('/dashboard/files/image_optimizer/settings');
    }

    /**
     * @return int
     */
    private function getNumberOfProcessedFiles()
    {
        $db = $this->app->make('database')->connection();
        return (int) $db->fetchColumn('
            SELECT COUNT(1) FROM ImageOptimizerProcessedFiles 
        ');
    }

    private function getNumberOfOptimizationsThisMonth()
    {
        $statistics = $this->app->make(Month::class);

        return $statistics->total();
    }
}
