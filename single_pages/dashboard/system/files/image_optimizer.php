<?php

defined('C5_EXECUTE') or die('Access Denied.');

/** @var $form \Concrete\Core\Form\Service\Form */
?>

<div class="ccm-dashboard-header-buttons btn-group">
    <a
        title="<?php echo t('The Image Optimizer runs as an automated job'); ?>"
        href="<?php echo URL::to('/dashboard/system/optimization/jobs'); ?>" class="btn btn-default"><?php echo t("Go to Automated Jobs")?>
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
                <label>
                    <?php
                    /** @var bool $includeFilemanagerImages*/
                    echo $form->checkbox('includeFilemanagerImages', 1, $includeFilemanagerImages);
                    ?>
                    <?php echo t('Include images from filemanager'); ?>
                </label>
            </div>

            <div class="form-group">
                <label>
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
                <?php
                echo $form->label('batchSize', t('Batch size for automated job'));
                /** @var int $batchSize */
                echo $form->number('batchSize', $batchSize, [
                    'placeholder' => t('Default: 5'),
                    'min' => 1,
                    'style' => 'max-width: 100px',
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

            <div class="form-group">
                <?php
                /** @var bool $tinyPngApiKey */
                echo $form->label('tinyPngApiKey', t('TinyPNG API key'));
                echo $form->text('tinyPngApiKey', $tinyPngApiKey);
                ?>
            </div>
        </fieldset>

        <?php
        echo $form->submit('submit', t('Save'), [
            'class' => 'btn-primary'
        ]);
        ?>
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
                    title="<?php echo t('Removing the log processed files is without risk.'); ?>"
                    href="<?php echo $this->action('clear_processed_files') ?>"
                    class="btn btn-danger"
                    value="<?php echo t("Clear log of processed files"); ?>" />
            </form>
        </div>
        <?php
    }
    ?>
</div>
