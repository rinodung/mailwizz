<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Templates_categoriesController
 *
 * Handles the actions for template categories related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.9.5
 */

class Templates_categoriesController extends Controller
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
            return;
        }

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
        $category = new CustomerEmailTemplateCategory('search');
        $category->unsetAttributes();

        // for filters.
        $category->attributes  = (array)request()->getQuery($category->getModelName(), []);
        $category->customer_id = (int)customer()->getId();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_templates', 'View categories'),
            'pageHeading'     => t('email_templates', 'View categories'),
            'pageBreadcrumbs' => [
                t('email_templates', 'Email templates') => createUrl('templates/index'),
                t('email_templates', 'Categories') => createUrl('templates_categories/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('category'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $category = new CustomerEmailTemplateCategory();
        $category->customer_id = (int)customer()->getId();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($category->getModelName(), []))) {
            $category->attributes  = $attributes;
            $category->customer_id = (int)customer()->getId();

            if (!$category->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'category'   => $category,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['templates_categories/index']);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_templates', 'Create new category'),
            'pageHeading'     => t('email_templates', 'Create new category'),
            'pageBreadcrumbs' => [
                t('email_templates', 'Email templates') => createUrl('templates/index'),
                t('email_templates', 'Categories') => createUrl('templates_categories/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('category'));
    }

    /**
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $category = CustomerEmailTemplateCategory::model()->findByAttributes([
            'category_id' => (int)$id,
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($category)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($category->getModelName(), []))) {
            $category->attributes  = $attributes;
            $category->customer_id = (int)customer()->getId();

            if (!$category->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'category'   => $category,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['templates_categories/index']);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_templates', 'Update category'),
            'pageHeading'     => t('email_templates', 'Update category'),
            'pageBreadcrumbs' => [
                t('email_templates', 'Email templates') => createUrl('templates/index'),
                t('email_templates', 'Categories') => createUrl('templates_categories/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('category'));
    }

    /**
     * @param int $id
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($id)
    {
        $category = CustomerEmailTemplateCategory::model()->findByAttributes([
            'category_id' => (int)$id,
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($category)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $category->delete();

        $redirect = '';
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['templates_categories/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $category,
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
        $models = CustomerEmailTemplateCategory::model()->findAllByAttributes([
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($models)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
            return;
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('email-templates-categories.csv');

        try {
            $csvWriter  = League\Csv\Writer::createFromPath('php://output', 'w');
            $attributes = AttributeHelper::removeSpecialAttributes($models[0]->attributes);

            /** @var callable $callback */
            $callback   = [$models[0], 'getAttributeLabel'];
            $attributes = array_map($callback, array_keys($attributes));

            $csvWriter->insertOne($attributes);

            foreach ($models as $model) {
                $attributes = AttributeHelper::removeSpecialAttributes($model->attributes);
                $csvWriter->insertOne(array_values($attributes));
            }
        } catch (Exception $e) {
        }

        app()->end();
    }
}
