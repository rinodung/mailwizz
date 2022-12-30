<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Customer_login_logsController
 *
 * Handles the actions for customer login logs
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.2
 */

class Customer_login_logsController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('customers-login-logs.js')]);
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, delete_all',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List customer login logs
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $model = new CustomerLoginLog('search');
        $model->unsetAttributes();
        $model->attributes = (array)request()->getQuery($model->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'View login logs'),
            'pageHeading'     => t('customers', 'View login logs'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers')  => createUrl('customers/index'),
                t('customers', 'Login logs') => createUrl('customer_login_logs/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('model'));
    }

    /**
     * Delete existing customer login log
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
        $model = CustomerLoginLog::model()->findByPk((int)$id);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $model->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            $redirect = request()->getPost('returnUrl', ['customer_login_logs/index']);
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
     * Delete all login logs
     *
     * @return void
     * @throws CException
     */
    public function actionDelete_all()
    {
        CustomerLoginLog::model()->deleteAll();

        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'Your items have been successfully deleted!'));
            $this->redirect(request()->getPost('returnUrl', ['customer_login_logs/index']));
        }
    }
}
