<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Customer_messagesController
 *
 * Handles the actions for customer messages related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.9
 */

class Customer_messagesController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->onBeforeAction = [$this, '_registerJuiBs'];
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
     * List customer messages
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $message = new CustomerMessage('search');
        $message->unsetAttributes();
        $message->attributes = (array)request()->getQuery($message->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('messages', 'View messages'),
            'pageHeading'     => t('messages', 'View messages'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                t('messages', 'Messages')   => createUrl('customer_messages/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('message'));
    }

    /**
     * Create a new message
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $message = new CustomerMessage();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($message->getModelName(), []))) {
            $message->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            $message->message = (string)ioFilter()->purify($post[$message->getModelName()]['message']);

            if (!$message->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'model'     => $message,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['customer_messages/index']);
            }
        }

        $message->fieldDecorator->onHtmlOptionsSetup = [$this, '_setEditorOptions'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('messages', 'Create new message'),
            'pageHeading'     => t('messages', 'Create new message'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                t('messages', 'Messages') => createUrl('customer_messages/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('message'));
    }

    /**
     * Update existing message
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $message = CustomerMessage::model()->findByPk((int)$id);

        if (empty($message)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($message->getModelName(), []))) {
            $message->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            $message->message = (string)ioFilter()->purify($post[$message->getModelName()]['message']);

            if (!$message->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'model'     => $message,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['customer_messages/index']);
            }
        }

        $message->fieldDecorator->onHtmlOptionsSetup = [$this, '_setEditorOptions'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('messages', 'Update message'),
            'pageHeading'     => t('messages', 'Update message'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                t('messages', 'Messages')   => createUrl('customer_messages/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('message'));
    }

    /**
     * View message
     *
     * @param int $id
     *
     * @return void
     * @throws CHttpException
     */
    public function actionView($id)
    {
        $message = CustomerMessage::model()->findByPk((int)$id);

        if (empty($message)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('messages', 'View message'),
            'pageHeading'     => t('messages', 'View message'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                t('messages', 'Messages')   => createUrl('customer_messages/index'),
                t('app', 'View'),
            ],
        ]);

        $this->render('view', compact('message'));
    }

    /**
     * Delete existing customer message
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
        $message = CustomerMessage::model()->findByPk((int)$id);

        if (empty($message)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $message->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            $redirect = request()->getPost('returnUrl', ['customer_messages/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $message,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Callback method to set the editor options for email footer in campaigns
     *
     * @return void
     * @param CEvent $event
     */
    public function _setEditorOptions(CEvent $event)
    {
        if (!in_array($event->params['attribute'], ['message'])) {
            return;
        }

        $options = [];
        if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
            $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
        }
        $options['id'] = CHtml::activeId($event->sender->owner, $event->params['attribute']);

        if ($event->params['attribute'] == 'notification_message') {
            $options['height'] = 100;
        }

        $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _registerJuiBs(CEvent $event)
    {
        if (in_array($event->params['action']->id, ['create', 'update'])) {
            $this->addPageStyles([
                ['src' => apps()->getBaseUrl('assets/css/jui-bs/jquery-ui-1.10.3.custom.css'), 'priority' => -1001],
            ]);
        }
    }
}
