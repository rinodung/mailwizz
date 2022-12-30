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

/** @var ExtensionInit $extension */
$extension = $controller->getData('extension');

/** @var PaymentGatewayOfflineExtCommon $model */
$model = $controller->getData('model');

echo CHtml::form(['price_plans/order'], 'post', []);
?>
<p class="text-muted well well-sm no-shadow" style="margin-top: 10px; padding: 16px">
    <?php echo $extension->t('Offline payment'); ?><br />
</p>
<p><?php echo nl2br($model->description); ?></p>
<p><button class="btn btn-success pull-right"><i class="fa fa-credit-card"></i> <?php echo t('price_plans', 'Place offline order'); ?></button></p>
<?php echo CHtml::endForm(); ?>
