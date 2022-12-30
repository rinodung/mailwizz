<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Block_email_requestController
 *
 * Handles the actions for block email requests related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.9
 */

class Block_email_requestController extends Controller
{
    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, bulk_action',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all block email requests
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $model = new BlockEmailRequest('search');
        $model->unsetAttributes();
        $model->attributes = (array)request()->getQuery($model->getModelName(), []);

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_blacklist', 'Block email requests'),
            'pageHeading'     => t('email_blacklist', 'Block email requests'),
            'pageBreadcrumbs' => [
                t('email_blacklist', 'Block email requests') => createUrl('block_email_request/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('model', 'optionUrl'));
    }

    /**
     * Confirm a block email request.
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionConfirm($id)
    {
        /** @var BlockEmailRequest|null $model */
        $model = BlockEmailRequest::model()->findByPk((int)$id);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $model->block();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('email_blacklist', 'The request has been successfully confirmed!'));
            $redirect = request()->getPost('returnUrl', ['block_email_request/index']);
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
     * Delete a block email request.
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
        $model = BlockEmailRequest::model()->findByPk((int)$id);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $model->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['block_email_request/index']);
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
     * Run a bulk action against the block requests
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionBulk_action()
    {
        // 1.4.5

        $action      = request()->getPost('bulk_action');
        $items       = array_unique((array)request()->getPost('bulk_item', []));
        $returnRoute = ['block_email_request/index'];

        if ($action == BlockEmailRequest::BULK_ACTION_CONFIRM && count($items)) {
            $affected = 0;
            foreach ($items as $item) {

                /** @var BlockEmailRequest|null $model */
                $model = BlockEmailRequest::model()->findByPk((int)$item);

                if (empty($model)) {
                    continue;
                }

                $model->block();

                $affected++;
            }

            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        }

        $defaultReturn = request()->getServer('HTTP_REFERER', $returnRoute);
        $this->redirect(request()->getPost('returnUrl', $defaultReturn));
    }
}
