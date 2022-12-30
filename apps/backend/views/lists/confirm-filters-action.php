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
 * @since 1.3.5.2
 */

/** @var Controller $controller */
$controller = controller();

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
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('fa-ban') . t('app', 'Confirm action') . '</h3>')
                    ->render();
                ?>
            </div>
            <div class="pull-right"></div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-body">

            <?php echo CHtml::form(); ?>
            <div class="alert alert-danger alert-dismissable">
                <strong>
			        <?php echo t('lists', 'Are you still sure you want to complete this action?'); ?>
                </strong>
            </div>
            <div class="clearfix"><!-- --></div>
            <div class="pull-right">
                <button class="btn btn-danger btn-large btn-flat" type="submit" name="confirm" value="true"><?php echo t('lists', 'Yes, i confirm!'); ?></button>
                <a class="btn btn-primary btn-large btn-flat" href="<?php echo createUrl('lists/all_subscribers'); ?>"><?php echo t('app', 'Cancel'); ?></a>
            </div>
            <?php echo CHtml::endForm(); ?>
            
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
