<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List_page_typeController
 *
 * Handles the actions for list page types related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class List_page_typeController extends Controller
{
    /**
     * List all the available page typs
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $pageType = new ListPageType('search');
        $pageType->unsetAttributes();
        $pageType->attributes = (array)request()->getQuery($pageType->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_page_types', 'List page types'),
            'pageHeading'     => t('list_page_types', 'List page types'),
            'pageBreadcrumbs' => [
                t('list_page_types', 'List page types') => createUrl('list_page_type/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('pageType'));
    }

    /**
     * Update certain page type
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $pageType = ListPageType::model()->findByPk((int)$id);

        if (empty($pageType)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($pageType->getModelName(), []))) {
            $pageType->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$pageType->getModelName()]['content'])) {
                $rawContent = $post[$pageType->getModelName()]['content'];
                if ($pageType->full_html === ListPageType::TEXT_YES) {
                    $pageType->content = (string)$rawContent;
                } else {
                    $pageType->content = (string)ioFilter()->purify($rawContent);
                }
            }
            if (isset($post[$pageType->getModelName()]['description'])) {
                $pageType->description = (string)ioFilter()->purify($post[$pageType->getModelName()]['description']);
            }
            if (!$pageType->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'pageType'  => $pageType,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['list_page_type/update', 'id' => $pageType->type_id]);
            }
        }

        // append the wysiwyg editor
        $pageType->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];
        $tags = $pageType->getAvailableTags();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_page_types', 'Update page type'),
            'pageHeading'     => t('list_page_types', 'Update page type'),
            'pageBreadcrumbs' => [
                 t('list_page_types', 'List page types') => createUrl('list_page_type/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('pageType', 'tags'));
    }

    /**
     * Callback method to set the editor options
     *
     * @param CEvent $event
     *
     * @return void
     */
    public function _setupEditorOptions(CEvent $event)
    {
        if (!in_array($event->params['attribute'], ['content', 'description'])) {
            return;
        }

        $options = [];
        if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
            $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
        }
        $options['id'] = CHtml::activeId($event->sender->owner, $event->params['attribute']);

        if ($event->params['attribute'] == 'content' && $event->sender->owner->full_html === ListPageType::TEXT_YES) {
            $options['fullPage']        = true;
            $options['allowedContent']  = true;
            $options['height']          = 500;
        }

        if ($event->params['attribute'] == 'description') {
            $options['toolbar'] = 'Simple';
            $options['height']  = 50;
        }

        $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
    }
}
