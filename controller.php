<?php

namespace Concrete\Package\ImageOptimizer;

use Concrete\Core\Attribute\Key\Category;
use Concrete\Core\Attribute\Key\FileKey;
use Concrete\Core\Attribute\Type;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Entity\Attribute\Key\Settings\BooleanSettings;
use Concrete\Core\Job\Job;
use Concrete\Core\Package\Package;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Single;
use Concrete\Core\Support\Facade\Events;
use Concrete\Core\Support\Facade\Package as PackageFacade;

final class Controller extends Package
{
    protected $pkgHandle = 'image_optimizer';
    protected $appVersionRequired = '8.0';
    protected $pkgVersion = '0.9.6';
    protected $pkgAutoloaderRegistries = [
        'src/ImageOptimizer' => '\A3020\ImageOptimizer',
    ];

    public function getPackageName()
    {
        return t('Image Optimizer');
    }

    public function getPackageDescription()
    {
        return t('Optimizes PNGs, JPGs, SVGs, and GIFs.');
    }

    public function on_start()
    {
        Events::addListener('on_cache_flush', function() {
            $db = $this->app->make('database')->connection();
            $db->executeQuery("
                DELETE FROM ImageOptimizerProcessedCacheFiles
            ");
        });
    }

    public function install()
    {
        $pkg = parent::install();
        $this->installEverything($pkg);

        $config = $this->app->make(Repository::class);

        if ($config->get('image_optimizer.batch_size') !== null) {
            // The add-on has been installed before
            // we will not overwrite existing config settings
            return;
        }

        $config->save('image_optimizer.enable_log', false);
        $config->save('image_optimizer.include_filemanager_images', true);
        $config->save('image_optimizer.include_cached_images', true);
        $config->save('image_optimizer.batch_size', 5);
    }

    public function upgrade()
    {
        $pkg = PackageFacade::getByHandle($this->pkgHandle);
        $this->installEverything($pkg);
    }

    public function installEverything($pkg)
    {
        $this->installDashboardPage($pkg);
        $this->installFileAttribute($pkg);
        $this->installJob($pkg);
    }

    private function installDashboardPage($pkg)
    {
        $path = '/dashboard/system/files/image_optimizer';

        /** @var Page $page */
        $page = Page::getByPath($path);
        if ($page && !$page->isError()) {
            return;
        }

        $singlePage = Single::add($path, $pkg);
        $singlePage->update('Image Optimizer');
    }

    private function installFileAttribute($pkg)
    {
        $handle = 'exclude_from_image_optimizer';
        $ak = FileKey::getByHandle($handle);
        if ($ak) {
            return;
        }

        $type = Type::getByHandle('boolean');
        $entity = Category::getByHandle('file');
        $category = $entity->getAttributeKeyCategory();

        $key = [
            'akHandle' => $handle,
            'akName' => t('Exclude from Image Optimizer'),
        ];

        $settings = new BooleanSettings();
        $settings->setIsCheckedByDefault(true);

        /** @var $category \Concrete\Core\Attribute\Category\FileCategory */
        $category->add($type, $key, $settings, $pkg);
    }

    private function installJob($pkg)
    {
        $job = Job::getByHandle('image_optimizer');
        if (!$job) {
            Job::installByPackage('image_optimizer', $pkg);
        }
    }

    public function uninstall()
    {
        parent::uninstall();

        $db = $this->app->make('database')->connection();
        $db->executeQuery("DROP TABLE IF EXISTS ImageOptimizerProcessedFiles");
        $db->executeQuery("DROP TABLE IF EXISTS ImageOptimizerProcessedCacheFiles");
    }
}
