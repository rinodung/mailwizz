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

/** @var PricePlan[] $pricePlans */
$pricePlans = (array)$controller->getData('pricePlans');

/** @var Customer $customer */
$customer = $controller->getData('customer');

/** @var array $paymentMethods */
$paymentMethods = (array)$controller->getData('paymentMethods');

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
            <div class="pull-left">
                <h3 class="box-title">
                    <?php echo IconHelper::make('glyphicon-credit-card') . t('price_plans', 'Available price plans'); ?>
                </h3>
            </div>
            <div class="pull-right"></div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-body">
            <div class="row">
                <?php foreach ($pricePlans as $index => $plan) { ?>
                    <div class="col-lg-4 price-plan-box-wrapper">
                        <div class="box box-<?php echo $plan->group_id == $customer->group_id ? 'primary' : 'success'; ?> price-plan-box borderless">
                            <div class="box-heading">
                                <h3 class="box-title"><?php echo $plan->name; ?></h3>
                                <div class="box-tools pull-right">
                                    <?php if ($plan->getIsRecommended()) { ?>
                                        <span class="badge bg-<?php echo $plan->group_id == $customer->group_id ? 'blue' : 'red'; ?>"><?php echo t('app', 'Recommended'); ?></span>
                                    <?php } ?>
                                    <span class="badge bg-<?php echo $plan->group_id == $customer->group_id ? 'blue' : 'red'; ?>"><?php echo $plan->getFormattedPrice(); ?></span>
                                </div>
                            </div>
                            <div class="box-body">
                                <p> <?php echo $plan->description; ?> </p>
                            </div>
                            <div class="box-footer">
                                <div class="pull-right">
                                    <a class="btn btn-<?php echo $plan->group_id == $customer->group_id ? 'primary' : 'success'; ?> btn-flat btn-do-order" href="#payment-options-modal" data-toggle="modal" data-plan-uid="<?php echo $plan->getUid(); ?>">
                                        <?php echo $plan->group_id == $customer->group_id ? t('app', 'Your current plan, renew it') : t('app', 'Purchase'); ?>
                                    </a>
                                </div>
                                <div class="clearfix"><!-- --></div>
                            </div>
                        </div>
                    </div>
                    <?php if (($index + 1) % 3 === 0) { ?><div class="clearfix"><!-- --></div><?php } ?>
                <?php } ?>
                <div class="clearfix"><!-- --></div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="payment-options-modal" tabindex="-1" role="dialog" aria-labelledby="payment-options-modal-label" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title"><?php echo t('price_plans', 'Select payment method'); ?></h4>
            </div>
            <div class="modal-body">
                <?php
                echo CHtml::form(['price_plans/payment'], 'post', ['id' => 'payment-options-form']);
                echo CHtml::hiddenField('plan_uid');
                ?>
                <div class="form-group">
                    <?php echo CHtml::label(t('price_plans', 'Payment gateway selection'), 'payment_gateway'); ?>
                    <?php echo CHtml::dropDownList('payment_gateway', '', $paymentMethods, ['class' => 'form-control']); ?>
                 </div>
                <?php echo CHtml::endForm(); ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
              <button type="button" class="btn btn-primary btn-flat" onclick="$('#payment-options-form').submit();"><?php echo t('price_plans', 'Proceed to payment'); ?></button>
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
