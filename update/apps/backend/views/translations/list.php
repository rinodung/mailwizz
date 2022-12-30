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
 * @since 2.0
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var TranslationSourceMessage $translation */
$translation = $controller->getData('translation');

/** @var Language $model */
$model = $controller->getData('model');

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->add('renderContent', false)}
 * in order to stop rendering the default content.
 * @since 1.3.3.1
 */
hooks()->doAction('views_before_content', $viewCollection = new CAttributeCollection([
    'controller'    => $controller,
    'renderContent' => true,
]));

// and render if allowed
if ($viewCollection->itemAt('renderContent')) { ?>
    <div class="box box-primary borderless">
        <div class="box-header">
    		<div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('envelope') . html_encode((string)$pageHeading) . '</h3>')
                    ->render();
                ?>
            </div>
    		<div class="pull-right">
                <?php
                BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->add($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $translation, 'columns' => ['category', 'message', 'translation']], true))
                    ->add(HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), createUrl('translations/index', ['language_id' => $model->language_id]), ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
                    ->add(HtmlHelper::accessLink(IconHelper::make('save') . t('app', 'Save all'), createUrl('translations/save'), ['class' => 'btn btn-primary btn-flat save-all', 'title' => t('app', 'Save all')]))
                    ->render();
                ?>
    		</div>
    	</div>
        <div class="box-body">
            <div class="table-responsive">
            <?php
            /**
             * This hook gives a chance to prepend content or to replace the default grid view content with a custom content.
             * Please note that from inside the action callback you can access all the controller view
             * variables via {@CAttributeCollection $collection->controller->getData()}
             * In case the content is replaced, make sure to set {@CAttributeCollection $collection->itemAt('renderGrid')} to false
             * in order to stop rendering the default content.
             * @since 1.3.3.1
             */
            hooks()->doAction('views_before_grid', $collection = new CAttributeCollection([
                'controller'   => $controller,
                'renderGrid'   => true,
            ]));

            // and render if allowed
            if ($collection->itemAt('renderGrid')) {
                $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                    'ajaxUrl'           => createUrl($controller->getRoute(), ['language_id' => $model->language_id]),
                    'id'                => $translation->getModelName() . '-grid',
                    'dataProvider'      => $translation->search(),
                    'filter'            => $translation,
                    'filterPosition'    => 'body',
                    'filterCssClass'    => 'grid-filter-cell',
                    'itemsCssClass'     => 'table table-hover',
                    'selectableRows'    => 0,
                    'enableSorting'     => false,
                    'cssFile'           => false,
                    'pagerCssClass'     => 'pagination pull-right',
                    'pager'             => [
                        'class'         => 'CLinkPager',
                        'cssFile'       => false,
                        'header'        => false,
                        'htmlOptions'   => ['class' => 'pagination'],
                    ],
                    'columns' => hooks()->applyFilters('grid_view_columns', [
                        [
                            'name'   => 'category',
                            'value'  => '$data->category',
                        ],
                        [
                            'name'        => 'message',
                            'value'       => '$data->message',
                            'htmlOptions' => ['style' => 'max-width:250px;word-wrap:break-word;'],
                        ],
                        [
                            'name'        => 'translation',
                            'value'       => '$data->getTranslationInputField((int)request()->getQuery("language_id", 0))',
                            'type'        => 'raw',
                            'htmlOptions' => ['style' => 'max-width: 300px'],
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'footer'    => $translation->paginationOptions->getGridFooterPagination(),
                            'buttons'   => [
                                'save' => [
                                    'label'     => IconHelper::make('save'),
                                    'url'       => 'createUrl("translations/save")',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Save'), 'class' => 'btn btn-primary btn-flat save-one'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("translations/save")',
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          => '{save}',
                        ],

                    ], $controller),
                ], $controller));
            }
            /**
             * This hook gives a chance to append content after the grid view content.
             * Please note that from inside the action callback you can access all the controller view
             * variables via {@CAttributeCollection $collection->controller->getData()}
             * @since 1.3.3.1
             */
            hooks()->doAction('views_after_grid', new CAttributeCollection([
                'controller'   => $controller,
                'renderedGrid' => $collection->itemAt('renderGrid'),
            ]));
            ?>
            </div>
            <div class="clearfix"><!-- --></div>
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
hooks()->doAction('views_after_content', new CAttributeCollection([
    'controller'        => $controller,
    'renderedContent'   => $viewCollection->itemAt('renderContent'),
]));
