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
 * @since 2.0.30
 */

/** @var Controller $controller */
$controller = controller();

/** @var MenuItem $menuItem */
$menuItem = $controller->getData('menuItem');

/** @var MenuItem[] $menuItems */
$menuItems = $controller->getData('menuItems');

/** @var CActiveForm $form */
$form = $controller->getData('form');

?>

<hr />

<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title"><?php echo IconHelper::make('glyphicon-list') . t('menus', 'Items'); ?></h3>
        </div>
        <div class="pull-right">
            <a href="javascript:;" class="btn btn-primary btn-sm btn-flat btn-add-menu-item"><?php echo IconHelper::make('create'); ?></a>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body">
        <div class="row">
            <div id="menu-items-list">
                <?php if (!empty($menuItems)) { ?>
                    <?php foreach ($menuItems as $index => $menuItemModel) { ?>
                        <div class="col-lg-12 menu-item">
                            <div class="row">
                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <?php echo CHtml::activeLabelEx($menuItemModel, 'label'); ?>
                                        <?php echo CHtml::textField($menuItemModel->getModelName() . '[' . $index . '][label]', $menuItemModel->label, $menuItemModel->fieldDecorator->getHtmlOptions('label')); ?>
                                        <?php echo CHtml::error($menuItemModel, 'label'); ?>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <?php echo CHtml::activeLabelEx($menuItemModel, 'title'); ?>
                                        <?php echo CHtml::textField($menuItemModel->getModelName() . '[' . $index . '][title]', $menuItemModel->title, $menuItemModel->fieldDecorator->getHtmlOptions('title')); ?>
                                        <?php echo CHtml::error($menuItemModel, 'title'); ?>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <?php echo CHtml::activeLabelEx($menuItemModel, 'url'); ?>
                                        <?php echo CHtml::textField($menuItemModel->getModelName() . '[' . $index . '][url]', $menuItemModel->url, $menuItemModel->fieldDecorator->getHtmlOptions('url')); ?>
                                        <?php echo CHtml::error($menuItemModel, 'url'); ?>
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <?php echo CHtml::activeLabelEx($menuItemModel, 'sort_order'); ?>
                                        <?php echo CHtml::dropDownList($menuItemModel->getModelName() . '[' . $index . '][sort_order]', $menuItemModel->sort_order, ArrayHelper::getAssociativeRange(-100, 100), $menuItemModel->fieldDecorator->getHtmlOptions('sort_order')); ?>
                                        <?php echo CHtml::error($menuItemModel, 'sort_order'); ?>
                                    </div>
                                </div>

                                <div class="col-lg-1">
                                    <label>&nbsp;</label>
                                    <div class="clearfix"><!-- --></div>
                                    <a href="javascript:;" class="btn btn-danger btn-flat remove-menu-item"><?php echo IconHelper::make('delete'); ?></a>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<div id="menu-items-template" style="display: none;" data-count="<?php echo !empty($menuItems) ? count($menuItems) : 0; ?>">
    <div class="col-lg-12 menu-item">
        <div class="row">
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo CHtml::activeLabelEx($menuItem, 'label'); ?>
                    <?php echo CHtml::textField($menuItem->getModelName() . '[__#__][label]', $menuItem->label, $menuItem->fieldDecorator->getHtmlOptions('label', ['disabled' => true])); ?>
                    <?php echo CHtml::error($menuItem, 'label'); ?>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo CHtml::activeLabelEx($menuItem, 'title'); ?>
                    <?php echo CHtml::textField($menuItem->getModelName() . '[__#__][title]', $menuItem->title, $menuItem->fieldDecorator->getHtmlOptions('title', ['disabled' => true])); ?>
                    <?php echo CHtml::error($menuItem, 'title'); ?>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo CHtml::activeLabelEx($menuItem, 'url'); ?>
                    <?php echo CHtml::textField($menuItem->getModelName() . '[__#__][url]', $menuItem->url, $menuItem->fieldDecorator->getHtmlOptions('url', ['disabled' => true])); ?>
                    <?php echo CHtml::error($menuItem, 'url'); ?>
                </div>
            </div>
            <div class="col-lg-2">
                <div class="form-group">
                    <?php echo CHtml::activeLabelEx($menuItem, 'sort_order'); ?>
                    <?php echo CHtml::dropDownList($menuItem->getModelName() . '[__#__][sort_order]', $menuItem->sort_order, ArrayHelper::getAssociativeRange(-100, 100), $menuItem->fieldDecorator->getHtmlOptions('sort_order', ['disabled' => true])); ?>
                    <?php echo CHtml::error($menuItem, 'sort_order'); ?>
                </div>
            </div>
            <div class="col-lg-1">
                <label>&nbsp;</label>
                <div class="clearfix"><!-- --></div>
                <a href="javascript:;" class="btn btn-danger btn-flat remove-menu-item"><?php echo IconHelper::make('delete'); ?></a>
            </div>
        </div>
    </div>
</div>
