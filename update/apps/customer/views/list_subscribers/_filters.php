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
 * @since 1.3.6.2
 */

/** @var Controller $controller */
$controller = controller();

/** @var Lists $list */
$list = $controller->getData('list');

/** @var bool $getFilterSet */
$getFilterSet = (bool)$controller->getData('getFilterSet');

/** @var array $getFilter */
$getFilter = (array)$controller->getData('getFilter');

/** @var ListSubscriber $subscriber */
$subscriber = $controller->getData('subscriber');

/** @var array $listCampaigns */
$listCampaigns = (array)$controller->getData('listCampaigns');

echo CHtml::form(createUrl($controller->getRoute(), ['list_uid' => $list->list_uid]), 'get', [
    'id'    => 'campaigns-filters-form',
    'style' => 'display:' . (!empty($getFilterSet) ? 'block' : 'none') . ';',
]); ?>
<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title"><span class="glyphicon glyphicon-filter"><!-- --></span> <?php echo t('list_subscribers', 'Campaigns filters'); ?></h3>
        </div>
        <div class="pull-right">
            <?php echo CHtml::submitButton(t('list_subscribers', 'Set filters'), ['name' => 'submit', 'class' => 'btn btn-primary btn-flat']); ?>
            <?php echo CHtml::link(t('list_subscribers', 'Reset filters'), ['list_subscribers/index', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('list_subscribers', 'Reset filters')]); ?>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-lg-4">
                <label><?php echo t('list_subscribers', 'Show only subscribers that'); ?>:</label>
                <?php echo CHtml::dropDownList('filter[campaigns][action]', $getFilter['campaigns']['action'], CMap::mergeArray(['' => ''], $subscriber->getCampaignFilterActions()), ['class' => 'form-control select2', 'style' => 'width: 100%']); ?>
            </div>
            <div class="col-lg-4">
                <label><?php echo t('list_subscribers', 'This campaign'); ?>:</label>
                <?php echo CHtml::dropDownList('filter[campaigns][campaign]', $getFilter['campaigns']['campaign'], CMap::mergeArray(['' => ''], $listCampaigns), ['class' => 'form-control select2', 'style' => 'width: 100%']); ?>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label><?php echo t('list_subscribers', 'In the last'); ?>:</label>
                    <div class="input-group">
		                <?php echo CHtml::numberField('filter[campaigns][atuc]', $getFilter['campaigns']['atuc'], ['class' => 'form-control', 'placeholder' => 2]); ?>
                        <span class="input-group-addon">
                        <?php echo CHtml::dropDownList('filter[campaigns][atu]', $getFilter['campaigns']['atu'], $subscriber->getFilterTimeUnits(), ['class' => 'form-control']); ?>
                    </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php echo CHtml::endForm(); ?>
<div class="clearfix"><!-- --></div>
