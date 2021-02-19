<?php

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Url;
?>

<p><?php echo t("Congratulations, %s has been installed!", t('Image Optimizer')); ?></p>
<br>

<p>
    <strong><?php echo t('Configure %s:', t('Image Optimizer')); ?></strong>
    <a class="btn btn-default" href="<?php echo Url::to('/dashboard/system/files/image_optimizer') ?>">
        <?php
        echo t('System & Settings / Files / Image Optimizer');
        ?>
    </a>
</p>
<br>

<p class="alert alert-warning">
    <?php
    echo t("Tip: Make sure you install the optimizers on your server or configure the TinyPNG service before running the Automated Job. See also the %sinstallation instructions%s.",
        '<a href="https://www.concrete5.org/marketplace/addons/image-optimizer/installation/" target="_blank">',
        '</a>');
    ?>
</p>
