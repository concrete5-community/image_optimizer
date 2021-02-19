<?php

namespace Concrete\Package\ImageOptimizer\Controller\SinglePage\Dashboard\Files\ImageOptimizer;

use A3020\ImageOptimizer\Resetter;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Routing\Redirect;

final class Search extends DashboardPageController
{
    /** @var Repository $config */
    protected $config;

    public function on_before_render()
    {
        parent::on_before_render();

        $al = AssetList::getInstance();

        $al->register('javascript', 'image_optimizer/datatables', 'js/datatables.min.js', [], 'image_optimizer');
        $this->requireAsset('javascript', 'image_optimizer/datatables');

        $al->register('css', 'gdpr/image_optimizer', 'css/datatables.css', [], 'image_optimizer');
        $this->requireAsset('css', 'image_optimizer/datatables');
    }

    public function view()
    {
        /** @see \A3020\ImageOptimizer\Ajax\Files */
    }

    public function resetAll()
    {
        if (!$this->token->validate('a3020.image_optimizer.reset_all')) {
            $this->flash('error', $this->token->getErrorMessage());

            return Redirect::to('/dashboard/files/image_optimizer/search');
        }

        /** @var Resetter $resetter */
        $resetter = $this->app->make(Resetter::class);
        $resetter->resetAll();

        $this->flash('success', t('All images have been reset.'));

        return Redirect::to('/dashboard/files/image_optimizer/search');
    }
}
