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

/** @var Campaign $campaign */
$campaign = $controller->getData('campaign');

/** @var CampaignTemplate $template */
$template = $controller->getData('template');

/** @var CampaignRandomContent $randomContent */
$randomContent = $controller->getData('randomContent');

/** @var array $templateListsArray */
$templateListsArray = (array)$controller->getData('templateListsArray');

/** @var array $templateContentUrls */
$templateContentUrls = (array)$controller->getData('templateContentUrls');

/** @var array $clickAllowedActions */
$clickAllowedActions = (array)$controller->getData('clickAllowedActions');

/** @var CampaignTemplateUrlActionSubscriber $templateUrlActionSubscriber */
$templateUrlActionSubscriber = $controller->getData('templateUrlActionSubscriber');

/** @var CampaignTemplateUrlActionSubscriber[] $templateUrlActionSubscriberModels */
$templateUrlActionSubscriberModels = (array)$controller->getData('templateUrlActionSubscriberModels');

/** @var bool $webhooksEnabled */
$webhooksEnabled = (bool)$controller->getData('webhooksEnabled');

/** @var CampaignTrackUrlWebhook $urlWebhook */
$urlWebhook = $controller->getData('urlWebhook');

/** @var CampaignTrackUrlWebhook[] $urlWebhookModels */
$urlWebhookModels = (array)$controller->getData('urlWebhookModels');

/** @var CampaignTemplateUrlActionListField $templateUrlActionListField */
$templateUrlActionListField = $controller->getData('templateUrlActionListField');

/** @var CampaignTemplateUrlActionListField[] $templateUrlActionListFields */
$templateUrlActionListFields = (array)$controller->getData('templateUrlActionListFields');

/** @var CampaignEmailTemplateUpload $templateUp */
$templateUp = $controller->getData('templateUp');

/** @var string $lastTestEmails */
$lastTestEmails = (string)$controller->getData('lastTestEmails');

/** @var string $lastTestFromEmail */
$lastTestFromEmail = (string)$controller->getData('lastTestFromEmail');

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
if ($viewCollection->itemAt('renderContent')) {
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
        $form = $controller->beginWidget('CActiveForm', [
            'action' => ['campaigns/template', 'campaign_uid' => $campaign->campaign_uid, 'do' => 'create'],
        ]);
        echo CHtml::hiddenField('selected_template_id', 0, ['id' => 'selected_template_id']); ?>
        <div class="box box-primary borderless">
            <div class="box-header">
                <div class="pull-left">
                    <h3 class="box-title">
                        <?php echo IconHelper::make('envelope') . $pageHeading; ?>
                    </h3>
                </div>
                <div class="pull-right">
                    <?php echo CHtml::link(t('email_templates', 'Import html from url'), '#template-import-modal', ['class' => 'btn btn-primary btn-flat', 'data-toggle' => 'modal', 'title' => t('email_templates', 'Import html from url')]); ?>
                    <?php echo CHtml::link(t('email_templates', 'Upload template'), '#template-upload-modal', ['class' => 'btn btn-primary btn-flat', 'data-toggle' => 'modal', 'title' => t('email_templates', 'Upload template')]); ?>
                    <?php echo CHtml::link(t('campaigns', 'Change/Select template'), ['campaigns/template', 'campaign_uid' => $campaign->campaign_uid, 'do' => 'select'], ['class' => 'btn btn-primary btn-flat', 'title' => t('campaigns', 'Change/Select template')]); ?>
                    <?php if (!empty($template->content)) { ?>
                    <?php echo CHtml::link(t('campaigns', 'Test template'), '#template-test-email', ['class' => 'btn btn-primary btn-flat', 'title' => t('campaigns', 'Test template'), 'data-toggle' => 'modal']); ?>
                    <?php } ?>
                    <?php echo CHtml::link(IconHelper::make('cancel') . t('app', 'Cancel'), ['campaigns/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]); ?>
                </div>
                <div class="clearfix"><!-- --></div>
            </div>
            <div class="box-body">
                <?php
                /**
                 * This hook gives a chance to prepend content before the active form fields.
                 * Please note that from inside the action callback you can access all the controller view variables
                 * via {@CAttributeCollection $collection->controller->getData()}
                 * @since 1.3.3.1
                 */
                hooks()->doAction('before_active_form_fields', new CAttributeCollection([
                    'controller'    => $controller,
                    'form'          => $form,
                ])); ?>

                <?php
                // since 1.3.9.0
                hooks()->doAction('campaign_form_template_step_before_top_options', [
                    'controller' => $controller,
                    'campaign'   => $campaign,
                    'form'       => $form,
                    'template'   => $template,
                ]); ?>

                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($template, 'name'); ?>
                            <?php echo $form->textField($template, 'name', $template->fieldDecorator->getHtmlOptions('name')); ?>
                            <?php echo $form->error($template, 'name'); ?>
                        </div>
                    </div>
                    <?php if (!empty($campaign->option) && $campaign->option->plain_text_email == CampaignOption::TEXT_YES) { ?>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($template, 'only_plain_text'); ?>
                                <?php echo $form->dropDownList($template, 'only_plain_text', $template->getYesNoOptions(), $template->fieldDecorator->getHtmlOptions('only_plain_text')); ?>
                                <?php echo $form->error($template, 'only_plain_text'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4 auto-plain-text-wrapper" style="display:<?php echo $template->getIsOnlyPlainText() ? 'none' : ''; ?>;">
                            <div class="form-group">
                                <?php echo $form->labelEx($template, 'auto_plain_text'); ?>
                                <?php echo $form->dropDownList($template, 'auto_plain_text', $template->getYesNoOptions(), $template->fieldDecorator->getHtmlOptions('auto_plain_text')); ?>
                                <?php echo $form->error($template, 'auto_plain_text'); ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <?php
                // since 1.3.9.0
                hooks()->doAction('campaign_form_template_step_after_top_options', [
                    'controller' => $controller,
                    'campaign'   => $campaign,
                    'form'       => $form,
                    'template'   => $template,
                ]); ?>

                <hr />

                <div class="row">
                    <div class="col-lg-12">
                        <div class="html-version" style="display:<?php echo $template->getIsOnlyPlainText() ? 'none' : ''; ?>;">
                            <div class="form-group">
                                <div class="pull-left">
                                    <?php echo $form->labelEx($template, 'content'); ?> [<a data-toggle="modal" href="#available-tags-modal"><?php echo t('lists', 'Available tags'); ?></a>]
                                    <?php
                                    // since 1.3.5
                                    hooks()->doAction('before_wysiwyg_editor_left_side', [
                                        'controller' => $controller,
                                        'template'   => $template,
                                        'campaign'   => $campaign,
                                        'form'       => $form,
                                    ]); ?>
                                </div>
                                <div class="pull-right">
                                    <?php
                                    // since 1.3.5
                                    hooks()->doAction('before_wysiwyg_editor_right_side', [
                                        'controller' => $controller,
                                        'template'   => $template,
                                        'campaign'   => $campaign,
                                        'form'       => $form,
                                    ]); ?>
                                </div>
                                <div class="clearfix"><!-- --></div>
                                <?php echo $form->textArea($template, 'content', $template->fieldDecorator->getHtmlOptions('content', ['rows' => 30])); ?>
                                <?php echo $form->error($template, 'content'); ?>
                                <?php
                                // since 1.4.4
                                hooks()->doAction('after_wysiwyg_editor', [
                                    'controller' => $controller,
                                    'template'   => $template,
                                    'campaign'   => $campaign,
                                    'form'       => $form,
                                ]); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <hr />

                <?php if (!empty($templateContentUrls)) { ?>

                    <div class="template-click-actions-list-fields-container" style="display: none;">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="pull-left">
                                    <h5><?php echo t('campaigns', 'Change subscriber custom field on link click'); ?></h5>
                                </div>
                                <div class="pull-right">
                                    <a href="javascript:;" class="btn btn-primary btn-flat btn-template-click-actions-list-fields-add"><?php echo IconHelper::make('create'); ?></a>
                                    <?php echo CHtml::link(IconHelper::make('info'), '#page-info-template-click-actions-list-fields-list', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                </div>
                                <div class="clearfix"><!-- --></div>
                                <div class="template-click-actions-list-fields-list">
                                    <?php if (!empty($templateUrlActionListFields)) {
                                    foreach ($templateUrlActionListFields as $index => $templateUrlActionListFieldMdl) { ?>
                                        <div class="template-click-actions-list-fields-row" data-start-index="<?php echo (int)$index; ?>" style="margin-bottom: 10px;">
                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <?php echo $form->labelEx($templateUrlActionListFieldMdl, 'url'); ?>
                                                        <?php echo CHtml::dropDownList($templateUrlActionListFieldMdl->getModelName() . '[' . $index . '][url]', $templateUrlActionListFieldMdl->url, $templateContentUrls, $templateUrlActionListFieldMdl->fieldDecorator->getHtmlOptions('url', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                                                        <?php echo $form->error($templateUrlActionListFieldMdl, 'url'); ?>
                                                    </div>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="form-group">
                                                        <?php echo $form->labelEx($templateUrlActionListField, 'field_id'); ?>
                                                        <?php echo CHtml::dropDownList($templateUrlActionListField->getModelName() . '[' . $index . '][field_id]', $templateUrlActionListFieldMdl->field_id, CMap::mergeArray(['' => t('app', 'Choose')], $templateUrlActionListFieldMdl->getCustomFieldsAsDropDownOptions()), $templateUrlActionListFieldMdl->fieldDecorator->getHtmlOptions('field_id', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                                                        <?php echo $form->error($templateUrlActionListField, 'field_id'); ?>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <?php echo $form->labelEx($templateUrlActionListFieldMdl, 'field_value'); ?>
                                                        <?php echo CHtml::textField($templateUrlActionListFieldMdl->getModelName() . '[' . $index . '][field_value]', $templateUrlActionListFieldMdl->field_value, $templateUrlActionListFieldMdl->fieldDecorator->getHtmlOptions('field_value')); ?>
                                                        <?php echo $form->error($templateUrlActionListFieldMdl, 'field_value'); ?>
                                                    </div>
                                                </div>
                                                <div class="col-lg-1">
                                                    <a style="margin-top: 25px;" href="javascript:;" class="btn btn-danger btn-flat btn-template-click-actions-list-fields-remove" data-url-id="<?php echo $templateUrlActionListFieldMdl->url_id; ?>" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php }
                                } ?>
                                </div>
                                <div class="clearfix"><!-- --></div>
                                <hr />
                            </div>
                        </div>
                    </div>
                    <div class="template-click-actions-container" style="display: none;">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="pull-left">
                                    <h5><?php echo t('campaigns', 'Actions against subscriber on link click'); ?></h5>
                                </div>
                                <div class="pull-right">
                                    <a href="javascript:;" class="btn btn-primary btn-flat btn-template-click-actions-add"><?php echo IconHelper::make('create'); ?></a>
                                    <?php echo CHtml::link(IconHelper::make('info'), '#page-info-template-click-actions-list', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                </div>
                                <div class="clearfix"><!-- --></div>
                                <div class="template-click-actions-list">
                                    <?php if (!empty($templateUrlActionSubscriberModels)) {
                                    foreach ($templateUrlActionSubscriberModels as $index => $templateUrlActionSub) { ?>
                                        <div class="template-click-actions-row" data-start-index="<?php echo (int)$index; ?>" style="margin-bottom: 10px;">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <?php echo $form->labelEx($templateUrlActionSub, 'url'); ?>
                                                        <?php echo CHtml::dropDownList($templateUrlActionSub->getModelName() . '[' . $index . '][url]', $templateUrlActionSub->url, $templateContentUrls, $templateUrlActionSub->fieldDecorator->getHtmlOptions('url', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                                                        <?php echo $form->error($templateUrlActionSub, 'url'); ?>
                                                    </div>
                                                </div>
                                                <div class="col-lg-1">
                                                    <div class="form-group">
                                                        <?php echo $form->labelEx($templateUrlActionSub, 'action'); ?>
                                                        <?php echo CHtml::dropDownList($templateUrlActionSub->getModelName() . '[' . $index . '][action]', $templateUrlActionSub->action, $clickAllowedActions, $templateUrlActionSub->fieldDecorator->getHtmlOptions('action', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                                                        <?php echo $form->error($templateUrlActionSub, 'action'); ?>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <?php echo $form->labelEx($templateUrlActionSub, 'list_id'); ?>
                                                        <?php echo CHtml::dropDownList($templateUrlActionSub->getModelName() . '[' . $index . '][list_id]', $templateUrlActionSub->list_id, $templateListsArray, $templateUrlActionSub->fieldDecorator->getHtmlOptions('list_id', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                                                        <?php echo $form->error($templateUrlActionSub, 'list_id'); ?>
                                                    </div>
                                                </div>
                                                <div class="col-lg-1">
                                                    <a style="margin-top: 25px;" href="javascript:;" class="btn btn-danger btn-template-click-actions-remove" data-url-id="<?php echo $templateUrlActionSub->url_id; ?>" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php }
                                } ?>
                                </div>
                                <div class="clearfix"><!-- --></div>
                                <hr />
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($webhooksEnabled)) { ?>
                        <div class="campaign-track-url-webhook-container" style="display: none;">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="pull-left">
                                        <h5><?php echo t('campaigns', 'Subscribers webhooks on link click'); ?></h5>
                                    </div>
                                    <div class="pull-right">
                                        <a href="javascript:;" class="btn btn-primary btn-flat btn-campaign-track-url-webhook-add"><?php echo IconHelper::make('create'); ?></a>
                                        <?php echo CHtml::link(IconHelper::make('info'), '#page-info-campaign-track-url-webhook', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                    </div>
                                    <div class="clearfix"><!-- --></div>
                                    <div class="campaign-track-url-webhook-list">
                                        <?php if (!empty($urlWebhookModels)) {
                                    foreach ($urlWebhookModels as $index => $urlWebhookModel) { ?>
                                            <div class="campaign-track-url-webhook-row" data-start-index="<?php echo (int)$index; ?>" style="margin-bottom: 10px;">
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <?php echo $form->labelEx($urlWebhookModel, 'track_url'); ?>
                                                            <?php echo CHtml::dropDownList($urlWebhookModel->getModelName() . '[' . $index . '][track_url]', $urlWebhookModel->track_url, $templateContentUrls, $urlWebhookModel->fieldDecorator->getHtmlOptions('track_url')); ?>
                                                            <?php echo $form->error($urlWebhookModel, 'track_url'); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-5">
                                                        <div class="form-group">
                                                            <?php echo $form->labelEx($urlWebhookModel, 'webhook_url'); ?>
                                                            <?php echo CHtml::textField($urlWebhookModel->getModelName() . '[' . $index . '][webhook_url]', $urlWebhookModel->webhook_url, $urlWebhookModel->fieldDecorator->getHtmlOptions('webhook_url')); ?>
                                                            <?php echo $form->error($urlWebhookModel, 'webhook_url'); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-1">
                                                        <a style="margin-top: 25px;" href="javascript:;" class="btn btn-danger btn-campaign-track-url-webhook-remove" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php }
                                } ?>
                                    </div>
                                    <div class="clearfix"><!-- --></div>
                                    <hr />
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <!-- modals -->
                    <div class="modal modal-info fade" id="page-info-template-click-actions-list-fields-list" tabindex="-1" role="dialog">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                    <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                                </div>
                                <div class="modal-body">
                                    <?php echo t('campaigns', 'When a subscriber clicks one or more links from your email template, do following actions against one of the subscriber custom fields.'); ?><br />
                                    <?php echo t('campaigns', 'This is useful if you later need to segment your list and find out who clicked on links in this campaign or who did not and based on that to take another action, like sending the campaign again to subscribers that did/did not clicked certain link previously.'); ?><br />
                                    <?php echo t('campaigns', 'In most of the cases, you will want to keep these fields as hidden fields.'); ?><br />
                                    <br />
                                    <?php echo t('campaigns', 'Following tags are available to be used as dynamic values:'); ?><br />
                                    <div style="width: 100%; height: 200px; overflow-y: scroll">
                                        <table class="table table-bordered table-condensed">
                                            <thead>
                                            <tr>
                                                <th><?php echo t('campaigns', 'Tag'); ?></th>
                                                <th><?php echo t('campaigns', 'Description'); ?></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach (CampaignHelper::getParsedFieldValueByListFieldValueTagInfo() as $tag => $tagInfo) { ?>
                                                <tr>
                                                    <td><?php echo $tag; ?></td>
                                                    <td><?php echo $tagInfo; ?></td>
                                                </tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal modal-info fade" id="page-info-template-click-actions-list" tabindex="-1" role="dialog">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                    <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                                </div>
                                <div class="modal-body">
                                    <?php echo t('campaigns', 'When a subscriber clicks one or more links from your email template, do following actions against the subscriber itself:'); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($webhooksEnabled)) { ?>
                        <div class="modal modal-info fade" id="page-info-campaign-track-url-webhook" tabindex="-1" role="dialog">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                        <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                                    </div>
                                    <div class="modal-body">
                                        <?php echo t('campaigns', 'When a campaign url is clicked by a subscriber, send a webhook request containing event data to the given urls'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                <?php } ?>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="plain-text-version" style="display:<?php echo $template->getIsOnlyPlainText() ? 'block' : 'none'; ?>;">
                            <div class="form-group">
                                <?php echo $form->labelEx($template, 'plain_text'); ?> [<a data-toggle="modal" href="#available-tags-modal"><?php echo t('lists', 'Available tags'); ?></a>]
                                <?php echo $form->textArea($template, 'plain_text', $template->fieldDecorator->getHtmlOptions('plain_text', ['rows' => 20])); ?>
                                <?php echo $form->error($template, 'plain_text'); ?>
                                <?php echo $form->error($template, 'content'); ?>
                            </div>
                            <hr />
                        </div>
                    </div>
                </div>

                <div class="random-content-container" style="display: none">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="pull-left">
                                <h5><?php echo t('campaigns', 'Random content blocks'); ?></h5>
                            </div>
                            <div class="pull-right">
                                <a href="javascript:;" class="btn btn-primary btn-flat btn-template-random-content-item-add"><?php echo IconHelper::make('create'); ?></a>
                                <?php echo CHtml::link(IconHelper::make('info'), '#page-info-random-content-container', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                            </div>
                            <div class="clearfix"><!-- --></div>
                            <div class="row">
                                <div class="random-content-container-items">
                                    <?php if (!empty($campaign->randomContents)) {
                                    foreach ($campaign->randomContents as $index => $rndContent) { ?>
                                        <div class="col-lg-6 random-content-item" data-counter="<?php echo (int)$index; ?>" style="margin-top:10px">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <div class="pull-left">
                                                            <?php echo CHtml::link(IconHelper::make('info'), '#page-info-random-content-name', ['class' => 'btn btn-xs btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                                            <?php echo $form->labelEx($rndContent, 'name'); ?>
                                                        </div>
                                                        <div class="pull-right">
                                                            <?php echo CHtml::link(IconHelper::make('delete'), 'javascript:;', ['class' => 'btn btn-xs btn-danger btn-flat btn-template-random-content-item-delete', 'title' => t('app', 'Delete')]); ?>
                                                        </div>
                                                        <div class="clearfix"><!-- --></div>
                                                        <?php echo $form->textField($rndContent, 'name', $rndContent->fieldDecorator->getHtmlOptions('name', [
                                                            'id'   => $rndContent->getModelName() . '_name_' . (int)$index,
                                                            'name' => $rndContent->getModelName() . '[' . $index . '][name]',
                                                        ])); ?>
                                                        <?php echo $form->error($rndContent, 'name'); ?>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <?php echo $form->labelEx($rndContent, 'content'); ?>
                                                        <?php echo $form->textArea($rndContent, 'content', $rndContent->fieldDecorator->getHtmlOptions('content', [
                                                            'id'   => $rndContent->getModelName() . '_content_' . (int)$index,
                                                            'name' => $rndContent->getModelName() . '[' . $index . '][content]',
                                                        ])); ?>
                                                        <?php echo $form->error($rndContent, 'content'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php }
                                } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="random-content-template" style="display: none">
                        <div class="col-lg-6 random-content-item" data-counter="{counter}" style="margin-top:10px">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <div class="pull-left">
                                            <?php echo CHtml::link(IconHelper::make('info'), '#page-info-random-content-name', ['class' => 'btn btn-xs btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                            <?php echo $form->labelEx($randomContent, 'name'); ?>
                                        </div>
                                        <div class="pull-right">
                                            <?php echo CHtml::link(IconHelper::make('delete'), 'javascript:;', ['class' => 'btn btn-xs btn-danger btn-flat btn-template-random-content-item-delete', 'title' => t('app', 'Delete')]); ?>
                                        </div>
                                        <div class="clearfix"><!-- --></div>
                                        <?php echo $form->textField($randomContent, 'name', $randomContent->fieldDecorator->getHtmlOptions('name', [
                                            'id'   => $randomContent->getModelName() . '_name_{counter}',
                                            'name' => $randomContent->getModelName() . '[{counter}][name]',
                                        ])); ?>
                                        <?php echo $form->error($randomContent, 'name'); ?>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <?php echo $form->labelEx($randomContent, 'content'); ?>
                                        <?php echo $form->textArea($randomContent, 'content', $randomContent->fieldDecorator->getHtmlOptions('content', [
                                            'id'   => $randomContent->getModelName() . '_content_{counter}',
                                            'name' => $randomContent->getModelName() . '[{counter}][content]',
                                        ])); ?>
                                        <?php echo $form->error($randomContent, 'content'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"><!-- --></div>
                    <hr />
                </div>
                <!-- Modals -->
                <div class="modal modal-info fade" id="page-info-random-content-container" tabindex="-1" role="dialog">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                            </div>
                            <div class="modal-body">
                                <?php echo t('campaigns', 'Random content blocks allows you to rotate random HTML content in your template body by using the [RANDOM_CONTENT] tag.'); ?><br />
                                <?php echo t('campaigns', 'You will define all your random content blocks, and then you will be able to call the [RANDOM_CONTENT] tag like:<br /> {exp} where N1, N2, N3 are the names of your blocks you want to use.<br />You can use an unlimited number of blocks.', [
                                    '{exp}' => '[RANDOM_CONTENT: BLOCK: N1 | BLOCK: N2 | BLOCK: N3]',
                                ]); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal modal-info fade" id="page-info-random-content-name" tabindex="-1" role="dialog">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                            </div>
                            <div class="modal-body">
                                <?php echo t('campaigns', 'Please make sure you use a unique name for your block!'); ?><br />
                                <?php echo t('campaigns', 'You will be able to use this block in the [RANDOM_CONTENT] tag like:<br /> {exp} where N1, N2, N3 are the names of your blocks you want to use.<br />You can use an unlimited number of blocks.', [
                                    '{exp}' => '[RANDOM_CONTENT: BLOCK: N1 | BLOCK: N2 | BLOCK: N3]',
                                ]); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">

                        <?php if (!empty($templateContentUrls)) { ?>
                            <button type="button" class="btn btn-primary btn-flat btn-template-click-actions-list-fields" style="margin-top: 3px">
			                    <?php echo t('campaigns', 'Change subscriber custom field on link click({count})', [
                                    '{count}' => sprintf('<span class="count">%d</span>', (!empty($templateUrlActionListFields) ? count($templateUrlActionListFields) : 0)),
                                ]);
                                ?>
                            </button>

                            <button type="button" class="btn btn-primary btn-flat btn-template-click-actions" style="margin-top: 3px">
			                    <?php echo t('campaigns', 'Actions against subscriber on link click({count})', [
                                    '{count}' => sprintf('<span class="count">%d</span>', (!empty($templateUrlActionSubscriberModels) ? count($templateUrlActionSubscriberModels) : 0)),
                                ]);
                                ?>
                            </button>

		                    <?php if (!empty($webhooksEnabled)) { ?>
                                <button type="button" class="btn btn-primary btn-flat btn-campaign-track-url-webhook" style="margin-top: 3px">
                                    <?php echo t('campaigns', 'Subscribers webhooks on link click({count})', [
                                        '{count}' => sprintf('<span class="count">%d</span>', (!empty($urlWebhookModels) ? count($urlWebhookModels) : 0)),
                                    ]);
                                    ?>
                                </button>
                            <?php } ?>

	                    <?php } ?>

	                    <?php echo CHtml::link(t('campaigns', 'UTM tags'), '#google-utm-tags-modal', ['class' => 'btn btn-primary btn-flat', 'data-toggle' => 'modal', 'title' => t('campaigns', 'Google UTM tags'), 'style' => 'margin-top: 3px']); ?>

	                    <?php if (!empty($campaign->option) && $campaign->option->plain_text_email == CampaignOption::TEXT_YES) { ?>
                            <button type="button" class="btn btn-primary btn-flat btn-plain-text" data-showtext="<?php echo t('campaigns', 'Show plain text version'); ?>" data-hidetext="<?php echo t('campaigns', 'Hide plain text version'); ?>" style="margin-top:3px; display:<?php echo $template->getIsOnlyPlainText() ? 'none' : ''; ?>;"><?php echo t('campaigns', 'Show plain text version'); ?></button>
	                    <?php } ?>

                        <button type="button" class="btn btn-primary btn-flat btn-toggle-random-content" style="margin-top: 3px">
		                    <?php echo t('campaigns', 'Random content({count})', [
                                '{count}' => sprintf('<span class="count">%d</span>', (!empty($campaign->randomContents) ? count($campaign->randomContents) : 0)),
                            ]); ?>
                        </button>

                        <button type="submit" id="is_next" name="is_next" value="0" class="btn btn-primary btn-flat btn-go-next" style="margin-top: 3px">
                            <?php echo t('campaigns', 'Save content'); ?>
                        </button>
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
                <div class="wizard">
                    <ul class="steps">
                        <li class="complete"><a href="<?php echo createAbsoluteUrl('campaigns/update', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Details'); ?></a><span class="chevron"></span></li>
                        <li class="complete"><a href="<?php echo createAbsoluteUrl('campaigns/setup', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Setup'); ?></a><span class="chevron"></span></li>
                        <li class="active"><a href="<?php echo createAbsoluteUrl('campaigns/template', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Template'); ?></a><span class="chevron"></span></li>
                        <li><a href="<?php echo createAbsoluteUrl('campaigns/confirm', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Confirmation'); ?></a><span class="chevron"></span></li>
                        <li><a href="javascript:;"><?php echo t('app', 'Done'); ?></a><span class="chevron"></span></li>
                    </ul>
                    <div class="actions">
                        <button type="submit" id="is_next" name="is_next" value="1" class="btn btn-primary btn-flat btn-go-next"><?php echo IconHelper::make('next') . '&nbsp;' . t('campaigns', 'Save and next'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $controller->endWidget();
    }
    /**
     * This hook gives a chance to append content after the active form.
     * Please note that from inside the action callback you can access all the controller view variables
     * via {@CAttributeCollection $collection->controller->getData()}
     * @since 1.3.3.1
     */
    hooks()->doAction('after_active_form', new CAttributeCollection([
        'controller'      => $controller,
        'renderedForm'    => $collection->itemAt('renderForm'),
    ])); ?>
    <div class="modal fade" id="available-tags-modal" tabindex="-1" role="dialog" aria-labelledby="available-tags-modal-label" aria-hidden="true">
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
                    <?php foreach ($template->getAvailableTags() as $tag) { ?>
                    <tr>
                        <td><?php echo html_encode($tag['tag']); ?></td>
                        <td><?php echo $tag['required'] ? strtoupper(t('app', CampaignTemplate::TEXT_YES)) : strtoupper(t('app', CampaignTemplate::TEXT_NO)); ?></td>
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

    <?php if (!empty($template->content)) { ?>
    <div class="modal fade" id="template-test-email" tabindex="-1" role="dialog" aria-labelledby="template-test-email-label" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title"><?php echo t('campaigns', 'Send a test email'); ?></h4>
            </div>
            <div class="modal-body">
                 <div class="callout callout-info">
                     <strong><?php echo t('app', 'Notes'); ?>: </strong><br />
                     <?php
                     $text = '* if multiple recipients, separate the email addresses by a comma.<br />
                     * the email tags will be parsed and we will pick a random subscriber to impersonate.<br />
                     * the tracking will not be enabled.<br />
                     * for the test email only, the subject will be prefixed with *** TEST ***<br />
                     * make sure you save the template changes before you send the test.';
                     echo t('campaigns', StringHelper::normalizeTranslationString($text));
                     ?>
                 </div>
                 <?php echo CHtml::form(['campaigns/test', 'campaign_uid' => $campaign->campaign_uid], 'post', ['id' => 'template-test-form']); ?>
                 <div class="form-group">
                     <?php echo CHtml::label(t('campaigns', 'Recipient(s)'), 'email'); ?>
                     <?php echo CHtml::textField('email', $lastTestEmails, ['class' => 'form-control', 'placeholder' => t('campaigns', 'i.e: a@domain.com, b@domain.com, c@domain.com')]); ?>
                 </div>
                 <div class="clearfix"><!-- --></div>
                 <div class="form-group">
                     <?php echo CHtml::label(t('campaigns', 'From email (optional)'), 'from_email'); ?>
                     <?php echo CHtml::textField('from_email', $lastTestFromEmail, ['class' => 'form-control', 'placeholder' => t('campaigns', 'i.e: me@domain.com')]); ?>
                 </div>
                 <?php echo CHtml::endForm(); ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
              <button type="button" class="btn btn-primary btn-flat" onclick="$('#template-test-form').submit();"><?php echo IconHelper::make('fa-send') . '&nbsp;' . t('campaigns', 'Send test'); ?></button>
            </div>
          </div>
        </div>
    </div>
    <?php } ?>

    <div class="modal fade" id="template-upload-modal" tabindex="-1" role="dialog" aria-labelledby="template-upload-modal-label" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title"><?php echo t('email_templates', 'Upload template archive'); ?></h4>
            </div>
            <div class="modal-body">
                 <div class="callout callout-info">
                    <?php
                    $text = '
                    Please see <a href="{templateArchiveHref}">this example archive</a> in order to understand how you should format your uploaded archive!
                    Also, please note we only accept zip files.';
    echo t('email_templates', StringHelper::normalizeTranslationString($text), [
                        '{templateArchiveHref}' => apps()->getAppUrl('customer', 'assets/files/example-template.zip', false, true),
                    ]); ?>
                 </div>
                <?php
                $form = $controller->beginWidget('CActiveForm', [
                    'action'        => ['campaigns/template', 'campaign_uid' => $campaign->campaign_uid, 'do' => 'upload'],
                    'id'            => $templateUp->getModelName() . '-upload-form',
                    'htmlOptions'   => [
                        'id'        => 'upload-template-form',
                        'enctype'   => 'multipart/form-data',
                    ],
                ]); ?>
                <div class="form-group">
                    <?php echo $form->labelEx($templateUp, 'archive'); ?>
                    <?php echo $form->fileField($templateUp, 'archive', $templateUp->fieldDecorator->getHtmlOptions('archive')); ?>
                    <?php echo $form->error($templateUp, 'archive'); ?>
                </div>
                <?php if (!empty($campaign->option) && $campaign->option->plain_text_email == CampaignOption::TEXT_YES) { ?>
                <div class="form-group">
                    <?php echo $form->labelEx($templateUp, 'auto_plain_text'); ?>
                    <?php echo $form->dropDownList($templateUp, 'auto_plain_text', $templateUp->getYesNoOptions(), $templateUp->fieldDecorator->getHtmlOptions('auto_plain_text')); ?>
                    <div class="help-block"><?php echo $templateUp->fieldDecorator->getAttributeHelpText('auto_plain_text'); ?></div>
                    <?php echo $form->error($templateUp, 'auto_plain_text'); ?>
                </div>
                <?php } ?>
                <?php $controller->endWidget(); ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
              <button type="button" class="btn btn-primary btn-flat" onclick="$('#upload-template-form').submit();"><?php echo t('email_templates', 'Upload archive'); ?></button>
            </div>
          </div>
        </div>
    </div>

    <div class="modal fade" id="template-import-modal" tabindex="-1" role="dialog" aria-labelledby="template-import-modal-label" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title"><?php echo t('email_templates', 'Import html template from url'); ?></h4>
            </div>
            <div class="modal-body">
                 <div class="callout callout-info">
                    <?php echo t('email_templates', 'Please note that your url must contain a valid html email template with absolute paths to resources!'); ?>
                 </div>
                <?php
                $form = $controller->beginWidget('CActiveForm', [
                    'action'        => ['campaigns/template', 'campaign_uid' => $campaign->campaign_uid, 'do' => 'from-url'],
                    'id'            => $template->getModelName() . '-import-form',
                    'htmlOptions'   => [
                        'id'        => 'import-template-form',
                        'enctype'   => 'multipart/form-data',
                    ],
                ]); ?>
                <div class="form-group">
                    <?php echo $form->labelEx($template, 'from_url'); ?>
                    <?php echo $form->textField($template, 'from_url', $template->fieldDecorator->getHtmlOptions('from_url')); ?>
                    <?php echo $form->error($template, 'from_url'); ?>
                </div>
                <?php if (!empty($campaign->option) && $campaign->option->plain_text_email == CampaignOption::TEXT_YES) { ?>
                <div class="form-group">
                    <?php echo $form->labelEx($template, 'auto_plain_text'); ?>
                    <?php echo $form->dropDownList($template, 'auto_plain_text', $template->getYesNoOptions(), $template->fieldDecorator->getHtmlOptions('auto_plain_text')); ?>
                    <div class="help-block"><?php echo $template->fieldDecorator->getAttributeHelpText('auto_plain_text'); ?></div>
                    <?php echo $form->error($template, 'auto_plain_text'); ?>
                </div>
                <?php } ?>
                <?php $controller->endWidget(); ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
              <button type="button" class="btn btn-primary btn-flat" onclick="$('#import-template-form').submit();"><?php echo t('email_templates', 'Import'); ?></button>
            </div>
          </div>
        </div>
    </div>

    <div class="modal fade" id="google-utm-tags-modal" tabindex="-1" role="dialog" aria-labelledby="google-utm-tags-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php echo t('campaigns', 'Google UTM tags pattern'); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="callout">
                        <?php echo t('campaigns', 'After you insert your UTM tags pattern, each link from your email template will be transformed and this pattern will be appended for tracking. Beside all the regular template tags, following special tags are also recognized:'); ?>
                        <hr />
                        <table class="table table-bordered table-condensed">
                            <tr>
                                <td><?php echo t('lists', 'Tag'); ?></td>
                                <td><?php echo t('lists', 'Description'); ?></td>
                            </tr>
                            <?php foreach ($template->getExtraUtmTags() as $tag => $tagDescription) { ?>
                                <tr>
                                    <td><?php echo html_encode($tag); ?></td>
                                    <td><?php echo html_encode($tagDescription); ?></td>
                                </tr>
                            <?php } ?>
                        </table>
                        <hr />
                        <strong><?php echo t('campaigns', 'Example pattern:'); ?></strong><br />
                        <span>utm_source=mail_from_[CURRENT_DATE]&utm_medium=email&utm_term=[EMAIL]&utm_campaign=[CAMPAIGN_NAME]</span>
                    </div>
                    <?php echo CHtml::form(['campaigns/google_utm_tags', 'campaign_uid' => $campaign->campaign_uid], 'post', ['id' => 'google-utm-tags-form']); ?>
                    <div class="form-group">
                        <label><?php echo t('campaigns', 'Insert your pattern'); ?>:</label>
                        <?php echo CHtml::textField('google_utm_pattern', '', ['class' => 'form-control']); ?>
                    </div>
                    <?php echo CHtml::endForm(); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
                    <button type="button" class="btn btn-primary btn-flat" onclick="$('#google-utm-tags-form').submit(); return false;"><?php echo t('campaigns', 'Parse links and set pattern'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($templateContentUrls)) { ?>
        <div id="template-click-actions-list-fields-template" style="display: none;">
            <div class="template-click-actions-list-fields-row" data-start-index="{index}" style="margin-bottom: 10px;">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($templateUrlActionListField, 'url'); ?>
                            <?php echo CHtml::dropDownList($templateUrlActionListField->getModelName() . '[{index}][url]', null, $templateContentUrls, $templateUrlActionListField->fieldDecorator->getHtmlOptions('url', ['class' => 'form-control select2-no-init', 'style' => 'width: 100%'])); ?>
                            <?php echo $form->error($templateUrlActionListField, 'url'); ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($templateUrlActionListField, 'field_id'); ?>
                            <?php echo CHtml::dropDownList($templateUrlActionListField->getModelName() . '[{index}][field_id]', null, CMap::mergeArray(['' => t('app', 'Choose')], $templateUrlActionListField->getCustomFieldsAsDropDownOptions()), $templateUrlActionListField->fieldDecorator->getHtmlOptions('field_id', ['class' => 'form-control select2-no-init', 'style' => 'width: 100%'])); ?>
                            <?php echo $form->error($templateUrlActionListField, 'field_id'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($templateUrlActionListField, 'field_value'); ?>
                            <?php echo CHtml::textField($templateUrlActionListField->getModelName() . '[{index}][field_value]', null, $templateUrlActionListField->fieldDecorator->getHtmlOptions('field_value')); ?>
                            <?php echo $form->error($templateUrlActionListField, 'field_value'); ?>
                        </div>
                    </div>
                    <div class="col-lg-1">
                        <a style="margin-top: 25px;" href="javascript:;" class="btn btn-flat btn-danger btn-template-click-actions-list-fields-remove" data-url-id="0" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                    </div>
                </div>
            </div>
        </div>

        <div id="template-click-actions-template" style="display: none;">
            <div class="template-click-actions-row" data-start-index="{index}" style="margin-bottom: 10px;">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <?php echo $form->labelEx($templateUrlActionSubscriber, 'url'); ?>
                            <?php echo CHtml::dropDownList($templateUrlActionSubscriber->getModelName() . '[{index}][url]', null, $templateContentUrls, $templateUrlActionSubscriber->fieldDecorator->getHtmlOptions('url', ['class' => 'form-control select2-no-init', 'style' => 'width: 100%'])); ?>
                            <?php echo $form->error($templateUrlActionSubscriber, 'url'); ?>
                        </div>
                    </div>
                    <div class="col-lg-1">
                        <div class="form-group">
                            <?php echo $form->labelEx($templateUrlActionSubscriber, 'action'); ?>
                            <?php echo CHtml::dropDownList($templateUrlActionSubscriber->getModelName() . '[{index}][action]', null, $clickAllowedActions, $templateUrlActionSubscriber->fieldDecorator->getHtmlOptions('action', ['class' => 'form-control select2-no-init', 'style' => 'width: 100%'])); ?>
                            <?php echo $form->error($templateUrlActionSubscriber, 'action'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($templateUrlActionSubscriber, 'list_id'); ?>
                            <?php echo CHtml::dropDownList($templateUrlActionSubscriber->getModelName() . '[{index}][list_id]', null, $templateListsArray, $templateUrlActionSubscriber->fieldDecorator->getHtmlOptions('list_id', ['class' => 'form-control select2-no-init', 'style' => 'width: 100%'])); ?>
                            <?php echo $form->error($templateUrlActionSubscriber, 'list_id'); ?>
                        </div>
                    </div>
                    <div class="col-lg-1">
                        <a style="margin-top: 25px;" href="javascript:;" class="btn btn-flat btn-danger btn-template-click-actions-remove" data-url-id="0" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                    </div>
                </div>
            </div>
        </div>

		<?php if (!empty($webhooksEnabled)) { ?>
            <div id="campaign-track-url-webhook-template" style="display: none;">
                <div class="campaign-track-url-webhook-row" data-start-index="{index}" style="margin-bottom: 10px;">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <?php echo $form->labelEx($urlWebhook, 'track_url'); ?>
                                <?php echo CHtml::dropDownList($urlWebhook->getModelName() . '[{index}][track_url]', null, $templateContentUrls, $urlWebhook->fieldDecorator->getHtmlOptions('track_url')); ?>
                                <?php echo $form->error($urlWebhook, 'track_url'); ?>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="form-group">
                                <?php echo $form->labelEx($urlWebhook, 'webhook_url'); ?>
                                <?php echo CHtml::textField($urlWebhook->getModelName() . '[{index}][webhook_url]', null, $urlWebhook->fieldDecorator->getHtmlOptions('webhook_url')); ?>
                                <?php echo $form->error($urlWebhook, 'webhook_url'); ?>
                            </div>
                        </div>
                        <div class="col-lg-1">
                            <a style="margin-top: 25px;" href="javascript:;" class="btn btn-flat btn-danger btn-campaign-track-url-webhook-remove" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

    <?php } ?>
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
