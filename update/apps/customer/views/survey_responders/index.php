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
 * @since 1.7.8
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var Survey $survey */
$survey = $controller->getData('survey');

/** @var SurveyResponder $responder */
$responder = $controller->getData('responder');

/** @var array $rows */
$rows = (array)$controller->getData('rows');

/** @var array $displayToggleColumns */
$displayToggleColumns = (array)$controller->getData('displayToggleColumns');

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
                    ->add('<h3 class="box-title">' . IconHelper::make('fa-users') . html_encode((string)$pageHeading) . '</h3>')
                    ->render();
                ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->addIf($controller->widget('customer.components.web.widgets.GridViewToggleResponderColumns', ['model' => $responder, 'survey' => $survey, 'columns' => $displayToggleColumns], true), count($rows))
                    ->add(CHtml::link(IconHelper::make('create') . t('app', 'Create new'), ['survey_responders/create', 'survey_uid' => $survey->survey_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]))
                    ->add(CHtml::link(IconHelper::make('export') . t('app', 'Export'), ['survey_export/index', 'survey_uid' => $survey->survey_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Export')]))
                    ->add(CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['survey_responders/index', 'survey_uid' => $survey->survey_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
                    ->render();
                ?>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-body">

            <?php
            /**
             * This hook gives a chance to prepend content or to replace the default grid view content with a custom content.
             * Please note that from inside the action callback you can access all the controller view
             * variables via {@CAttributeCollection $collection->controller->getData()}
             * In case the content is replaced, make sure to set {@CAttributeCollection $collection->itemAt('renderGrid')} to false
             * in order to stop rendering the default content.
             * @since 1.3.3.1
             */
            hooks()->doAction('before_grid_view', $collection = new CAttributeCollection([
                'controller'    => $controller,
                'renderGrid'    => true,
            ]));

            /**
             * This widget renders default getting started page for this particular section.
             * @since 1.3.9.2
             */
            $controller->widget('common.components.web.widgets.StartPagesWidget', [
                'collection' => $collection,
                'enabled'    => !count($rows),
            ]);
            ?>

            <?php if ($collection->itemAt('renderGrid')) { ?>

            <div id="responders-wrapper">
                <?php $controller->renderPartial('_list'); ?>
            </div>

            <?php }

            /**
             * This hook gives a chance to append content after the grid view content.
             * Please note that from inside the action callback you can access all the controller view
             * variables via {@CAttributeCollection $collection->controller->getData()}
             * @since 1.3.3.1
             */
            hooks()->doAction('after_grid_view', new CAttributeCollection([
                'controller'    => $controller,
                'renderedGrid'  => $collection->itemAt('renderGrid'),
            ]));
            ?>

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
