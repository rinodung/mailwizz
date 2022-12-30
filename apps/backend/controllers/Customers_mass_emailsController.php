<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Customers_mass_emailsController
 *
 * Handles the actions for sending mass emails to customers
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.7
 */

class Customers_mass_emailsController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('customers-mass-emails.js')]);
        parent::init();
    }

    /**
     * Send mass emails to customers
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $model = new CustomerMassEmail();

        /** @var OptionCommon $optionCommon */
        $optionCommon = container()->get(OptionCommon::class);

        if (empty($model->message)) {
            /** @var OptionEmailTemplate $optionEmailTemplate */
            $optionEmailTemplate = container()->get(OptionEmailTemplate::class);

            $searchReplace = [
                '[SITE_NAME]'       => $optionCommon->getSiteName(),
                '[SITE_TAGLINE]'    => $optionCommon->getSiteTagline(),
                '[CURRENT_YEAR]'    => date('Y'),
            ];
            $model->message = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $optionEmailTemplate->common);
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'Mass emails'),
            'pageHeading'     => t('customers', 'Mass emails'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers')  => createUrl('customers/index'),
                t('customers', 'Mass emails') => createUrl('customers_mass_emails/index'),
            ],
        ]);

        $model->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;
            if (!request()->getIsAjaxRequest()) {
                /** @var array $post */
                $post = (array)request()->getOriginalPost('', []);
                $model->message = (string)$post[$model->getModelName()]['message'];
                if ($model->validate()) {
                    $jsonAttributes = json_encode([
                        'attributes'           => $model->attributes,
                        'formatted_attributes' => $model->getFormattedAttributes(),
                    ], JSON_HEX_APOS);
                    $this->render('index-ajax', compact('model', 'jsonAttributes'));
                    return;
                }
            } else {
                if (empty($model->message_id) || !is_file((string)Yii::getPathOfAlias(CustomerMassEmail::STORAGE_ALIAS) . '/' . $model->message_id)) {
                    $this->renderJson([
                        'result'  => 'error',
                        'message' => t('customers', 'Unable to load the message from written source!'),
                    ]);
                }
                $model->loadCustomers();
                if (empty($model->customers)) {
                    if (is_file($file = (string)Yii::getPathOfAlias(CustomerMassEmail::STORAGE_ALIAS) . '/' . $model->message_id)) {
                        unlink($file);
                    }
                    $model->finished = true;
                    $model->progress_text = t('customers', 'All emails were queued successfully!');
                    $this->renderJson([
                        'result'               => 'success',
                        'message'              => $model->progress_text,
                        'attributes'           => $model->attributes,
                        'formatted_attributes' => $model->getFormattedAttributes(),
                    ]);
                }
                $message = (string)file_get_contents((string)Yii::getPathOfAlias(CustomerMassEmail::STORAGE_ALIAS) . '/' . $model->message_id);

                /** @var Customer[] $customers */
                $customers = !empty($model->customers) ? $model->customers : [];
                foreach ($customers as $customer) {
                    $searchReplace = [
                        '[FULL_NAME]'  => $customer->getFullName(),
                        '[FIRST_NAME]' => $customer->first_name,
                        '[LAST_NAME]'  => $customer->last_name,
                        '[EMAIL]'      => $customer->email,
                    ];
                    $body    = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $message);
                    $subject = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $model->subject);
                    $email   = new TransactionalEmail();
                    $email->to_name   = $customer->getFullName();
                    $email->to_email  = $customer->email;
                    $email->from_name = $optionCommon->getSiteName();
                    $email->subject   = $subject;
                    $email->body      = $body;
                    $email->save();

                    $model->processed++;
                }

                $model->customers     = [];
                $model->page          = $model->page + 1;
                $model->percentage    = ($model->processed * 100) / $model->total;
                $model->progress_text = t('customers', 'Please wait, queueing messages...');

                $this->renderJson([
                    'result'               => 'success',
                    'message'              => $model->progress_text,
                    'attributes'           => $model->attributes,
                    'formatted_attributes' => $model->getFormattedAttributes(),
                ]);
            }
        }

        $this->render('index', compact('model'));
    }

    /**
     * Callback method to set the editor options for email footer in campaigns
     *
     * @return void
     * @param CEvent $event
     */
    public function _setupEditorOptions(CEvent $event)
    {
        if (!in_array($event->params['attribute'], ['message'])) {
            return;
        }

        $options = [];
        if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
            $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
        }

        $options['id']              = CHtml::activeId($event->sender->owner, $event->params['attribute']);
        $options['fullPage']        = true;
        $options['allowedContent']  = true;
        $options['height']          = 500;

        $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
    }
}
