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

/** @var Lists $list */
$list = $controller->getData('list');

/** @var CPagination $pages */
$pages = $controller->getData('pages');

/** @var array $columns */
$columns = (array)$controller->getData('columns');

/** @var array $rows */
$rows = (array)$controller->getData('rows');

/** @var int $count */
$count = (int)$controller->getData('count');

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
    <div class="table-responsive subscribers-table">
        <h5><?php echo t('list_segments', 'This segment contains {count} subscribers', ['{count}' => $count]); ?></h5>
        <table class="table table-hover">
            <thead>
                <tr>
                    <?php foreach ($columns as $column) { ?>
                    <th><?php echo $column['label']; ?></th>
                    <?php } ?>
                </tr>
            </thead>
            <?php if (count($rows) > 0) { ?>
            <tbody>
                <?php foreach ($rows as $row) { ?>
                <tr>
                    <?php foreach ($row['columns'] as $column) { ?>
                    <td><?php echo (string)$column; ?></td>
                    <?php } ?>    
                </tr>
                <?php } ?>
            </tbody>
            <?php } else { ?>
            <tbody>
                <tr>
                    <td colspan="<?php echo count($columns); ?>" align="center">
                        <?php echo t('list_segments', 'Sorry, but there are no subscribers matching your segment.'); ?>
                    </td>
                </tr>
            </tbody>
            <?php } ?>
        </table>
    </div>    
    <div class="clearfix" style="height: 10px;"><!-- --></div>
    <div class="row-fluid">
        <div class="pull-right">
            <?php $controller->widget('CLinkPager', [
                'pages'         => $pages,
                'htmlOptions'   => ['id' => 'subscribers-pagination', 'class' => 'pagination'],
                'header'        => false,
                'cssFile'       => false,
            ]); ?>
        </div>
        <div class="clearfix"><!-- --></div>
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
