<?php

use Concrete\Core\Support\Facade\Url;

defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Utility\Service\Number $numberHelper */
/** @var int $totalGained */
?>

<div class="ccm-dashboard-header-buttons btn-group">
    <form method="post" action="<?php echo $this->action('resetAll'); ?>" id="frm-reset-all-files">
        <?php
        /** @var $token \Concrete\Core\Validation\CSRF\Token */
        echo $token->output('a3020.image_optimizer.reset_all');
        ?>
        <input type="submit"
           class="btn btn-danger"
           title="<?php echo t(''); ?>"
           value="<?php echo t("Reset all files")?>" />
        <a
            title="<?php echo t('The Image Optimizer runs as an automated job'); ?>"
            href="<?php echo Url::to('/dashboard/system/optimization/jobs'); ?>" class="btn btn-default"><?php echo t("Go to Automated Jobs")?>
        </a>
    </form>
</div>

<div class="ccm-dashboard-content-inner page-optimized-images">
    <?php
    $this->element('/dashboard/review_notification', [], 'image_optimizer');
    ?>

    <?php
    if ($totalGained) {
        ?>
        <p class="text-muted" style="margin-bottom: 20px;">
            <?php
            echo t('Total file size gained: %s', $numberHelper->formatSize($totalGained));
            ?>
        </p>
        <?php
    }
    ?>

    <table class="table table-striped table-bordered" id="tbl-files">
        <thead>
        <tr>
            <th><?php echo t('Path') ?></th>
            <th>
                <?php echo t('Is original file') ?>
                <i class="text-muted launch-tooltip fa fa-question-circle" data-placement="bottom"
                   title="<?php echo t('Original files are files that have been uploaded to the File Manager.') ?>">
                </i>
            </th>
            <th>
                <?php echo t('Former size'); ?>
                <i class="text-muted launch-tooltip fa fa-question-circle" data-placement="bottom"
                   title="<?php echo t('The current file size + the difference after optimzation.') ?>">
                </i>
            </th>
            <th>
                <?php echo t('Current size'); ?>
                <i class="text-muted launch-tooltip fa fa-question-circle" data-placement="bottom"
                   title="<?php echo t('The file size after optimization.') ?>">
                </i>
            </th>
            <th>
                <?php echo t('Difference'); ?>
                <i class="text-muted launch-tooltip fa fa-question-circle" data-placement="bottom"
                   title="<?php echo t('The difference in size after the images have been optimized. The higher, the better.') ?>">
                </i>
            </th>
            <th>
                <?php echo t('OK'); ?>
                <i class="text-muted launch-tooltip fa fa-question-circle" data-placement="bottom"
                   title="<?php echo t('Any peculiarities?') ?>">
                </i>
            </th>
            <th>
                <?php echo t('Reset'); ?>
                <i class="text-muted launch-tooltip fa fa-question-circle" data-placement="bottom"
                   title="<?php echo t("Image Optimizer marks files it has processed in a log. By clicking the reset button, the log will be cleared for a file. By doing so, Image Optimizer will try to optimize the file again next time. Because files are overwritten, it may be that the image can't be optimized further.") ?>">
                </i>
            </th>
        </tr>
        </thead>
    </table>
</div>

<script>
    $(document).ready(function() {
        var DataTableElement = $('#tbl-files');

        var DataTable = DataTableElement.DataTable({
            ajax: '<?php echo Url::to('/ccm/system/image_optimizer/files') ?>',
            lengthMenu: [[15, 40, 80, -1], [15, 40, 80, '<?php echo t('All') ?>']],
            columns: [
                {
                    data: function(row, type, val) {
                        if (type === 'display') {
                            var html = '';
                            html += '<div class="thumb" style="background-image: url(\''+row.path+'\')">';
                            html += '<a target="_blank" href="' + row.path + '"></a>';
                            html += '</div>';
                            html += '<div class="path"><a target="_blank" href="' + row.path + '">' + row.path + '</a></div>';

                            return html;
                        }

                        return row.path;
                    }
                },
                {
                    data: function(row, type, val) {
                        return row.is_original ? '<?php echo t('Yes'); ?>' : '<?php echo t('No'); ?>';
                    }
                },
                {
                    data: function(row, type, val) {
                        if (type === 'display') {
                            return '<div class="text-muted">'+row.size_original + ' <?php echo t('KB'); ?><br>' +
                                '<small class="text-muted">' + row.size_original_human + '</small></div>';
                        }

                        return row.size_original;
                    }
                },
                {
                    data: function(row, type, val) {
                        if (type === 'display') {
                            return '<div class="text-muted">'+row.size_optimized + ' <?php echo t('KB'); ?><br>' +
                                '<small class="text-muted">' + row.size_optimized_human + '</small></div>';
                        }

                        return row.size_optimized;
                    }
                },
                {
                    data: function(row, type, val) {
                        if (type === 'display') {
                            return row.size_reduction + ' <?php echo t('KB'); ?><br>' +
                                '<small class="text-muted">' + row.size_reduction_human + '</small>';
                        }

                        return row.size_reduction;
                    }
                },
                {
                    data: function(row, type, val) {
                        if (row.skip_reason) {
                            return '<i class="fa fa-info-circle launch-tooltip text-muted" ' +
                                'title="<?php echo t("A bug in concrete5 causes issues with PNG-8 images. TinyPNG might return 8-bit PNG images, therefore this file was skipped."); ?>"></i>';
                        }

                        if (row.size_reduction === 0) {
                            return '<i class="fa fa-info-circle launch-tooltip text-muted" ' +
                                'title="<?php echo t("0KB was optimized. This can happen if you ran the optimizers multiple times, or if no optimizers have been configured."); ?>"></i>';
                        }

                        return '<i class="fa fa-check text-muted"></i>';
                    }
                },
                {
                    data: function(row, type, val) {
                        return '<a data-id="'+row.id+'" data-is-original="'+ (row.is_original ? 1 : 0)+'" href="#" class="reset-one">' +
                            '<i class="fa fa-close"></i>' +
                            '</a>';
                    }
                }
            ],
            order: [[ 4, "desc" ]],
            language: {
                emptyTable: '<?php echo t('No images have been optimized yet. Please go to Automated Jobs to run the Image Optimizer.') ?>'
            },
            drawCallback: function(settings) {
                $(".launch-tooltip").tooltip();
            }
        });

        $('#frm-reset-all-files').on('submit', function() {
            return confirm("<?php echo t("Are you sure you want to reset the status of all files? ".
                "If so, Image Optimizer will try to optimize the images again. " .
                "You probably only want to do this if you didn't have any optimizers configured before.") ?>");
        });

        DataTableElement.on('click', '.reset-one', function() {
            var data = {
                'id': $(this).data('id'),
                'is_original': $(this).data('is-original')
            };

            var row = $(this).closest('tr');
            row.css('opacity', '.5');

            $.post('<?php echo Url::to('/ccm/system/image_optimizer/reset') ?>', data)
                .done(function() {
                    DataTable.row(row).remove();
                })
                .always(function() {
                    DataTable.draw(false);
                });
        });
    });
</script>
