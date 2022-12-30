<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Company_typesController
 *
 * Handles the actions for company types related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.7
 */

class Company_typesController extends Controller
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
     * List all the available company types
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $type = new CompanyType('search');
        $type->unsetAttributes();
        $type->attributes = (array)request()->getQuery($type->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('company_types', 'Company types'),
            'pageHeading'     => t('company_types', 'Company types'),
            'pageBreadcrumbs' => [
                t('company_types', 'Company types')    => createUrl('company_types/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('type'));
    }

    /**
     * Create a new company type
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $type = new CompanyType();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($type->getModelName(), []))) {
            $type->attributes = $attributes;
            if (!$type->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'type'      => $type,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['company_types/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('company_types', 'Create new company type'),
            'pageHeading'     => t('company_types', 'Create new company type'),
            'pageBreadcrumbs' => [
                t('company_types', 'Company types') => createUrl('company_types/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('type'));
    }

    /**
     * Update existing company type
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $type = CompanyType::model()->findByPk((int)$id);

        if (empty($type)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($type->getModelName(), []))) {
            $type->attributes = $attributes;
            if (!$type->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'type'      => $type,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['company_types/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('company_types', 'Update company type'),
            'pageHeading'     => t('company_types', 'Update company type'),
            'pageBreadcrumbs' => [
                t('company_types', 'Company types') => createUrl('company_types/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('type'));
    }

    /**
     * Delete exiting company type
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
        $type = CompanyType::model()->findByPk((int)$id);

        if (empty($type)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $type->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['company_types/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $type,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }
}
