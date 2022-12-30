<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * TemplatesController
 *
 * Handles the actions for templates related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class TemplatesController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageEmailTemplates()) {
            $this->redirect(['dashboard/index']);
        }

        $this->addPageScript(['src' => AssetsUrl::js('templates.js')]);
        parent::init();
    }

    /**
     * @return array
     * @throws CException
     */
    public function filters()
    {
        return CMap::mergeArray([
            'postOnly + delete',
        ], parent::filters());
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $template = new CustomerEmailTemplate('search');
        $template->unsetAttributes();

        // for filters.
        $template->attributes  = (array)request()->getQuery($template->getModelName(), []);
        $template->customer_id = (int)customer()->getId();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_templates', 'Email templates'),
            'pageHeading'     => t('email_templates', 'Email templates'),
            'pageBreadcrumbs' => [
                t('email_templates', 'Email templates') => createUrl('templates/index'),
                t('app', 'View all'),
            ],
        ]);

        $templateUp = new CustomerEmailTemplate('upload');

        $this->render('list', compact('template', 'templateUp'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionGallery()
    {
        $template = new CustomerEmailTemplate('search');
        $template->unsetAttributes();

        // for filters.
        $template->attributes  = (array)request()->getQuery($template->getModelName(), []);
        $template->customer_id = null;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_templates', 'Email templates gallery'),
            'pageHeading'     => t('email_templates', 'Email templates gallery'),
            'pageBreadcrumbs' => [
                t('email_templates', 'Email templates') => createUrl('templates/index'),
                t('email_templates', 'Gallery') => createUrl('templates/gallery'),
                t('app', 'View all'),
            ],
        ]);

        $itemsCount = CustomerEmailTemplate::model()->count('customer_id IS NULL');
        if (empty($itemsCount)) {
            $this->redirect(['templates/index']);
        }

        $this->render('gallery', compact('template', 'itemsCount'));
    }

    /**
     * @param string $template_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionGallery_import($template_uid)
    {
        /** @var CustomerEmailTemplate|null $template */
        $template = CustomerEmailTemplate::model()->find([
            'condition' => 'template_uid = :uid AND customer_id IS NULL',
            'params'    => [':uid' => (string)$template_uid],
        ]);

        if (empty($template)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        /** @var CustomerEmailTemplate|null $newTemplate */
        $newTemplate = $template->copy();
        if (empty($newTemplate)) {
            notify()->addError(t('email_templates', 'Unable to import the template!'));
            $this->redirect(['templates/gallery']);
            return;
        }
        $newTemplate->customer_id = (int)customer()->getId();
        $newTemplate->category_id = null;

        if (!empty($template->category_id)) {

            /** @var CustomerEmailTemplateCategory|null $category */
            $category = CustomerEmailTemplateCategory::model()->findByAttributes([
                'name'        => $template->category->name,
                'customer_id' => (int)$newTemplate->customer_id,
            ]);

            if (empty($category)) {
                $category = new CustomerEmailTemplateCategory();
                $category->customer_id = (int)$newTemplate->customer_id;
                $category->name        = $template->category->name;
                $category->save();
            }

            if (!empty($category->category_id)) {
                $newTemplate->category_id = (int)$category->category_id;
            }
        }

        if (!$newTemplate->save(false)) {
            $newTemplate->delete();
            notify()->addError(t('email_templates', 'Unable to save the imported template!'));
            $this->redirect(['templates/gallery']);
        }

        notify()->addSuccess(t('email_templates', 'The template has been successfully imported!'));
        $this->redirect(['templates/index']);
    }

    /**
     * @param string $template_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCopy($template_uid)
    {
        $template = $this->loadModel((string)$template_uid);

        if (!($newTemplate = $template->copy())) {
            notify()->addError(t('email_templates', 'Unable to copy the template!'));
            $this->redirect(['templates/index']);
        }

        notify()->addSuccess(t('email_templates', 'The template has been successfully copied!'));
        $this->redirect(['templates/index']);
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $campaignTemplate = new CampaignTemplate();
        $template = new CustomerEmailTemplate();
        $template->customer_id = (int)customer()->getId();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($template->getModelName(), []))) {
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);

            $template->attributes  = $attributes;
            $template->customer_id = (int)customer()->getId();
            $template->content     = $post[$template->getModelName()]['content'];

            if ($template->save()) {
                notify()->addSuccess(t('email_templates', 'You successfully created a new email template!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'template'   => $template,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['templates/update', 'template_uid' => $template->template_uid]);
            }
        }

        $template->fieldDecorator->onHtmlOptionsSetup = [$this, '_setDefaultEditorForContent'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_templates', 'Create email template'),
            'pageHeading'     => t('email_templates', 'Create email template'),
            'pageBreadcrumbs' => [
                t('email_templates', 'Email templates') => createUrl('templates/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('template', 'campaignTemplate'));
    }

    /**
     * @param string $template_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($template_uid)
    {
        $campaignTemplate = new CampaignTemplate();
        $template   = $this->loadModel((string)$template_uid);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($template->getModelName(), []))) {
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);

            $template->attributes  = $attributes;
            $template->customer_id = (int)customer()->getId();
            $template->content     = $post[$template->getModelName()]['content'];

            if ($template->save()) {
                notify()->addSuccess(t('email_templates', 'You successfully updated your email template!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'    => $this,
                'success'       => notify()->getHasSuccess(),
                'template'      => $template,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['templates/update', 'template_uid' => $template->template_uid]);
            }
        }

        $template->fieldDecorator->onHtmlOptionsSetup = [$this, '_setDefaultEditorForContent'];
        $this->setData('previewUrl', createUrl('templates/preview', ['template_uid' => $template_uid]));

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_templates', 'Update email template'),
            'pageHeading'     => t('email_templates', 'Update email template'),
            'pageBreadcrumbs' => [
                t('email_templates', 'Email templates') => createUrl('templates/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('template', 'campaignTemplate'));
    }

    /**
     * @param string $template_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionPreview($template_uid)
    {
        /** @var CustomerEmailTemplate $template */
        $template = $this->loadModel((string)$template_uid);

        $cs = clientScript();
        $cs->reset();
        $cs->registerCoreScript('jquery');

        if ($template->create_screenshot === CustomerEmailTemplate::TEXT_YES) {
            if (request()->enableCsrfValidation) {
                $cs->registerMetaTag(request()->csrfTokenName, 'csrf-token-name');
                $cs->registerMetaTag(request()->getCsrfToken(), 'csrf-token-value');
            }

            $cs->registerMetaTag(createUrl('templates/save_screenshot', ['template_uid' => $template_uid]), 'save-screenshot-url');
            $cs->registerMetaTag(t('email_templates', 'Please wait while saving your template screenshot...'), 'wait-message');
            $cs->registerScriptFile(AssetsUrl::js('html2canvas/html2canvas.min.js'));
        }

        $cs->registerScriptFile(AssetsUrl::js('template-preview.js'));

        $this->renderPartial('preview', compact('template'), false, true);
    }

    /**
     * @param string $template_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionSave_screenshot($template_uid)
    {
        /** @var bool $isDebug */
        $isDebug = MW_DEBUG;

        if (!request()->getIsPostRequest() || $isDebug) {
            app()->end();
        }

        /** @var CustomerEmailTemplate $template */
        $template = $this->loadModel((string)$template_uid);

        if ($template->create_screenshot !== CustomerEmailTemplate::TEXT_YES) {
            app()->end();
        }

        $data = '';
        if ((string)request()->getOriginalPost('data', '')) {
            $data = (string)ioFilter()->purify((string)request()->getOriginalPost('data', ''));
        }

        if (empty($data) || strpos($data, 'data:image/png;base64,') !== 0) {
            app()->end();
        }

        $base64img = (string)str_replace('data:image/png;base64,', '', $data);
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
     * @throws CException
     */
    public function actionUpload()
    {
        $model = new CustomerEmailTemplate('upload');
        $model->customer_id = (int)customer()->getId();

        $redirect = ['templates/index'];

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes  = $attributes;
            $model->customer_id = (int)customer()->getId();
            $model->archive = CUploadedFile::getInstance($model, 'archive');
            if (!$model->validate() || !$model->uploader->handleUpload()) {
                notify()->addError($model->shortErrors->getAllAsString());
            } else {
                notify()->addSuccess(t('app', 'Your file has been successfully uploaded!'));
                $redirect = ['templates/update', 'template_uid' => $model->template_uid];
            }
            $this->redirect($redirect);
        }

        notify()->addError(t('app', 'Please select a file for upload!'));
        $this->redirect($redirect);
    }

    /**
     * @param string $template_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionTest($template_uid)
    {
        $template = $this->loadModel((string)$template_uid);

        if (!request()->getPost('email')) {
            notify()->addError(t('email_templates', 'Please specify the email address to where we should send the test email.'));
            $this->redirect(['templates/update', 'template_uid' => $template_uid]);
        }

        $emails = explode(',', (string)request()->getPost('email', ''));
        $emails = array_map('trim', $emails);
        $emails = array_unique($emails);
        $emails = array_slice($emails, 0, 100);

        $dsParams = ['useFor' => [DeliveryServer::USE_FOR_EMAIL_TESTS, DeliveryServer::USE_FOR_LIST_EMAILS]];

        /** @var DeliveryServer|null $server */
        $server = DeliveryServer::pickServer(0, $template, $dsParams);

        if (empty($server)) {
            notify()->addError(t('email_templates', 'Email delivery is temporary disabled.'));
            $this->redirect(['templates/update', 'template_uid' => $template_uid]);
            return;
        }

        foreach ($emails as $index => $email) {
            if (!FilterVarHelper::email($email)) {
                notify()->addError(t('email_templates', 'The email address {email} does not seem to be valid!', ['{email}' => html_encode($email)]));
                unset($emails[$index]);
                continue;
            }
        }

        if (empty($emails)) {
            notify()->addError(t('email_templates', 'Cannot send using provided email address(es)!'));
            $this->redirect(['templates/update', 'template_uid' => $template_uid]);
        }

        /** @var Customer $customer */
        $customer = customer()->getModel();
        $fromName = $customer->getFullName();

        if (!empty($customer->company)) {
            $fromName = $customer->company->name;
        }

        if (empty($fromName)) {
            $fromName = $customer->email;
        }

        $fromEmail = (string)request()->getPost('from_email', '');
        if (!empty($fromEmail) && !FilterVarHelper::email($fromEmail)) {
            $fromEmail = null;
        }

        $subject = (string)request()->getPost('subject', '');
        if (empty($subject)) {
            $subject = t('templates', '*** TEST TEMPLATE *** {name}', ['{name}' => $template->name]);
        }

        foreach ($emails as $email) {
            $params = [
                'to'        => $email,
                'fromName'  => $fromName,
                'subject'   => $subject,
                'body'      => $template->content,
            ];

            if ($fromEmail) {
                $params['from'] = [$fromEmail => $fromName];
            }

            $sent = false;
            for ($i = 0; $i < 3; ++$i) {
                // @phpstan-ignore-next-line
                if ($sent = $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_TEMPLATE_TEST)->setDeliveryObject($template)->sendEmail($params)) {
                    break;
                }

                /** @var DeliveryServer|null $server */
                $server = DeliveryServer::pickServer((int)$server->server_id, $template, $dsParams); // @phpstan-ignore-line

                if (empty($server)) {
                    break;
                }
            }

            if (!$sent) {
                notify()->addError(t('email_templates', 'Unable to send the test email to {email}!', [
                    '{email}' => html_encode($email),
                ]));
            } else {
                notify()->addSuccess(t('email_templates', 'Test email successfully sent to {email}!', [
                    '{email}' => html_encode($email),
                ]));
            }
        }

        $this->redirect(['templates/update', 'template_uid' => $template_uid]);
    }

    /**
     * @param string $template_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($template_uid)
    {
        /** @var CustomerEmailTemplate $template */
        $template = $this->loadModel((string)$template_uid);

        $template->delete();

        $redirect = '';
        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('email_templates', 'Your template was successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['templates/index']);
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
     * Export
     *
     * @return void
     */
    public function actionExport()
    {
        $models = CustomerEmailTemplate::model()->findAllByAttributes([
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($models)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('email-templates.csv');

        $attributes = AttributeHelper::removeSpecialAttributes($models[0]->getAttributes());

        /** @var callable $callback */
        $callback   = [$models[0], 'getAttributeLabel'];
        $attributes = array_map($callback, array_keys($attributes));

        $attributes = CMap::mergeArray($attributes, [
            $models[0]->getAttributeLabel('category_id'),
        ]);

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertOne($attributes);

            foreach ($models as $model) {
                $attributes = AttributeHelper::removeSpecialAttributes($model->getAttributes());
                $attributes = CMap::mergeArray($attributes, [
                    'category' => $model->category_id ? $model->category->name : '',
                ]);
                $csvWriter->insertOne(array_values($attributes));
            }
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @param string $template_uid
     *
     * @return CustomerEmailTemplate
     * @throws CHttpException
     */
    public function loadModel(string $template_uid): CustomerEmailTemplate
    {
        $model = CustomerEmailTemplate::model()->findByAttributes([
            'template_uid'  => (string)$template_uid,
            'customer_id'   => (int)customer()->getId(),
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }

    /**
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
