<?php

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Url;

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var int $numberOfOptimizationsThisMonth */
/** @var int $maxImageSize */
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
                        title="<?php echo t("If you don't want to optimize the original files, you can disable this option."); ?>"
                >
                    <?php
                    /** @var bool $includeFilemanagerImages*/
                    echo $form->checkbox('includeFilemanagerImages', 1, $includeFilemanagerImages);
                    ?>
                    <?php echo t('Include images from File Manager'); ?>
                </label>
            </div>

            <div class="form-group">
                <label class="control-label launch-tooltip"
                   title="<?php echo t("Thumbnail Types ar often used by galleries. You probably want this setting to be enabled. In case no thumbnails are optimized, make sure you re-run the '%s' Automated Job.", t("Fill thumbnail database table")); ?>"
                >
                    <?php
                    /** @var bool $includeThumbnailImages */
                    echo $form->checkbox('includeThumbnailImages', 1, $includeThumbnailImages);
                    ?>
                    <?php echo t('Include thumbnail images')?><br>
                    <small>
                        <?php
                        /** @var string $thumbnailImageDirectory **/
                        echo $thumbnailImageDirectory;
                        ?>
                    </small>
                </label>
            </div>

            <div class="form-group">
                <label class="control-label launch-tooltip"
                       title="<?php echo t("This will optimize all images found in the cache directory. This also includes cache images that are created via the getThumbnail function. It's recommend to enable this setting."); ?>"
                >
                    <?php
                    /** @var bool $includeCachedImages */
                    echo $form->checkbox('includeCachedImages', 1, $includeCachedImages);
                    ?>
                    <?php echo t('Include images from cache directory')?><br>
                    <small>
                        <?php
                        /** @var string $cacheDirectory **/
                        echo $cacheDirectory;
                        ?>
                    </small>
                </label>
            </div>

            <div class="form-group">
                <label>
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

    <?php
    /** @var int $numberOfProcessedFiles */
    if ($numberOfProcessedFiles > 0) {
        ?>
        <hr>

        <div class="well">
            <p>
                <?php
                echo t2(
                    'The Image Optimizer has processed %d file.',
                    'The Image Optimizer has processed %d files.',
                    $numberOfProcessedFiles,
                    $numberOfProcessedFiles
                ) . ' ';
                echo t('In case you want to rerun the Image Optimizer you can clear the log of processed files.') .' ';
                echo t('This can be done without risk, but it will take some more time to finish the automated job next time.');
                ?>
            </p>

            <form method="post" action="<?php echo $this->action('clear_processed_files'); ?>">
                <?php
                /** @var $token \Concrete\Core\Validation\CSRF\Token */
                echo $token->output('a3020.image_optimizer.clear_processed_files');
                ?>

                <input type="submit"
                   title="<?php echo t('Clearing the log of processed files is without risk.'); ?>"
                   href="<?php echo $this->action('clear_processed_files') ?>"
                   class="btn btn-danger"
                   value="<?php echo t("Clear log of processed files"); ?>" />
            </form>
        </div>
        <?php
    }
    ?>
</div>
