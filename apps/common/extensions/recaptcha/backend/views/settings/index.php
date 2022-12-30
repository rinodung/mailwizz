<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

/** @var ExtensionController $controller */
$controller = controller();

/** @var RecaptchaExtCommon $model */
$model = $controller->getData('model');

/** @var RecaptchaExtDomainsKeysPair $domainsKeysPair */
$domainsKeysPair = $controller->getData('domainsKeysPair');

/** @var CActiveForm $form */
$form = $controller->beginWidget('CActiveForm');

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->data}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->renderContent} to false
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
     * via {@CAttributeCollection $collection->controller->data}
     * In case the form is replaced, make sure to set {@CAttributeCollection $collection->renderForm} to false
     * in order to stop rendering the default content.
     * @since 1.3.3.1
     */
    hooks()->doAction('before_active_form', $collection = new CAttributeCollection([
        'controller'    => $controller,
        'renderForm'    => true,
    ]));

    // and render if allowed
    if ($collection->itemAt('renderForm')) { ?>
        <div class="box box-primary borderless">
            <div class="box-header">
                <h3 class="box-title"><?php echo $controller->t('Common'); ?></h3>
            </div>
            <div class="box-body">
                <?php
                /**
                 * This hook gives a chance to prepend content before the active form fields.
                 * Please note that from inside the action callback you can access all the controller view variables
                 * via {@CAttributeCollection $collection->controller->data}
                 * @since 1.3.3.1
                 */
                hooks()->doAction('before_active_form_fields', new CAttributeCollection([
                    'controller'    => $controller,
                    'form'          => $form,
                ])); ?>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($model, 'enabled'); ?>
                            <?php echo $form->dropDownList($model, 'enabled', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('enabled')); ?>
                            <?php echo $form->error($model, 'enabled'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($model, 'site_key'); ?>
                            <?php echo $form->textField($model, 'site_key', $model->fieldDecorator->getHtmlOptions('site_key')); ?>
                            <?php echo $form->error($model, 'site_key'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($model, 'secret_key'); ?>
                            <?php echo $form->textField($model, 'secret_key', $model->fieldDecorator->getHtmlOptions('secret_key')); ?>
                            <?php echo $form->error($model, 'secret_key'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($model, 'enabled_for_list_forms'); ?>
                            <?php echo $form->dropDownList($model, 'enabled_for_list_forms', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('enabled_for_list_forms')); ?>
                            <?php echo $form->error($model, 'enabled_for_list_forms'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
			                <?php echo $form->labelEx($model, 'enabled_for_block_email_form'); ?>
			                <?php echo $form->dropDownList($model, 'enabled_for_block_email_form', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('enabled_for_block_email_form')); ?>
			                <?php echo $form->error($model, 'enabled_for_block_email_form'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group">
			                <?php echo $form->labelEx($model, 'enabled_for_registration'); ?>
			                <?php echo $form->dropDownList($model, 'enabled_for_registration', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('enabled_for_registration')); ?>
			                <?php echo $form->error($model, 'enabled_for_registration'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
			                <?php echo $form->labelEx($model, 'enabled_for_login'); ?>
			                <?php echo $form->dropDownList($model, 'enabled_for_login', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('enabled_for_login')); ?>
			                <?php echo $form->error($model, 'enabled_for_login'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
			                <?php echo $form->labelEx($model, 'enabled_for_forgot'); ?>
			                <?php echo $form->dropDownList($model, 'enabled_for_forgot', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('enabled_for_forgot')); ?>
			                <?php echo $form->error($model, 'enabled_for_forgot'); ?>
                        </div>
                    </div>
                </div>
                <hr />
                <div class="box-header">
                    <div class="pull-left">
                        <h3 class="box-title"><?php echo $controller->t('Additional domains and key pairs'); ?></h3>
                    </div>
                    <div class="pull-right">
			            <?php BoxHeaderContent::make()
			                ->add(HtmlHelper::accessLink(IconHelper::make('create'), 'javascript:;', ['class' => 'btn btn-primary btn-flat btn-add-domains-keys-pair', 'title' => Yii::t('app', 'Create new')]))
			                ->render();
                        ?>
                    </div>
                    <div class="clearfix"><!-- --></div>
                </div>
                <div class="box-body domains-keys-pair-items">
		            <?php foreach ($model->getDomainsKeysPairs() as $index => $pair) {
                            $controller->renderPartial('_domains-keys-pair-template', [
                            'form'              => $form,
                            'domainsKeysPair'   => $pair,
                            'counter'           => $index,
                        ]);
                        } ?>
                </div>
                <?php
                /**
                 * This hook gives a chance to append content after the active form fields.
                 * Please note that from inside the action callback you can access all the controller view variables
                 * via {@CAttributeCollection $collection->controller->data}
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
                    <button type="submit" class="btn btn-primary btn-flat"><?php echo IconHelper::make('save') . t('app', 'Save changes'); ?></button>
                </div>
                <div class="clearfix"><!-- --></div>
            </div>
        </div>
        <?php
        $controller->endWidget();
    }
    /**
     * This hook gives a chance to append content after the active form.
     * Please note that from inside the action callback you can access all the controller view variables
     * via {@CAttributeCollection $collection->controller->data}
     * @since 1.3.3.1
     */
    hooks()->doAction('after_active_form', new CAttributeCollection([
        'controller'      => $controller,
        'renderedForm'    => $collection->itemAt('renderForm'),
    ]));
}
/**
 * This hook gives a chance to append content after the view file default content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->data}
 * @since 1.3.3.1
 */
hooks()->doAction('after_view_file_content', new CAttributeCollection([
    'controller'        => $controller,
    'renderedContent'   => $viewCollection->itemAt('renderContent'),
]));
?>
<script type="text/template" id="domains-keys-pair-item-template">
	<?php $controller->renderPartial('_domains-keys-pair-template', [
        'form'              => $form,
        'domainsKeysPair'   => $domainsKeysPair,
        'counter'           => '{COUNTER}',
    ]); ?>
</script>
