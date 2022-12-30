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
 * @since 2.1.4
 */

/** @var Controller $controller */
$controller = controller();

/** @var array $timelineItems */
$timelineItems = $controller->getData('timelineItems');

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
    <hr />
    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <h3 class="box-title"><?php echo IconHelper::make('fa-clock-o') . t('dashboard', 'Recent activity'); ?></h3>
            </div>
            <div class="pull-right"></div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-body">
            <ul class="timeline">
                <?php foreach ($timelineItems as $item) { ?>
                    <li class="time-label">
                        <span class="flat bg-red"><?php echo html_encode((string)$item['date']); ?></span>
                    </li>
                    <?php foreach ($item['items'] as $itm) { ?>
                        <li>
                            <i class="fa fa-user bg-blue"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="fa fa-clock-o"></i> <?php echo html_encode((string)$itm['time']); ?></span>
                                <h3 class="timeline-header"><a href="<?php echo html_encode((string)$itm['customerUrl']); ?>"><?php echo html_encode((string)$itm['customerName']); ?></a></h3>
                                <div class="timeline-body">
                                    <?php echo html_purify((string)$itm['message']); ?>
                                </div>
                            </div>
                        </li>
                    <?php } ?>
                <?php } ?>
                <li>
                    <i class="fa fa-clock-o bg-gray"></i>
                </li>
            </ul>
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
