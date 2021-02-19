<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Url;
?>
<p><?php echo t("Congratulations, %s has been installed!", t('Image Optimizer')); ?></p>
<br>

<p>
    <strong><?php echo t('In order to configure it go to:'); ?></strong>
    <a class="btn btn-default" href="<?php echo Url::to('/dashboard/system/files/image_optimizer') ?>">
        <?php
        echo t('System & Settings / Files / Image Optimizer.');
        ?>
    </a>
</p>
<br>

<p>
    <strong><?php echo t('In order to run it go to:'); ?></strong>
    <a class="btn btn-default" href="<?php echo Url::to('/dashboard/system/optimization/jobs') ?>">
        <?php
        echo t('System & Settings / Optimization / Automated Jobs.');
        ?>
    </a>
</p>
