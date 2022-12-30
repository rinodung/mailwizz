<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PagesController
 *
 * Handles the actions for pages related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.5
 */

class PagesController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('pages.js')]);
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, slug',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all available pages
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $page = new Page('search');
        $page->unsetAttributes();
        $page->attributes = (array)request()->getQuery($page->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('pages', 'View pages'),
            'pageHeading'     => t('pages', 'View pages'),
            'pageBreadcrumbs' => [
                t('pages', 'Pages') => createUrl('pages/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('page'));
    }

    /**
     * Create a new page
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $page = new Page();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($page->getModelName(), []))) {
            $page->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$page->getModelName()]['content'])) {
                $page->content = (string)ioFilter()->purify($post[$page->getModelName()]['content']);
            }

            if (!$page->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'page'   => $page,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['pages/index']);
            }
        }

        $page->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('pages', 'Create new page'),
            'pageHeading'     => t('pages', 'Create new page'),
            'pageBreadcrumbs' => [
                t('pages', 'Pages') => createUrl('pages/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('page'));
    }

    /**
     * Update existing page
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $page = Page::model()->findByPk((int)$id);

        if (empty($page)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($page->getModelName(), []))) {
            $page->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$page->getModelName()]['content'])) {
                $page->content = (string)ioFilter()->purify($post[$page->getModelName()]['content']);
            }

            if (!$page->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'page'      => $page,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['pages/index']);
            }
        }

        $page->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('pages', 'Update page'),
            'pageHeading'     => t('pages', 'Update page'),
            'pageBreadcrumbs' => [
                t('pages', 'Pages') => createUrl('pages/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('page'));
    }

    /**
     * Delete an existing page
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
        $page = Page::model()->findByPk((int)$id);

        if (empty($page)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $page->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['pages/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $page,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Generate the slug for an page based on the page title
     *
     * @return void
     * @throws CException
     */
    public function actionSlug()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['pages/index']);
        }

        $page = new Page();
        $page->page_id = (int)request()->getPost('page_id');
        $page->slug    = (string)request()->getPost('string');
        $page->slug    = $page->generateSlug();

        $this->renderJson([
            'result' => 'success',
            'slug'   => $page->slug,
        ]);
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
        if (!in_array($event->params['attribute'], ['content'])) {
            return;
        }

        $options = [];
        if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
            $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
        }

        $options['id']     = CHtml::activeId($event->sender->owner, $event->params['attribute']);
        $options['height'] = 500;

        $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
    }
}
