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
 * @since 1.0
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var Lists $list */
$list = $controller->getData('list');

/** @var Campaign $campaign */
$campaign = $controller->getData('campaign');

/** @var bool $forceOptIn */
$forceOptIn = (bool)$controller->getData('forceOptIn');

/** @var bool $forceOptOut */
$forceOptOut = (bool)$controller->getData('forceOptOut');

/** @var ListDefault $listDefault */
$listDefault = $controller->getData('listDefault');

/** @var ListCustomerNotification $listCustomerNotification */
$listCustomerNotification = $controller->getData('listCustomerNotification');

/** @var ListSubscriberAction $listSubscriberAction */
$listSubscriberAction = $controller->getData('listSubscriberAction');

/** @var array $selectedSubscriberActions */
$selectedSubscriberActions = (array)$controller->getData('selectedSubscriberActions');

/** @var array $subscriberActionLists */
$subscriberActionLists = (array)$controller->getData('subscriberActionLists');

/** @var ListCompany $listCompany */
$listCompany = $controller->getData('listCompany');

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->add('renderContent', false)}
 * in order to stop rendering the default content.
 * @since 1.3.3.1
 */
hooks()->doAction('before_view_file_content', $viewCollection = new CAttributeCollection([
    'controller'    => $controller,
    'renderContent' => true,
]));

// and render if allowed
if ($viewCollection->itemAt('renderContent')) { ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <?php if (!$list->getIsNewRecord()) { ?>
                <div class="pull-left">
                    <?php $controller->widget('customer.components.web.widgets.MailListSubNavWidget', [
                        'list' => $list,
                    ]); ?>
                </div>
            <?php } ?>
        </div>
        <?php
        /**
         * This hook gives a chance to prepend content before the active form or to replace the default active form entirely.
         * Please note that from inside the action callback you can access all the controller view variables
         * via {@CAttributeCollection $collection->controller->getData()}
         * In case the form is replaced, make sure to set {@CAttributeCollection $collection->add('renderForm', false)}
         * in order to stop rendering the default content.
         * @since 1.3.3.1
         */
        hooks()->doAction('before_active_form', $collection = new CAttributeCollection([
            'controller'    => $controller,
            'renderForm'    => true,
        ]));

        // and render if allowed
        if ($collection->itemAt('renderForm')) {
            /** @var CActiveForm $form */
            $form = $controller->beginWidget('CActiveForm'); ?>
            <div class="box box-primary borderless">
                <div class="box-header">
                    <div class="pull-left">
                        <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                            ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-list-alt') . html_encode((string)$pageHeading) . '</h3>')
                            ->render(); ?>
                    </div>
                    <div class="pull-right">
                        <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                            ->addIf(CHtml::link(IconHelper::make('create') . t('app', 'Create new'), ['lists/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]), !$list->getIsNewRecord())
                            ->add(CHtml::link(IconHelper::make('cancel') . t('app', 'Cancel'), ['lists/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]))
                            ->render(); ?>
                    </div>
                    <div class="clearfix"><!-- --></div>
                </div>
                <div class="box-body">
                    <?php
                    /**
                     * This hook gives a chance to prepend content before the active form fields.
                     * Please note that from inside the action callback you can access all the controller view variables
                     * via {@CAttributeCollection $collection->controller->getData()}
                     *
                     * @since 1.3.3.1
                     */
                    hooks()->doAction('before_active_form_fields', new CAttributeCollection([
                        'controller'    => $controller,
                        'form'          => $form,
                    ])); ?>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="box box-primary borderless">
                                <div class="box-header">
                                    <h3 class="box-title"><?php echo t('lists', 'General data'); ?></h3>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($list, 'name'); ?>
                                                <?php echo $form->textField($list, 'name', $list->fieldDecorator->getHtmlOptions('name')); ?>
                                                <?php echo $form->error($list, 'name'); ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($list, 'display_name'); ?>
                                                <?php echo $form->textField($list, 'display_name', $list->fieldDecorator->getHtmlOptions('display_name')); ?>
                                                <?php echo $form->error($list, 'display_name'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($list, 'description'); ?>
                                                <?php echo $form->textField($list, 'description', $list->fieldDecorator->getHtmlOptions('description')); ?>
                                                <?php echo $form->error($list, 'description'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($list, 'opt_in'); ?>
                                                <?php echo $form->dropDownList($list, 'opt_in', $list->getOptInArray(), $list->fieldDecorator->getHtmlOptions('opt_in', [
                                                    $forceOptIn ? 'disabled' : 'data-disabled' => $forceOptIn ? 'disabled' : 'false',
                                                ])); ?>
                                                <?php echo $form->error($list, 'opt_in'); ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <?php echo $form->labelEx($list, 'opt_out'); ?>
                                            <?php echo $form->dropDownList($list, 'opt_out', $list->getOptOutArray(), $list->fieldDecorator->getHtmlOptions('opt_out', [
                                                $forceOptOut ? 'disabled' : 'data-disabled' => $forceOptOut ? 'disabled' : 'false',
                                            ])); ?>
                                            <?php echo $form->error($list, 'opt_out'); ?>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($list, 'welcome_email'); ?>
                                                <?php echo $form->dropDownList($list, 'welcome_email', $list->getYesNoOptions(), $list->fieldDecorator->getHtmlOptions('welcome_email')); ?>
                                                <?php echo $form->error($list, 'welcome_email'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($list, 'subscriber_404_redirect'); ?>
                                                <?php echo $form->textField($list, 'subscriber_404_redirect', $list->fieldDecorator->getHtmlOptions('subscriber_404_redirect')); ?>
                                                <?php echo $form->error($list, 'subscriber_404_redirect'); ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($list, 'subscriber_exists_redirect'); ?>
                                                <?php echo $form->textField($list, 'subscriber_exists_redirect', $list->fieldDecorator->getHtmlOptions('subscriber_exists_redirect')); ?>
                                                <?php echo $form->error($list, 'subscriber_exists_redirect'); ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($list, 'subscriber_require_approval'); ?>
                                                <?php echo $form->dropDownList($list, 'subscriber_require_approval', $list->getYesNoOptions(), $list->fieldDecorator->getHtmlOptions('subscriber_require_approval')); ?>
                                                <?php echo $form->error($list, 'subscriber_require_approval'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="box box-primary borderless">
                                <div class="box-header">
                                    <h3 class="box-title"><?php echo t('lists', 'Defaults'); ?></h3>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($listDefault, 'from_name'); ?>
                                                <?php echo $form->textField($listDefault, 'from_name', $listDefault->fieldDecorator->getHtmlOptions('from_name')); ?>
                                                <?php echo $form->error($listDefault, 'from_name'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($listDefault, 'from_email'); ?>
                                                <?php echo $form->emailField($listDefault, 'from_email', $listDefault->fieldDecorator->getHtmlOptions('from_email')); ?>
                                                <?php echo $form->error($listDefault, 'from_email'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <div>
                                                    <?php echo $form->labelEx($listDefault, 'reply_to'); ?>
                                                </div>
                                                <?php echo $form->emailField($listDefault, 'reply_to', $listDefault->fieldDecorator->getHtmlOptions('reply_to')); ?>
                                                <?php echo $form->error($listDefault, 'reply_to'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($listDefault, 'subject'); ?>
                                                <?php echo $form->textField($listDefault, 'subject', $listDefault->fieldDecorator->getHtmlOptions('subject')); ?>
                                                <?php echo $form->error($listDefault, 'subject'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr />
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="box box-primary borderless">
                                <div class="box-header">
                                    <h3 class="box-title"><?php echo t('lists', 'Notifications'); ?></h3>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <?php echo $form->labelEx($listCustomerNotification, 'subscribe'); ?>
                                                        <?php echo $form->dropDownList($listCustomerNotification, 'subscribe', $listCustomerNotification->getYesNoDropdownOptions(), $listCustomerNotification->fieldDecorator->getHtmlOptions('subscribe')); ?>
                                                        <?php echo $form->error($listCustomerNotification, 'subscribe'); ?>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <?php echo $form->labelEx($listCustomerNotification, 'unsubscribe'); ?>
                                                        <?php echo $form->dropDownList($listCustomerNotification, 'unsubscribe', $listCustomerNotification->getYesNoDropdownOptions(), $listCustomerNotification->fieldDecorator->getHtmlOptions('unsubscribe')); ?>
                                                        <?php echo $form->error($listCustomerNotification, 'unsubscribe'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <?php echo $form->labelEx($listCustomerNotification, 'subscribe_to'); ?>
                                                        <?php echo $form->textField($listCustomerNotification, 'subscribe_to', $listCustomerNotification->fieldDecorator->getHtmlOptions('subscribe_to')); ?>
                                                        <?php echo $form->error($listCustomerNotification, 'subscribe_to'); ?>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <?php echo $form->labelEx($listCustomerNotification, 'unsubscribe_to'); ?>
                                                        <?php echo $form->textField($listCustomerNotification, 'unsubscribe_to', $listCustomerNotification->fieldDecorator->getHtmlOptions('unsubscribe_to')); ?>
                                                        <?php echo $form->error($listCustomerNotification, 'unsubscribe_to'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr />
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="box box-primary borderless">
                                <div class="box-header">
                                    <h3 class="box-title"><?php echo t('lists', 'Subscriber actions'); ?></h3>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <ul class="nav nav-tabs">
                                                <li class="active">
                                                    <a href="#tab-subscriber-action-when-subscribe" data-toggle="tab">
                                                        <?php echo t('lists', 'Actions when subscribe'); ?>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="#tab-subscriber-action-when-unsubscribe" data-toggle="tab">
                                                        <?php echo t('lists', 'Actions when unsubscribe'); ?>
                                                    </a>
                                                </li>
                                            </ul>
                                            <div class="tab-content">
                                                <div class="tab-pane active" id="tab-subscriber-action-when-subscribe">
                                                    <div class="callout callout-info" style="margin-bottom: 5px; margin-top: 5px;">
                                                        <?php echo t('lists', 'When a subscriber will subscribe into this list, if he exists in any of the lists below, unsubscribe him from them. Please note that the unsubscribe from the lists below is silent, no email is sent to the subscriber.'); ?>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div class="form-group">
                                                                <?php hooks()->doAction('list_subscriber_actions_subscribe_action_before_html_display', $list); ?>
                                                                <div class="list-subscriber-actions-scrollbox">
                                                                    <ul class="list-group">
                                                                        <?php echo CHtml::checkBoxList($listSubscriberAction->getModelName() . '[' . ListSubscriberAction::ACTION_SUBSCRIBE . '][]', $selectedSubscriberActions[ListSubscriberAction::ACTION_SUBSCRIBE], $subscriberActionLists, $listSubscriberAction->fieldDecorator->getHtmlOptions('target_list_id', [
                                                                            'class'        => '',
                                                                            'template'     => '<li class="list-group-item">{beginLabel}{input} <span>{labelTitle}</span> {endLabel}</li>',
                                                                            'container'    => '',
                                                                            'separator'    => '',
                                                                            'labelOptions' => ['style' => 'margin-right: 10px;'],
                                                                        ])); ?>
                                                                    </ul>
                                                                </div>
                                                                <?php hooks()->doAction('list_subscriber_actions_subscribe_action_after_html_display', $list); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane" id="tab-subscriber-action-when-unsubscribe">
                                                    <div class="callout callout-info" style="margin-bottom: 5px; margin-top: 5px;">
                                                        <?php echo t('lists', 'When a subscriber will unsubscribe from this list, if he exists in any of the lists below, unsubscribe him from them too. Please note that the unsubscribe from the lists below is silent, no email is sent to the subscriber.'); ?>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div class="form-group">
                                                                <?php hooks()->doAction('list_subscriber_actions_unsubscribe_action_before_html_display', $list); ?>
                                                                <div class="list-subscriber-actions-scrollbox">
                                                                    <ul class="list-group">
                                                                        <?php echo CHtml::checkBoxList($listSubscriberAction->getModelName() . '[' . ListSubscriberAction::ACTION_UNSUBSCRIBE . '][]', $selectedSubscriberActions[ListSubscriberAction::ACTION_UNSUBSCRIBE], $subscriberActionLists, $listSubscriberAction->fieldDecorator->getHtmlOptions('target_list_id', [
                                                                            'class'        => '',
                                                                            'template'     => '<li class="list-group-item">{beginLabel}{input} <span>{labelTitle}</span> {endLabel}</li>',
                                                                            'container'    => '',
                                                                            'separator'    => '',
                                                                            'labelOptions' => ['style' => 'margin-right: 10px;'],
                                                                        ])); ?>
                                                                    </ul>
                                                                </div>
                                                                <?php hooks()->doAction('list_subscriber_actions_unsubscribe_action_after_html_display', $list); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr />
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="box box-primary borderless">
                                <div class="box-header">
                                    <div class="pull-left">
                                        <h3 class="box-title"><?php echo t('lists', 'Company details'); ?> <small>(<?php echo t('lists', 'defaults to <a href="{href}">account company</a>', ['{href}' => createUrl('account/company')]); ?>)</small></h3>
                                    </div>
                                    <div class="pull-right"></div>
                                    <div class="clearfix"><!-- --></div>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($listCompany, 'name'); ?>
                                                <?php echo $form->textField($listCompany, 'name', $listCompany->fieldDecorator->getHtmlOptions('name')); ?>
                                                <?php echo $form->error($listCompany, 'name'); ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($listCompany, 'type_id'); ?>
                                                <?php echo $form->dropDownList($listCompany, 'type_id', CMap::mergeArray(['' => t('app', 'Please select')], CompanyType::getListForDropDown()), $listCompany->fieldDecorator->getHtmlOptions('type_id')); ?>
                                                <?php echo $form->error($listCompany, 'type_id'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($listCompany, 'country_id'); ?>
                                                <?php echo $listCompany->getCountriesDropDown(); ?>
                                                <?php echo $form->error($listCompany, 'country_id'); ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($listCompany, 'zone_id'); ?>
                                                <?php echo $listCompany->getZonesDropDown(); ?>
                                                <?php echo $form->error($listCompany, 'zone_id'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($listCompany, 'address_1'); ?>
                                                <?php echo $form->textField($listCompany, 'address_1', $listCompany->fieldDecorator->getHtmlOptions('address_1')); ?>
                                                <?php echo $form->error($listCompany, 'address_1'); ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($listCompany, 'address_2'); ?>
                                                <?php echo $form->textField($listCompany, 'address_2', $listCompany->fieldDecorator->getHtmlOptions('address_2')); ?>
                                                <?php echo $form->error($listCompany, 'address_2'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-2 zone-name-wrap">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($listCompany, 'zone_name'); ?>
                                                <?php echo $form->textField($listCompany, 'zone_name', $listCompany->fieldDecorator->getHtmlOptions('zone_name')); ?>
                                                <?php echo $form->error($listCompany, 'zone_name'); ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 city-wrap">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($listCompany, 'city'); ?>
                                                <?php echo $form->textField($listCompany, 'city', $listCompany->fieldDecorator->getHtmlOptions('city')); ?>
                                                <?php echo $form->error($listCompany, 'city'); ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 zip-wrap">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($listCompany, 'zip_code'); ?>
                                                <?php echo $form->textField($listCompany, 'zip_code', $listCompany->fieldDecorator->getHtmlOptions('zip_code')); ?>
                                                <?php echo $form->error($listCompany, 'zip_code'); ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 phone-wrap">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($listCompany, 'phone'); ?>
                                                <?php echo $form->textField($listCompany, 'phone', $listCompany->fieldDecorator->getHtmlOptions('phone')); ?>
                                                <?php echo $form->error($listCompany, 'phone'); ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($listCompany, 'website'); ?>
                                                <?php echo $form->urlField($listCompany, 'website', $listCompany->fieldDecorator->getHtmlOptions('website')); ?>
                                                <?php echo $form->error($listCompany, 'website'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($listCompany, 'address_format'); ?> [<a data-toggle="modal" href="#company-available-tags-modal"><?php echo t('lists', 'Available tags'); ?></a>]
                                                <?php echo $form->textArea($listCompany, 'address_format', $listCompany->fieldDecorator->getHtmlOptions('address_format', ['rows' => 4])); ?>
                                                <?php echo $form->error($listCompany, 'address_format'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"><!-- --></div>
                    <?php
                    /**
                     * This hook gives a chance to append content after the active form fields.
                     * Please note that from inside the action callback you can access all the controller view variables
                     * via {@CAttributeCollection $collection->controller->getData()}
                     * @since 1.3.3.1
                     */
                    hooks()->doAction('after_active_form_fields', new CAttributeCollection([
                        'controller'    => $controller,
                        'form'          => $form,
                    ])); ?>
                    <div class="clearfix"><!-- --></div>
                </div>
                <div class="box-footer">
                    <div class="pull-right">
                        <div class="btn-group dropup buttons-save-changes-and-action">
                            <button type="button" class="btn btn-primary btn-flat dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php echo IconHelper::make('save') . t('app', 'Save changes and...'); ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li><button type="submit" class="btn btn-primary btn-flat"><?php echo t('app', 'Stay on page'); ?></button></li>
                                <li><button type="submit" class="btn btn-primary btn-flat" name="save-back" value="1"><?php echo t('lists', 'Back to lists'); ?></button></li>
                            </ul>
                        </div>
                    </div>
                    <div class="clearfix"><!-- --></div>
                </div>
            </div>
            <?php
            $controller->endWidget();
        }
        /**
         * This hook gives a chance to append content after the active form fields.
         * Please note that from inside the action callback you can access all the controller view variables
         * via {@CAttributeCollection $collection->controller->getData()}
         * @since 1.3.3.1
         */
        hooks()->doAction('after_active_form', new CAttributeCollection([
            'controller'      => $controller,
            'renderedForm'    => $collection->itemAt('renderForm'),
        ]));
        ?>
        <div class="modal fade" id="company-available-tags-modal" tabindex="-1" role="dialog" aria-labelledby="company-available-tags-modal-label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title"><?php echo t('lists', 'Available tags'); ?></h4>
                    </div>
                    <div class="modal-body" style="max-height: 300px; overflow-y:scroll;">
                        <table class="table table-hover">
                            <tr>
                                <td><?php echo t('lists', 'Tag'); ?></td>
                                <td><?php echo t('lists', 'Required'); ?></td>
                            </tr>
                            <?php foreach ($listCompany->getAvailableTags() as $tag) { ?>
                                <tr>
                                    <td><?php echo html_encode($tag['tag']); ?></td>
                                    <td><?php echo $tag['required'] ? strtoupper(t('app', ListCompany::TEXT_YES)) : strtoupper(t('app', ListCompany::TEXT_NO)); ?></td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <?php
}
/**
 * This hook gives a chance to append content after the view file default content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * @since 1.3.3.1
 */
hooks()->doAction('after_view_file_content', new CAttributeCollection([
    'controller'        => $controller,
    'renderedContent'   => $viewCollection->itemAt('renderContent'),
]));
