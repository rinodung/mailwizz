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

/** @var PricePlanOrder $order */
/** @var PricePlan $pricePlan */
/** @var OptionMonetizationInvoices $invoiceOptions */

?>
<!DOCTYPE html>
<html dir="<?php echo $this->htmlOrientation; ?>" lang="<?php echo app()->language; ?>">
<head>
    <meta charset="<?php echo app()->charset; ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <title><?php echo t('app', 'Invoice'); ?></title>
    <style>
        * {
            font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
            font-size: 14px;
        }
        body {
            margin-left: 5%;
            margin-right: 5%;
        }
        table.table-responsive {width: 100%;}
        table.mtop {margin-top: 50px!important;}
        h2 {
            font-size: 40px;
            padding: 0 0 0 0;
            margin: 0 0 0 0;
        }
        h3 {
            font-size: 20px;
            padding: 0 0 0 0;
            margin: 0 0 10px 0;
        }
        .text-center {text-align: center;}
        .text-right {text-align: right;}
        .text-left {text-align: left;}
        .text-color {color: <?php echo '#' . html_encode($invoiceOptions->color_code); ?>}

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .invoice-details td, .invoice-details th {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .invoice-details tr:nth-child(even){background-color: #f2f2f2;}

        .invoice-details tr:hover {background-color: #ddd;}

        .invoice-details th {
            padding-top: 12px;
            padding-bottom: 12px;
        }
    </style>
</head>
<body class="<?php echo $this->bodyClasses; ?>">
    <table cellspacing="0" cellpadding="10" class="table-responsive">
        <tr>
            <td width="20%">
                <?php if ($logoPathEncoded = $invoiceOptions->getLogoPathBase64Encoded()) { ?>
                    <img src="<?php echo $logoPathEncoded; ?>" />
                <?php } ?>
            </td>
            <td class="text-center"><h2><?php echo strtoupper(t('app', 'Invoice')); ?></h2></td>
            <td width="20%" align="right" style="white-space: nowrap;">
                <table cellspacing="0" cellpadding="0" class="table-responsive">
                    <tr>
                        <td><span class="text-color"><b><?php echo t('app', 'Order no'); ?>: </b></span></td>
                        <td><?php echo html_encode($order->getUid()); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-color"><b><?php echo t('app', 'Invoice no'); ?>: </b></span></td>
                        <td><?php echo html_encode($order->getNumber()); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-color"><b><?php echo t('app', 'Billing date'); ?>: </b></span></td>
                        <td><?php echo html_encode(preg_replace('/(,)?\s.*/', '', $order->dateAdded)); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-color"><b><?php echo t('app', 'Due date'); ?>: </b></span></td>
                        <td><?php echo html_encode(preg_replace('/(,)?\s.*/', '', $order->dateAdded)); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-color"><b><?php echo t('app', 'Status'); ?>: </b></span></td>
                        <td><?php echo html_encode($order->getStatusName()); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-color"><b><?php echo t('app', 'Paid'); ?>: </b></span></td>
                        <td><?php echo $order->getIsComplete() ? t('app', 'Yes') : t('app', 'No'); ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <hr />
    <table cellspacing="0" cellpadding="10" class="table-responsive">
        <tr>
            <td width="50%" align="left" valign="top">
                <h3><strong class="text-color" style="font-size: 16px;"><?php echo t('app', 'Payment from'); ?></strong></h3>
                <?php echo $order->getHtmlPaymentFrom("\n"); ?>
            </td>
            <td width="50%" align="right" valign="top">
                <h3><strong class="text-color" style="font-size: 16px;"><?php echo t('app', 'Payment to'); ?></strong></h3>
                <?php echo $order->getHtmlPaymentTo("\n"); ?>
            </td>
        </tr>
    </table>
    <table cellspacing="0" cellpadding="10" class="table-responsive mtop invoice-details">
        <thead>
            <tr>
                <th width="30%" class="text-left text-color" style="font-size: 16px; white-space: nowrap;"><?php echo t('app', 'Plan'); ?></th>
                <th width="25%" class="text-center text-color" style="font-size: 16px; white-space: nowrap;"><?php echo t('app', 'Quantity'); ?></th>
                <th width="20%" class="text-right text-color" style="font-size: 16px; white-space: nowrap;"><?php echo t('app', 'Unit price'); ?></th>
                <th width="25%" class="text-right text-color" style="font-size: 16px; white-space: nowrap;"><?php echo t('app', 'Amount'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-size: 14px; white-space: nowrap;">
                    <?php echo html_encode($pricePlan->name); ?><br />
                    <small style="font-size: 11px; white-space: nowrap;"><?php echo html_encode(StringHelper::truncateLength($pricePlan->description, 50)); ?></small>
                </td>
                <td style="font-size: 14px; white-space: nowrap;" class="text-center">x <span class="badge">1</span></td>
                <td style="font-size: 14px; white-space: nowrap;" class="text-right"><?php echo html_encode($pricePlan->getFormattedPrice()); ?></td>
                <td style="font-size: 14px; white-space: nowrap;" class="text-right"><?php echo html_encode($pricePlan->getFormattedPrice()); ?></td>
            </tr>
            <tr>
                <td width="30%">&nbsp; </td>
                <td width="25%">&nbsp; </td>
                <td width="20%" class="text-right">
                    <?php echo t('app', 'Subtotal'); ?><br />
                    <?php echo t('app', 'Discount'); ?><br />
                    <?php echo t('app', 'Tax'); ?> <small><?php echo  html_encode($order->getFormattedTaxPercent()); ?></small><br />
                    <?php echo t('app', 'Total'); ?><br />
                    <strong><?php echo t('app', 'Total due'); ?></strong>
                </td>
                <td width="25%" class="text-right">
                    <?php echo html_encode($order->getFormattedSubtotal()); ?><br />
                    <?php echo html_encode($order->getFormattedDiscount()); ?><br />
                    <?php echo html_encode($order->getFormattedTaxValue()); ?><br />
                    <?php echo html_encode($order->getFormattedTotal()); ?><br />
                    <strong><?php echo html_encode($order->getFormattedTotalDue()); ?></strong>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="table-responsive mtop">
        <tr>
            <td class="text-left">
                <strong><?php echo t('app', 'Extra notes'); ?></strong><br />
                <hr />
            </td>
        </tr>
        <tr>
            <td class="text-left">
                <?php echo html_encode((string)$invoiceOptions->notes); ?><br />
            </td>
        </tr>
    </table>
</body>
</html>