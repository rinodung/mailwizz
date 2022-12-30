<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Common_email_templatesController
 *
 * Handles the actions for common email templates related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.6.2
 */

class Common_email_templatesController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('common-email-templates.js')]);
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, reinstall',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all available email templates
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $model = new CommonEmailTemplate('search');
        $model->unsetAttributes();
        $model->attributes = (array)request()->getQuery($model->getModelName(), []);

        $types = OptionEmailTemplate::getTypesList();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('common_email_templates', 'View email templates'),
            'pageHeading'     => t('common_email_templates', 'View email templates'),
            'pageBreadcrumbs' => [
                t('common_email_templates', 'Common email templates') => createUrl('common_email_templates/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('model', 'types'));
    }

    /**
     * Create a new template
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $model = new CommonEmailTemplate();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;
            $model->removable  = CommonEmailTemplate::TEXT_YES;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            $model->content = (string)$post[$model->getModelName()]['content'];
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
                $this->redirect(['common_email_templates/index']);
            }
        }

        $model->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('common_email_templates', 'Create new template'),
            'pageHeading'     => t('common_email_templates', 'Create new template'),
            'pageBreadcrumbs' => [
                t('common_email_templates', 'Common email templates') => createUrl('common_email_templates/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('model'));
    }

    /**
     * Update existing template
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $model = CommonEmailTemplate::model()->findByPk((int)$id);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            $model->content = (string)$post[$model->getModelName()]['content'];
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
                $this->redirect(['common_email_templates/update', 'id' => $model->template_id]);
            }
        }

        $model->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('common_email_templates', 'Update template'),
            'pageHeading'     => t('common_email_templates', 'Update template'),
            'pageBreadcrumbs' => [
                t('common_email_templates', 'Common email templates') => createUrl('common_email_templates/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('model'));
    }

    /**
     * Reinstall core templates
     *
     * @return void
     * @throws CException
     */
    public function actionReinstall()
    {
        CommonEmailTemplate::reinstallCoreTemplates();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            $redirect = request()->getPost('returnUrl', ['common_email_templates/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Delete existing template
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
        $model = CommonEmailTemplate::model()->findByPk((int)$id);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($model->getIsRemovable()) {
            $model->delete();
        }

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['common_email_templates/index']);
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

        $options['id']              = CHtml::activeId($event->sender->owner, $event->params['attribute']);
        $options['height']          = 500;
        $options['fullPage']        = true;
        $options['allowedContent']  = true;

        $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
    }
}
