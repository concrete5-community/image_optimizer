<?php

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Url;

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var int $numberOfOptimizationsThisMonth */
/** @var int $maxImageSize */
/** @var int|null $tinyPngNumberOfCompressions */
?>

<div class="ccm-dashboard-header-buttons btn-group">
    <a
        title="<?php echo t('The Image Optimizer runs as an automated job'); ?>"
        href="<?php echo Url::to('/dashboard/system/optimization/jobs'); ?>" class="btn btn-default"><?php echo t("Go to Automated Jobs")?>
    </a>
</div>

<div class="ccm-dashboard-content-inner">
    <form method="post" action="<?php echo $this->action('save'); ?>">
        <?php
        /** @var $token \Concrete\Core\Validation\CSRF\Token */
        echo $token->output('a3020.image_optimizer.settings');
        ?>

        <fieldset>
            <legend><?php echo t('General'); ?></legend>

            <div class="form-group">
                <label class="control-label launch-tooltip"
                        title="<?php echo t("These are the files the user once uploaded to the File Manager."); ?>"
                >
                    <?php
                    /** @var bool $includeFilemanagerImages*/
                    echo $form->checkbox('includeFilemanagerImages', 1, $includeFilemanagerImages);
                    ?>
                    <?php echo t('Optimize original files'); ?>
                </label>
            </div>

            <div class="form-group">
                <label class="control-label launch-tooltip"
                   title="<?php echo t("Thumbnail Types are often used in galleries. The images are scaled versions of the original images. You probably want this setting to be enabled. In case no thumbnails are optimized, make sure you re-run the '%s' Automated Job.", t("Fill thumbnail database table")); ?>"
                >
                    <?php
                    /** @var bool $includeThumbnailImages */
                    echo $form->checkbox('includeThumbnailImages', 1, $includeThumbnailImages);
                    ?>
                    <?php echo t('Optimize thumbnail images')?><br>
                </label><br>
                <small class="text-muted">
                    <?php
                    /** @var string $thumbnailImageDirectory **/
                    echo $thumbnailImageDirectory;
                    ?>
                </small>
            </div>

            <div class="form-group">
                <label class="control-label launch-tooltip"
                       title="<?php echo t("This will optimize all images found in the cache directory. This also includes cache images that are created via the getThumbnail function. It's recommend to enable this setting."); ?>"
                >
                    <?php
                    /** @var bool $includeCachedImages */
                    echo $form->checkbox('includeCachedImages', 1, $includeCachedImages);
                    ?>
                    <?php echo t('Optimize images from cache directory')?><br>
                </label><br>
                <small class="text-muted">
                    <?php
                    /** @var string $cacheDirectory **/
                    echo $cacheDirectory;
                    ?>
                </small>
            </div>

            <div class="form-group">
                <label class="control-label launch-tooltip"
                       title="<?php echo t("Enable verbose logging. Only use this for debugging purposes."); ?>"
                >
                    <?php
                    /** @var bool $enableLog */
                    echo $form->checkbox('enableLog', 1, $enableLog);
                    ?>
                    <?php
                    echo t('Write output to concrete5 log');
                    ?>
                </label>
            </div>

            <div class="form-group">
                <label class="control-label launch-tooltip"
                   title="<?php echo t("If you run into time-out issues, you may want to decrease the batch size. (how many images are processed in a single request)") ?>"
                   for="batchSize"
                >
                    <?php
                    echo t('Batch size for automated job');
                    ?>
                </label>

                <?php
                /** @var int $batchSize */
                echo $form->number('batchSize', $batchSize, [
                    'placeholder' => t('Default: %s', 5),
                    'min' => 1,
                    'style' => 'max-width: 350px',
                ]);
                ?>
            </div>

            <div class="form-group">
                <label class="control-label launch-tooltip"
                       title="<?php echo t("This is interesting if you use TinyPNG. %d optimizations per month are for free!", 500) ?>"
                       for="maxOptimizationsPerMonth"
                >
                    <?php
                    echo $form->label('maxOptimizationsPerMonth', t('Maximum number of optimizations per month'));
                    ?>
                </label>

                <?php
                /** @var int|null $maxOptimizationsPerMonth */
                echo $form->number('maxOptimizationsPerMonth', $maxOptimizationsPerMonth, [
                    'placeholder' => t('Leave empty to not set a maximum'),
                    'min' => 0,
                    'style'=> 'max-width: 350px',
                ]);

                if ($numberOfOptimizationsThisMonth) {
                    echo '<small style="color: #777; font-style: italic; margin-top: 3px;">' . t('Optimizations performed this month: %s.', $numberOfOptimizationsThisMonth) . '</small>';
                }
                ?>
            </div>

            <div class="form-group" style="margin-bottom: 0">
                <label class="control-label launch-tooltip"
                       title="<?php echo t("Set a maximum here if your server can't handle the big images you are trying to optimize") ?>"
                       for="maxImageSize"
                >
                    <?php
                    echo t("Don't optimize images bigger than ... KB");
                    ?>
                </label>

                <?php
                /** @var int|null $maxImageSize */
                echo $form->number('maxImageSize', $maxImageSize, [
                    'placeholder' => t('Leave empty to not set a maximum'),
                    'min' => 1,
                    'style'=> 'max-width: 350px',
                ]);
                ?>
            </div>
        </fieldset>

        <fieldset>
            <legend>TinyPNG</legend>

            <p>
                <?php
                echo t('TinyPNG is a cloud service that can optimize PNG and JPEG images. You can register an account '.
                    'on <a href="%s" target="_blank">https://tinypng.com</a> to obtain an API-key.', 'https://tinypng.com');
                ?>
            </p>
            <br>

            <div class="form-group">
                <label>
                    <?php
                    /** @var bool $tinyPngEnabled */
                    echo $form->checkbox('tinyPngEnabled', 1, $tinyPngEnabled);
                    ?>
                    <?php
                    echo t('Enable TinyPNG');
                    ?>
                </label>

                <?php
                if ($tinyPngNumberOfCompressions !== null) {
                    echo '<small class="help-block">';

                    echo t('Number of compressions this month: %s.', $tinyPngNumberOfCompressions);

                    echo '</small>';
                }
                ?>
            </div>

            <div class="form-group" style="margin-bottom: 0">
                <?php
                /** @var bool $tinyPngApiKey */
                echo $form->label('tinyPngApiKey', t('TinyPNG API key'));
                echo $form->text('tinyPngApiKey', $tinyPngApiKey);
                ?>
            </div>
        </fieldset>

        <div class="ccm-dashboard-form-actions-wrapper">
            <div class="ccm-dashboard-form-actions">
                <?php
                echo $form->submit('submit', t('Save'), [
                    'class' => 'btn-primary pull-right',
                ]);
                ?>
            </div>
        </div>
    </form>
</div>
