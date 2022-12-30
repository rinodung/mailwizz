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

/** @var string $subscribeForm */
$subscribeForm = (string)$controller->getData('subscribeForm');

/** @var Lists $list */
$list = $controller->getData('list');

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
                <?php $controller->widget('customer.components.web.widgets.MailListSubNavWidget', [
                    'list' => $list,
                ]); ?>
            </div>
        </div>
        <ul class="nav nav-tabs list-forms-nav" style="border-bottom: 0px;">
            <li class="active"><a href="#subscribe-form"><?php echo t('list_forms', 'Subscribe form'); ?></a></li>
            <li class="inactive"><a href="#unsubscribe-form"><?php echo t('list_forms', 'Unsubscribe form'); ?></a></li>
        </ul>
        <div class="box-body">
            <div class="form-container" id="subscribe-form">
                <div class="box box-primary borderless">
                    <div class="box-header">
                        <div class="pull-left">
                            <h3 class="box-title"><?php echo t('list_forms', 'Subscribe form'); ?></h3>
                        </div>
                        <div class="pull-right"></div>
                        <div class="clearfix"><!-- --></div>
                    </div>
                    <div class="box-body">
                        <div class="form-group">

                            <textarea class="form-control" rows="20"><?php echo $subscribeForm; ?></textarea>
                            <hr />
                            <h5><?php echo t('list_forms', 'Iframe version'); ?></h5>
                            <textarea class="form-control" rows="3">
                        <iframe src="<?php echo apps()->getAppUrl('frontend', 'lists/' . $list->list_uid . '/subscribe', true); ?>?output=embed&width=400&height=400" width="400" height="400" frameborder="0" scrolling="no"></iframe>
                    </textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-container" id="unsubscribe-form" style="display: none;">
                <div class="box box-primary borderless">
                    <div class="box-header">
                        <div class="pull-left">
                            <h3 class="box-title"><?php echo t('list_forms', 'Unsubscribe form'); ?></h3>
                        </div>
                        <div class="pull-right"></div>
                        <div class="clearfix"><!-- --></div>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                    <textarea class="form-control" rows="20">
<form action="<?php echo apps()->getAppUrl('frontend', 'lists/' . $list->list_uid . '/unsubscribe', true); ?>" method="post" accept-charset="utf-8" target="_blank">

    <div class="form-group">
        <label>Email <span class="required">*</span></label>
        <input type="text" class="form-control" name="EMAIL" placeholder="<?php echo t('list_forms', 'Please type your email address'); ?>" value="" required />
    </div>

    <div class="clearfix"><!-- --></div>
    <div class="actions pull-right">
        <button type="submit" class="btn btn-primary btn-flat"><?php echo t('list_forms', 'Unsubscribe'); ?></button>
    </div>
    <div class="clearfix"><!-- --></div>

</form>
                    </textarea>
                            <hr />
                            <h5><?php echo t('list_forms', 'Iframe version'); ?></h5>
                            <textarea class="form-control" rows="3">
<iframe src="<?php echo apps()->getAppUrl('frontend', 'lists/' . $list->list_uid . '/unsubscribe', true); ?>?output=embed&width=400&height=200" width="400" height="200" frameborder="0" scrolling="no"></iframe>
                    </textarea>
                        </div>
                    </div>
                </div>
            </div>
            <hr />
            <div class="callout callout-info">
                <?php
                $text = 'Please note, you will have to style the forms below to match the place where you embed them.<br />
        You can create better forms by using the <a href="{sdkHref}" target="_blank">PHP-SDK</a> and connect to the provided api.';
                echo t('list_forms', StringHelper::normalizeTranslationString($text), [
                    '{sdkHref}' => hooks()->applyFilters('sdk_download_url', 'https://github.com/ems-api/php-client'),
                ]); ?>
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
