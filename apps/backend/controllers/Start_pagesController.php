<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Start_pagesController
 *
 * Handles the actions for list page types related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.9.2
 */

class Start_pagesController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageStyle(['src'  => apps()->getAppUrl('frontend', 'assets/js/colorpicker/css/bootstrap-colorpicker.css', false, true)]);
        $this->addPageScript(['src' => apps()->getAppUrl('frontend', 'assets/js/colorpicker/js/bootstrap-colorpicker.min.js', false, true)]);
        $this->addPageScript(['src' => AssetsUrl::js('start-pages.js')]);
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all the available page indexes
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $model = new StartPage('search');
        $model->unsetAttributes();
        $model->attributes = (array)request()->getQuery($model->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('start_pages', 'Start pages'),
            'pageHeading'     => t('start_pages', 'Start pages'),
            'pageBreadcrumbs' => [
                t('start_pages', 'Start pages') => createUrl('start_pages/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('model'));
    }

    /**
     * Create page index
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $model = new StartPage();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$model->getModelName()]['content'])) {
                $rawContent = (string)$post[$model->getModelName()]['content'];
                $model->content = (string)ioFilter()->purify($rawContent);
            }
            if (!$model->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'model'     => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['start_pages/update', 'id' => $model->page_id]);
            }
        }

        // append the wysiwyg editor
        $model->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('start_pages', 'Create new page'),
            'pageHeading'     => t('start_pages', 'Create new page'),
            'pageBreadcrumbs' => [
                t('start_pages', 'Start pages') => createUrl('start_pages/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('model'));
    }

    /**
     * Update page index
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        /** @var StartPage|null $model */
        $model = StartPage::model()->findByPk((int)$id);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$model->getModelName()]['content'])) {
                $rawContent = (string)$post[$model->getModelName()]['content'];
                $model->content = (string)ioFilter()->purify($rawContent);
            }
            if (!$model->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'model'     => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['start_pages/update', 'id' => $model->page_id]);
            }
        }

        // append the wysiwyg editor
        $model->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('start_pages', 'Update page'),
            'pageHeading'     => t('start_pages', 'Update page'),
            'pageBreadcrumbs' => [
                t('start_pages', 'Start pages') => createUrl('start_pages/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('model'));
    }

    /**
     * Delete existing page index
     *
     * @param int $id
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($id)
    {
        /** @var StartPage|null $model */
        $model = StartPage::model()->findByPk((int)$id);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $model->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['start_pages/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $model,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Callback method to set the editor options
     *
     * @return void
     * @param CEvent $event
     */
    public function _setupEditorOptions(CEvent $event)
    {
        if (!in_array($event->params['attribute'], ['content'])) {
            return;
        }

        $options = [];
        if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
            $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
        }
        $options['id'] = CHtml::activeId($event->sender->owner, $event->params['attribute']);

        if ($event->params['attribute'] == 'content') {
            $options['height'] = 300;
        }

        $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
    }
}
