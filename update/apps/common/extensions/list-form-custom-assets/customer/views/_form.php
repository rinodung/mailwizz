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
 * @since 1.3.4.3
 */

/** @var Controller $controller */
$controller = controller();

/** @var ListFormCustomAsset[] $models */
$models = $controller->getData('models');

/** @var ListFormCustomAsset $model */
$model = $controller->getData('model');

/** @var CActiveForm $form */
$form = $controller->getData('form');


?>
<hr />
<div class="row">
    <div class="col-lg-12">
        <h4><?php echo t('lists', 'Custom assets'); ?> <a href="javascript:;" class="btn btn-primary btn-flat pull-right btn-list-custom-asset-add"><?php echo IconHelper::make('create'); ?></a></h4>
        <div class="clearfix"><!-- --></div>
        <div class="row">
            <div class="list-custom-assets-list">
                <?php foreach ($models as $index => $mdl) { ?>
                    <div class="col-lg-6 list-custom-assets-row" data-start-index="<?php echo $index; ?>">
                        <div class="row">
                            <div class="col-lg-7">
                                <div class="form-group">
                                    <?php echo CHtml::activeLabelEx($mdl, 'asset_url'); ?>
                                    <?php echo CHtml::textField($mdl->getModelName() . '[' . $index . '][asset_url]', $mdl->asset_url, $mdl->fieldDecorator->getHtmlOptions('asset_url')); ?>
                                    <?php echo CHtml::error($mdl, 'asset_url'); ?>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <?php echo CHtml::activeLabelEx($mdl, 'asset_type'); ?>
                                    <?php echo CHtml::dropDownList($mdl->getModelName() . '[' . $index . '][asset_type]', $mdl->asset_type, $mdl->getAssetTypes(), $mdl->fieldDecorator->getHtmlOptions('asset_type')); ?>
                                    <?php echo CHtml::error($mdl, 'asset_type'); ?>
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <div class="pull-left" style="margin-top: 25px;">
                                        <a href="javascript:;" class="btn btn-danger btn-flat btn-list-custom-asset-remove" data-asset-id="<?php echo $mdl->asset_id; ?>" data-message="<?php echo t('lists', 'Are you sure you want to remove this asset? There is no coming back from this after you save the changes.'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                                    </div>
                                    <div class="clearfix"><!-- --></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<div id="list-custom-assets-row-template" style="display: none;">
    <div class="col-lg-6 list-custom-assets-row" data-start-index="{index}">
        <div class="row">
            <div class="col-lg-7">
                <div class="form-group">
                    <?php echo CHtml::activeLabelEx($model, 'asset_url'); ?>
                    <?php echo CHtml::textField($model->getModelName() . '[{index}][asset_url]', $model->asset_url, $model->fieldDecorator->getHtmlOptions('asset_url')); ?>
                    <?php echo CHtml::error($model, 'asset_url'); ?>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo CHtml::activeLabelEx($model, 'asset_type'); ?>
                    <?php echo CHtml::dropDownList($model->getModelName() . '[{index}][asset_type]', $model->asset_type, $model->getAssetTypes(), $model->fieldDecorator->getHtmlOptions('asset_type')); ?>
                    <?php echo CHtml::error($model, 'asset_type'); ?>
                </div>
            </div>
            <div class="col-lg-2">
                <div class="form-group">
                    <div class="pull-left" style="margin-top: 25px;">
                        <a href="javascript:;" class="btn btn-danger btn-flat btn-list-custom-asset-remove" data-asset-id="<?php echo $model->asset_id; ?>" data-message="<?php echo t('lists', 'Are you sure you want to remove this asset? There is no coming back from this after you save the changes.'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
