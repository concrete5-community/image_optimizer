<?php

namespace A3020\ImageOptimizer\Installer;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Entity\Attribute\Key\Settings\BooleanSettings;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Single;
use Concrete\Core\Attribute\Key\Category;
use Concrete\Core\Attribute\Key\FileKey;
use Concrete\Core\Attribute\Type;
use Concrete\Core\Job\Job;

class Installer
{
    /**
     * @var Repository
     */
    private $config;

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    public function install($pkg)
    {
        $this->configure();
        $this->uninstallOldPage();
        $this->installPages($pkg);
        $this->installFileAttribute($pkg);
        $this->installJob($pkg);
    }

    private function installPages($pkg)
    {
        $pages = [
            '/dashboard/files/image_optimizer' => t('Image Optimizer'),
            '/dashboard/files/image_optimizer/search' => t('Optimized Images'),
            '/dashboard/files/image_optimizer/settings' => t('Settings'),
        ];

        foreach ($pages as $path => $name) {
            /** @var Page $page */
            $page = Page::getByPath($path);
            if (!$page || $page->isError()) {
                $page = Single::add($path, $pkg);
            }

            if ($page->getCollectionName() !== $name) {
                $page->update([
                    'cName' => $name
                ]);
            }
        }
    }

    private function uninstallOldPage()
    {
        /** @var Page $page */
        $page = Page::getByPath('/dashboard/system/files/image_optimizer');
        if ($page && !$page->isError()) {
            $page->delete();
        }
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

    private function configure()
    {
        if ($this->config->get('image_optimizer.batch_size') !== null) {
            // The add-on has been installed before
            // we will not overwrite existing config settings
            return;
        }

        $this->config->save('image_optimizer.enable_log', false);
        $this->config->save('image_optimizer.include_filemanager_images', true);
        $this->config->save('image_optimizer.include_thumbnail_images', true);
        $this->config->save('image_optimizer.include_cached_images', true);
        $this->config->save('image_optimizer.batch_size', 5);
    }
}
