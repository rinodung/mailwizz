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
 * @since 1.4.5
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

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

    <?php
    $controller->widget('customer.components.web.widgets.campaign-tracking.CampaignGeoOpensWidget', [
        'headingLeft'   => BoxHeaderContent::make(BoxHeaderContent::LEFT)->add('<h3 class="box-title">' . IconHelper::make('glyphicon-map-marker') . html_encode((string)$pageHeading) . '</h3>'),
        'headingRight'  => BoxHeaderContent::make(BoxHeaderContent::RIGHT)->add(CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['campaigns_geo_opens/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')])),
        'campaign'      => null,
    ]);
    ?>
    
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
