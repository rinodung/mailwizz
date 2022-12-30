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
 * @since 1.3.9.5
 */

/** @var AccountController $controller */
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
    <div class="box box-primary borderless no-top-border">
        <div class="box-body">
            <div class="tabs-container">
                <?php
                echo $controller->renderTabs();
                /**
                 * This hook gives a chance to prepend content before the active form or to replace the default active form entirely.
                 * Please note that from inside the action callback you can access all the controller view variables
                 * via {@CAttributeCollection $collection->controller->getData()}
                 * In case the form is replaced, make sure to set {@CAttributeCollection $collection->add('renderForm', false)}
                 * in order to stop rendering the default content.
                 * @since 1.3.3.1
                 */
                hooks()->doAction('before_active_form', $collection = new CAttributeCollection([
                    'controller'    => $controller,
                    'renderForm'    => true,
                ]));

                // and render only if allowed
                if ($collection->itemAt('renderForm')) {
                    /** @var CActiveForm $form */
                    $form = $controller->beginWidget('CActiveForm');

                    /**
                     * This widget renders default getting started page for this particular section.
                     * @since 1.3.9.2
                     */
                    $controller->widget('common.components.web.widgets.StartPagesWidget', [
                        'collection' => new CAttributeCollection([
                            'controller' => $controller,
                            'renderGrid' => true,
                        ]),
                        'enabled'    => true,
                    ]);

                    $controller->endWidget();
                }
                /**
                 * This hook gives a chance to append content after the active form.
                 * Please note that from inside the action callback you can access all the controller view variables
                 * via {@CAttributeCollection $collection->controller->getData()}
                 * @since 1.3.3.1
                 */
                hooks()->doAction('after_active_form', new CAttributeCollection([
                    'controller'      => $controller,
                    'renderedForm'    => $collection->itemAt('renderForm'),
                ]));
                ?>
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
