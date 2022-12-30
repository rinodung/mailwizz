<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * MessagesController
 *
 * Handles the actions for messages related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.9
 */

class MessagesController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount()) {
            $this->redirect(['dashboard/index']);
        }

        parent::init();
    }

    /**
     * @return array
     * @throws CException
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * Show available customer messages
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $message = new CustomerMessage('search');
        $message->unsetAttributes();
        $message->attributes  = (array)request()->getQuery($message->getModelName(), []);
        $message->customer_id = (int)customer()->getId();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('messages', 'Messages'),
            'pageHeading'     => t('messages', 'Messages'),
            'pageBreadcrumbs' => [
                t('messages', 'Messages') => createUrl('messages/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('message'));
    }

    /**
     * View customer message
     *
     * @param string $message_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionView($message_uid)
    {
        $message = CustomerMessage::model()->findByAttributes([
            'customer_id' => (int)customer()->getId(),
            'message_uid' => $message_uid,
        ]);

        if (empty($message)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($message->getIsUnseen()) {
            $message->saveStatus(CustomerMessage::STATUS_SEEN);
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('messages', 'Messages'),
            'pageHeading'     => t('messages', 'Messages'),
            'pageBreadcrumbs' => [
                t('messages', 'Messages') => createUrl('messages/index'),
                t('app', 'View'),
            ],
        ]);

        $this->render('view', compact('message'));
    }

    /**
     * Delete existing message
     *
     * @param string $message_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($message_uid)
    {
        $message = CustomerMessage::model()->findByAttributes([
            'customer_id' => (int)customer()->getId(),
            'message_uid' => $message_uid,
        ]);

        if (empty($message)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $message->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            $redirect = request()->getPost('returnUrl', ['messages/index']);
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
     * Mark all messages as seen for a certain customer
     *
     * @return void
     */
    public function actionMark_all_as_seen()
    {
        CustomerMessage::markAllAsSeenForCustomer((int)customer()->getId());
        notify()->addSuccess(t('messages', 'All messages were marked as seen!'));
        request()->redirect('index');
    }

    /**
     * Show available customer messages for header
     *
     * @return void
     * @throws CException
     */
    public function actionHeader()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
        }

        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)customer()->getId());
        $criteria->compare('status', CustomerMessage::STATUS_UNSEEN);
        $criteria->order = 'message_id DESC';
        $criteria->limit = 100;

        $messages = CustomerMessage::model()->findAll($criteria);
        $counter  = is_countable($messages) ? count($messages) : 0;

        $this->renderJson([
            'counter' => $counter,
            'header'  => t('messages', 'You have {n} unread messages!', $counter),
            'html'    => $this->renderPartial('_header', compact('messages'), true),
        ]);
    }

    /**
     * Export
     *
     * @return void
     */
    public function actionExport()
    {
        $models = CustomerMessage::model()->findAllByAttributes([
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($models)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('messages.csv');

        $attrsList  = ['translatedTitle', 'translatedMessage', 'status', 'date_added', 'last_updated'];
        $attributes = AttributeHelper::removeSpecialAttributes($models[0]->getAttributes($attrsList));

        /** @var callable $callback */
        $callback = [$models[0], 'getAttributeLabel'];
        $columns  = array_map($callback, array_keys($attributes));

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertOne($columns);

            foreach ($models as $model) {
                $attributes = AttributeHelper::removeSpecialAttributes($model->getAttributes($attrsList));
                $attributes['translatedTitle']   = $model->translatedTitle;
                $attributes['translatedMessage'] = $model->translatedMessage;

                $csvWriter->insertOne(array_values($attributes));
            }
        } catch (Exception $e) {
        }

        app()->end();
    }
}
