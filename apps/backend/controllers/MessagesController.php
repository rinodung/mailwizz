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
 * @since 1.3.7.3
 */

class MessagesController extends Controller
{
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
     * Show available user messages
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $message = new UserMessage('search');
        $message->unsetAttributes();
        $message->attributes = (array)request()->getQuery($message->getModelName(), []);
        $message->user_id = (int)user()->getId();

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
     * View user message
     *
     * @param string $message_uid
     *
     * @return void
     * @throws CHttpException
     * @throws Exception
     */
    public function actionView($message_uid)
    {
        /** @var UserMessage|null $message */
        $message = UserMessage::model()->findByAttributes([
            'user_id'     => (int)user()->getId(),
            'message_uid' => $message_uid,
        ]);

        if (empty($message)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($message->getIsUnseen()) {
            $message->saveStatus(UserMessage::STATUS_SEEN);
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
        $message = UserMessage::model()->findByAttributes([
            'user_id'     => (int)user()->getId(),
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
     * Mark all messages as seen for a certain user
     *
     * @return void
     */
    public function actionMark_all_as_seen()
    {
        UserMessage::markAllAsSeenForUser((int)user()->getId());
        notify()->addSuccess(t('messages', 'All messages were marked as seen!'));
        request()->redirect('index');
    }

    /**
     * Show available user messages for header
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
        $criteria->compare('user_id', (int)user()->getId());
        $criteria->compare('status', UserMessage::STATUS_UNSEEN);
        $criteria->order = 'message_id DESC';
        $criteria->limit = 100;

        $messages = UserMessage::model()->findAll($criteria);
        $counter  = is_countable($messages) ? count($messages) : 0;

        $this->renderJson([
            'counter' => $counter,
            'header'  => t('messages', 'You have {n} unread messages!', $counter),
            'html'    => $this->renderPartial('_header', compact('messages'), true),
        ]);
    }
}
