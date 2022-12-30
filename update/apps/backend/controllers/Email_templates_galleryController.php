<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Email_templates_galleryController
 *
 * Handles the actions for templates related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.7
 */

class Email_templates_galleryController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('email-templates-gallery.js')]);
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        return CMap::mergeArray([
            'postOnly + delete',
        ], parent::filters());
    }

    /**
     * List available templates
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $template = new CustomerEmailTemplate('search');
        $template->unsetAttributes();

        // for filters.
        $template->attributes  = (array)request()->getQuery($template->getModelName(), []);
        $template->customer_id = null;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_templates', 'Email templates'),
            'pageHeading'     => t('email_templates', 'View templates'),
            'pageBreadcrumbs' => [
                t('email_templates', 'Email templates') => createUrl('email_templates_gallery/index'),
                t('email_templates', 'Gallery') => createUrl('email_templates_gallery/index'),
                t('app', 'View all'),
            ],
        ]);

        $templateUp = new CustomerEmailTemplate('upload');

        $this->render('list', compact('template', 'templateUp'));
    }

    /**
     * Create a new template
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $template         = new CustomerEmailTemplate();
        $campaignTemplate = new CampaignTemplate();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($template->getModelName(), []))) {
            $template->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            $template->content = (string)$post[$template->getModelName()]['content'];

            if ($template->save()) {
                notify()->addSuccess(t('email_templates', 'You successfully created a new email template!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'    => $this,
                'success'       => notify()->getHasSuccess(),
                'template'      => $template,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['email_templates_gallery/update', 'template_uid' => $template->template_uid]);
            }
        }

        $template->fieldDecorator->onHtmlOptionsSetup = [$this, '_setDefaultEditorForContent'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_templates', 'Create email template'),
            'pageHeading'     => t('email_templates', 'Create email template'),
            'pageBreadcrumbs' => [
                t('email_templates', 'Email templates') => createUrl('email_templates_gallery/index'),
                t('email_templates', 'Gallery') => createUrl('email_templates_gallery/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('template', 'campaignTemplate'));
    }

    /**
     * Update existing template
     *
     * @param string $template_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($template_uid)
    {
        $campaignTemplate = new CampaignTemplate();
        $template         = $this->loadModel($template_uid);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($template->getModelName(), []))) {
            $template->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            $template->content = (string)$post[$template->getModelName()]['content'];

            if ($template->save()) {
                notify()->addSuccess(t('email_templates', 'You successfully updated your email template!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'    => $this,
                'success'       => notify()->getHasSuccess(),
                'template'      => $template,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['email_templates_gallery/update', 'template_uid' => $template->template_uid]);
            }
        }

        $template->fieldDecorator->onHtmlOptionsSetup = [$this, '_setDefaultEditorForContent'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_templates', 'Update email template'),
            'pageHeading'     => t('email_templates', 'Update email template'),
            'pageBreadcrumbs' => [
                t('email_templates', 'Email templates') => createUrl('email_templates_gallery/index'),
                t('email_templates', 'Gallery') => createUrl('email_templates_gallery/index'),
                t('app', 'Update'),
            ],
            'previewUrl' => createUrl('email_templates_gallery/preview', ['template_uid' => $template_uid]),
        ]);

        $this->render('form', compact('template', 'campaignTemplate'));
    }

    /**
     * Copy a template
     *
     * @param string $template_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCopy($template_uid)
    {
        $template = $this->loadModel($template_uid);

        if (!($newTemplate = $template->copy())) {
            notify()->addError(t('email_templates', 'Unable to copy the template!'));
            $this->redirect(['email_templates_gallery/index']);
        }

        notify()->addSuccess(t('email_templates', 'The template has been successfully copied!'));
        $this->redirect(['email_templates_gallery/index']);
    }

    /**
     * Preview template
     *
     * @param string $template_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionPreview($template_uid)
    {
        $template = $this->loadModel($template_uid);

        $cs = clientScript();
        $cs->reset();
        $cs->registerCoreScript('jquery');
        $cs->registerCoreScript('jquery-migrate');

        if ($template->create_screenshot === CustomerEmailTemplate::TEXT_YES) {
            if (request()->enableCsrfValidation) {
                $cs->registerMetaTag(request()->csrfTokenName, 'csrf-token-name');
                $cs->registerMetaTag(request()->getCsrfToken(), 'csrf-token-value');
            }

            $cs->registerMetaTag(createUrl('email_templates_gallery/save_screenshot', ['template_uid' => $template_uid]), 'save-screenshot-url');
            $cs->registerMetaTag(t('email_templates', 'Please wait while saving your template screenshot...'), 'wait-message');
            $cs->registerScriptFile(AssetsUrl::js('html2canvas/html2canvas.min.js'));
        }

        $cs->registerScriptFile(AssetsUrl::js('email-templates-gallery-preview.js'));

        $this->renderPartial('preview', compact('template'), false, true);
    }

    /**
     * Save template screenshot
     *
     * @param string $template_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionSave_screenshot($template_uid)
    {
        /** @var bool $debug */
        $debug = MW_DEBUG;

        if (!request()->getIsPostRequest() || $debug) {
            app()->end();
        }

        $template = $this->loadModel($template_uid);

        if ($template->create_screenshot !== CustomerEmailTemplate::TEXT_YES) {
            app()->end();
        }

        $data = '';

        /** @var array $post */
        $post = (array)request()->getOriginalPost('', []);
        if (isset($post['data'])) {
            $data = (string)ioFilter()->purify($post['data']);
        }

        if (empty($data) || strpos($data, 'data:image/png;base64,') !== 0) {
            app()->end();
        }

        $base64img = str_replace('data:image/png;base64,', '', $data);
        if (!($image = base64_decode($base64img))) {
            app()->end();
        }

        $baseDir = (string)Yii::getPathOfAlias('root.frontend.assets.gallery.' . $template_uid);
        if ((!file_exists($baseDir) && !mkdir($baseDir, 0777, true)) || (!is_writable($baseDir) && !chmod($baseDir, 0777))) {
            app()->end();
        }

        $destination = $baseDir . '/' . $template_uid . '.png';
        file_put_contents($destination, $image);

        if (!($info = ImageHelper::getImageSize($destination))) {
            unlink($destination);
        }

        $template->screenshot = '/frontend/assets/gallery/' . $template_uid . '/' . $template_uid . '.png';
        $template->create_screenshot = CustomerEmailTemplate::TEXT_NO;
        $template->save(false);

        app()->end();
    }

    /**
     * Upload a template zip archive
     *
     * @return void
     */
    public function actionUpload()
    {
        $model    = new CustomerEmailTemplate('upload');
        $redirect = ['email_templates_gallery/index'];

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;
            $model->archive = CUploadedFile::getInstance($model, 'archive');
            if (!$model->validate() || !$model->uploader->handleUpload()) {
                notify()->addError($model->shortErrors->getAllAsString());
            } else {
                notify()->addSuccess(t('app', 'Your file has been successfully uploaded!'));
                $redirect = ['email_templates_gallery/update', 'template_uid' => $model->template_uid];
            }
            $this->redirect($redirect);
        }

        notify()->addError(t('app', 'Please select a file for upload!'));
        $this->redirect($redirect);
    }

    /**
     * Delete existing template
     *
     * @param string $template_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($template_uid)
    {
        $template = $this->loadModel($template_uid);

        $template->delete();

        $redirect = null;
        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('email_templates', 'Your template was successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['email_templates_gallery/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $template,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * @param string $template_uid
     *
     * @return CustomerEmailTemplate
     * @throws CHttpException
     */
    public function loadModel(string $template_uid): CustomerEmailTemplate
    {
        $model = CustomerEmailTemplate::model()->find([
            'condition' => 'template_uid = :uid AND customer_id IS NULL',
            'params'    => [':uid' => $template_uid],
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }

    /**
     * Callback to setup the editor for creating/updating the template
     *
     * @param CEvent $event
     *
     * @return void
     */
    public function _setDefaultEditorForContent(CEvent $event)
    {
        if ($event->params['attribute'] == 'content') {
            $options = [];
            if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
                $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
            }

            $options['id'] = CHtml::activeId($event->sender->owner, 'content');
            $options['fullPage'] = true;
            $options['allowedContent'] = true;
            $options['contentsCss'] = [];
            $options['height'] = 800;

            $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
        }
    }
}
