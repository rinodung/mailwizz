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
 */

/** @var Controller $controller */
$controller = controller();

/** @var PaymentGatewayPaypalExtCommon $model */
$model = $controller->getData('model');

/** @var PricePlanOrder $order */
$order = $controller->getData('order');

/** @var CustomerCompany|null $company */
$company = $controller->getData('company');

/** @var ExtensionInit $extension */
$extension = $controller->getData('extension');

/** @var string $cancelUrl */
$cancelUrl = (string)$controller->getData('cancelUrl');

/** @var string $returnUrl */
$returnUrl = (string)$controller->getData('returnUrl');

/** @var string $notifyUrl */
$notifyUrl = (string)$controller->getData('notifyUrl');

/** @var string $customVars */
$customVars = (string)$controller->getData('customVars');

echo CHtml::form($model->getModeUrl(), 'post', [
    'id'         => 'paypal-hidden-form',
    'data-order' => createUrl('price_plans/order'),
]);
echo CHtml::hiddenField('business', $model->email);
echo CHtml::hiddenField('cmd', '_xclick');
echo CHtml::hiddenField('item_name', t('price_plans', 'Price plan') . ': ' . $order->plan->name);
echo CHtml::hiddenField('item_number', $order->plan->getUid());
echo CHtml::hiddenField('amount', round($order->total, 2));
echo CHtml::hiddenField('currency_code', $order->currency->code);
echo CHtml::hiddenField('no_shipping', 1);
echo CHtml::hiddenField('cancel_return', $cancelUrl);
echo CHtml::hiddenField('return', $returnUrl);
echo CHtml::hiddenField('notify_url', $notifyUrl);
echo CHtml::hiddenField('custom', $customVars);

// 1.3.9.1
echo CHtml::hiddenField('email', $order->customer->email);
echo CHtml::hiddenField('address1', !empty($company) ? $company->address_1 : '');
echo CHtml::hiddenField('address2', !empty($company) ? $company->address_2 : '');
echo CHtml::hiddenField('city', !empty($company) ? $company->city : '');
echo CHtml::hiddenField('state', !empty($company) ? (!empty($company->zone_id) ? $company->zone->name : $company->zone_name) : '');
echo CHtml::hiddenField('zip', !empty($company) ? $company->zip_code : '');
echo CHtml::hiddenField('country', !empty($company) && !empty($company->country_id) ? $company->country->name : '');
?>
<p class="text-muted well well-sm no-shadow" style="margin-top: 10px;">
    Paypal - www.paypal.com <br />
    <?php echo $extension->t('You will be redirected to pay securely on paypal.com official website!'); ?>
</p>
<p><button class="btn btn-success pull-right"><i class="fa fa-credit-card"></i> <?php echo t('price_plans', 'Submit payment'); ?></button></p>

<?php echo CHtml::endForm(); ?>
