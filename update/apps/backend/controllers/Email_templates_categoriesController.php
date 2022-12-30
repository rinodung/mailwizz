<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Email_templates_categoriesController
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

class Email_templates_categoriesController extends Controller
{
    /**
     * @return array
     */
    public function filters()
    {
        return CMap::mergeArray([
            'postOnly + delete',
        ], parent::filters());
    }

    /**
     * List all available categories
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $category = new CustomerEmailTemplateCategory('search');
        $category->unsetAttributes();
        $category->attributes = (array)request()->getQuery($category->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_templates', 'View categories'),
            'pageHeading'     => t('email_templates', 'View categories'),
            'pageBreadcrumbs' => [
                t('email_templates', 'Email templates') => createUrl('email_templates_gallery/index'),
                t('email_templates', 'Categories') => createUrl('email_templates_categories/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('category'));
    }

    /**
     * Create a new category
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $category = new CustomerEmailTemplateCategory();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($category->getModelName(), []))) {
            $category->attributes = $attributes;
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
                $this->redirect(['email_templates_categories/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_templates', 'Create new category'),
            'pageHeading'     => t('email_templates', 'Create new category'),
            'pageBreadcrumbs' => [
                t('email_templates', 'Email templates') => createUrl('email_templates_gallery/index'),
                t('email_templates', 'Categories') => createUrl('email_templates_categories/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('category'));
    }

    /**
     * Update existing category
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $category = CustomerEmailTemplateCategory::model()->findByPk((int)$id);

        if (empty($category)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($category->getModelName(), []))) {
            $category->attributes = $attributes;

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
                $this->redirect(['email_templates_categories/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_templates', 'Update category'),
            'pageHeading'     => t('email_templates', 'Update category'),
            'pageBreadcrumbs' => [
                t('email_templates', 'Email templates') => createUrl('email_templates_gallery/index'),
                t('email_templates', 'Categories') => createUrl('email_templates_categories/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('category'));
    }

    /**
     * Delete an existing category
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
        $category = CustomerEmailTemplateCategory::model()->findByPk((int)$id);

        if (empty($category)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $category->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['email_templates_categories/index']);
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
}
