<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Transactional_emailsController
 *
 * Handles the actions for transactional emails related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.6
 */

class Transactional_emailsController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->addPageScript(['src' => AssetsUrl::js('transactional-emails.js')]);
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
     * List all available emails
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $email = new TransactionalEmail('search');
        $email->unsetAttributes();

        $email->attributes = (array)request()->getQuery($email->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('transactional_emails', 'View transactional emails'),
            'pageHeading'     => t('transactional_emails', 'View transactional emails'),
            'pageBreadcrumbs' => [
                t('transactional_emails', 'Transactional emails') => createUrl('transactional_emails/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('email'));
    }

    /**
     * Preview transactional email
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionPreview($id)
    {
        $email = TransactionalEmail::model()->findByPk((int)$id);

        if (empty($email)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $this->renderPartial('preview', compact('email'), false, true);
    }

    /**
     * Resend transactional email
     *
     * @param int $id
     *
     * @return void
     * @throws CHttpException
     */
    public function actionResend($id)
    {
        $email = TransactionalEmail::model()->findByPk((int)$id);

        if (empty($email) || $email->status != TransactionalEmail::STATUS_SENT) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $email->status       = TransactionalEmail::STATUS_UNSENT;
        $email->sendDirectly = true;

        if ($email->save(false)) {
            notify()->addSuccess(t('app', 'The email has been successfully resent!'));
        }

        $this->redirect(request()->getPost('returnUrl', ['transactional_emails/index']));
    }

    /**
     * Delete existing email
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
        $email = TransactionalEmail::model()->findByPk((int)$id);

        if (empty($email)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $email->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['transactional_emails/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $email,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }
}
