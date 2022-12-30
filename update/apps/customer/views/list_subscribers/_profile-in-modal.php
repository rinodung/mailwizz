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
 * @since 1.3.9.8
 */

/** @var Controller $controller */
$controller = controller();

/** @var Lists $list */
$list = $controller->getData('list');

/** @var ListSubscriber $subscriber */
$subscriber = $controller->getData('subscriber');

/** @var string $subscriberName */
$subscriberName = (string)$controller->getData('subscriberName');

/** @var ListSubscriberOptinHistory|null $optinHistory */
$optinHistory = $controller->getData('optinHistory');

/** @var ListSubscriberOptoutHistory|null $optoutHistory */
$optoutHistory = $controller->getData('optoutHistory');

?>
<div class="">
    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
        <div class="img-avatar">
            <img src="<?php echo html_encode((string)$subscriber->getAvatarUrl()); ?>" width="200" height="200" alt="" />
        </div>
    </div>
    <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
        <div class="name-avatar">
            <div class="pull-left">
                <span><?php echo IconHelper::make('fa-user') . ' ' . html_encode((string)$subscriberName); ?></span>
            </div>
            <div class="pull-right">
                <?php echo IconHelper::make('campaign') . ' ' . html_encode((string)$subscriber->getDisplayEmail()); ?>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="list-wrapper">
            <ul class="custom-list">
                <li><span class="cl-span"><?php echo $subscriber->getAttributeLabel('date_added'); ?></span><span class="cl-span"><?php echo $subscriber->dateTimeFormatter->getDateAdded(); ?></span></li>
                <li><span class="cl-span"><?php echo $subscriber->getAttributeLabel('last_updated'); ?></span><span class="cl-span"><?php echo $subscriber->dateTimeFormatter->getLastUpdated(); ?></span></li>
                <li><span class="cl-span"><?php echo $subscriber->getAttributeLabel('ip_address'); ?></span><span class="cl-span"><?php echo $subscriber->ip_address; ?></span></li>
                <li><span class="cl-span"><?php echo $subscriber->getAttributeLabel('source'); ?></span><span class="cl-span"><?php echo ucfirst($subscriber->source); ?></span></li>
                <li><span class="cl-span"><?php echo $subscriber->getAttributeLabel('status'); ?></span><span class="cl-span"><?php echo $subscriber->getStatusName(); ?></span></li>
                <?php if (!empty($optinHistory)) { ?>
                    <li><span class="cl-span"><?php echo $optinHistory->getAttributeLabel('optin_date'); ?></span><span class="cl-span"><?php echo $optinHistory->getOptinDate(); ?></span></li>
                    <li><span class="cl-span"><?php echo $optinHistory->getAttributeLabel('optin_ip'); ?></span><span class="cl-span"><?php echo $optinHistory->optin_ip; ?></span></li>
                    <li><span class="cl-span"><?php echo $optinHistory->getAttributeLabel('optin_user_agent'); ?></span><span class="cl-span"><input type="text" class="scroll" value="<?php echo $optinHistory->optin_user_agent; ?>" style="width: 280px" /></span></li>
                    <li><span class="cl-span"><?php echo $optinHistory->getAttributeLabel('confirm_date'); ?></span><span class="cl-span"><?php echo $optinHistory->getConfirmDate(); ?></span></li>
                    <li><span class="cl-span"><?php echo $optinHistory->getAttributeLabel('confirm_ip'); ?></span><span class="cl-span"><?php echo $optinHistory->confirm_ip; ?></span></li>
                    <li><span class="cl-span"><?php echo $optinHistory->getAttributeLabel('confirm_user_agent'); ?></span><span class="cl-span"><input type="text" class="scroll" value="<?php echo $optinHistory->confirm_user_agent; ?>" /></span></li>
                <?php } ?>
                <?php if (!empty($optoutHistory)) { ?>
                    <li><span class="cl-span"><?php echo $optoutHistory->getAttributeLabel('optout_date'); ?></span><span class="cl-span"><?php echo $optoutHistory->getOptoutDate(); ?></span></li>
                    <li><span class="cl-span"><?php echo $optoutHistory->getAttributeLabel('optout_ip'); ?></span><span class="cl-span"><?php echo $optoutHistory->optout_ip; ?></span></li>
                    <li><span class="cl-span"><?php echo $optoutHistory->getAttributeLabel('optout_user_agent'); ?></span><span class="cl-span"><input type="text" class="scroll" value="<?php echo $optoutHistory->optout_user_agent; ?>" style="width: 280px"/></span></li>
                    <li><span class="cl-span"><?php echo $optoutHistory->getAttributeLabel('confirm_date'); ?></span><span class="cl-span"><?php echo $optoutHistory->getConfirmDate(); ?></span></li>
                    <li><span class="cl-span"><?php echo $optoutHistory->getAttributeLabel('confirm_ip'); ?></span><span class="cl-span"><?php echo $optoutHistory->confirm_ip; ?></span></li>
                    <li><span class="cl-span"><?php echo $optoutHistory->getAttributeLabel('confirm_user_agent'); ?></span><span class="cl-span"><input type="text" class="scroll" value="<?php echo $optoutHistory->confirm_user_agent; ?>" /></span></li>
                <?php } ?>
            </ul>
        </div>
    </div>
</div>