<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/** @var Controller $controller */
$controller = controller();

/** @var array $attributes */
$attributes = (array)$controller->getData('attributes');

/** @var Survey $survey */
$survey = $controller->getData('survey');

/** @var string $fieldsHtml */
$fieldsHtml = (string)$controller->getData('fieldsHtml');

$htmlOptions = [];
if (!empty($attributes) && !empty($attributes['target']) && in_array($attributes['target'], ['_blank'])) {
    $htmlOptions['target'] = $attributes['target'];
}
?>

<div class="row">
    <div class="<?php echo (string)$controller->layout != 'embed' ? 'col-lg-6 col-lg-push-3 col-md-6 col-md-push-3 col-sm-12' : ''; ?>">
        <?php echo CHtml::form('', 'post', $htmlOptions); ?>
        <div class="box box-primary borderless">
            <div class="box-header">
                <h3 class="box-title"><?php echo html_encode((string)$survey->getDisplayName()); ?></h3>
            </div>

            <div class="box-body">
                <?php if (!empty($survey->description)) {?>
                    <div class="callout">
                        <?php echo html_purify((string)$survey->description); ?>
                    </div>
                <?php } ?>
                <div class="fields-list">
                    <?php echo $fieldsHtml; ?>
                </div>
            </div>

            <div class="box-footer">
                <div class="pull-right">
                    <?php echo CHtml::submitButton(t('surveys', 'Submit'), ['class' => 'btn btn-primary btn-flat']); ?>
                </div>
                <div class="clearfix"> </div>
            </div>
        </div>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>
