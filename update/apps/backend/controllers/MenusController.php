<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * MenusController
 *
 * Handles the actions for menus related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.30
 */

class MenusController extends Controller
{
    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('menus.js')]);
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
     * List all available menus
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $menu = new Menu('search');
        $menu->unsetAttributes();
        $menu->attributes = (array)request()->getQuery($menu->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('menus', 'View menus'),
            'pageHeading'     => t('menus', 'View menus'),
            'pageBreadcrumbs' => [
                t('menus', 'Menus') => createUrl('menus/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('menu'));
    }

    /**
     * Create a new menu
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCreate()
    {
        $menu = new Menu();

        $menuItem  = new MenuItem();
        $menuItems = [];

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($menu->getModelName(), []))) {
            $menu->attributes = $attributes;

            if ($menuItemsAttributes = (array)request()->getPost($menuItem->getModelName(), [])) {
                /** @var array $attributes */
                foreach ($menuItemsAttributes as $attributes) {
                    $menuItemModel = new MenuItem();
                    $menuItemModel->attributes = (array)$attributes;
                    $menuItems[] = $menuItemModel;
                }
            }

            $transaction = db()->beginTransaction();
            $success = false;

            try {
                if (!$menu->save()) {
                    throw new Exception(CHtml::errorSummary($menu), 422);
                }

                $menuItemErrors = false;
                if (!empty($menuItems)) {
                    foreach ($menuItems as $menuItemModel) {
                        $menuItemModel->menu_id = (int)$menu->menu_id;
                        if (!$menuItemModel->save()) {
                            $menuItemErrors = true;
                        }
                    }
                }

                if ($menuItemErrors) {
                    throw new Exception(t('app', 'Your form has a few errors, please fix them and try again!'));
                }

                $transaction->commit();
                $success = true;
            } catch (Exception $e) {
                $transaction->rollback();
            }

            if ($success) {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => $success,
                'menu'       => $menu,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['menus/update', 'id' => $menu->menu_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('menus', 'Create new menu'),
            'pageHeading'     => t('menus', 'Create new menu'),
            'pageBreadcrumbs' => [
                t('menus', 'Menus') => createUrl('menus/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('menu', 'menuItem', 'menuItems'));
    }

    /**
     * Update existing menu
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $menu = Menu::model()->findByAttributes(['menu_id' => (int)$id]);

        if (empty($menu)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $menuItem  = new MenuItem();
        $menuItems = MenuItem::model()->findAllByAttributes(['menu_id' => $menu->menu_id]);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($menu->getModelName(), []))) {
            $menu->attributes = $attributes;

            $menuItems = [];
            if ($menuItemsAttributes = (array)request()->getPost($menuItem->getModelName(), [])) {
                /** @var array $attributes */
                foreach ($menuItemsAttributes as $attributes) {
                    $menuItemModel = new MenuItem();
                    $menuItemModel->attributes = (array)$attributes;
                    $menuItems[] = $menuItemModel;
                }
            }

            $transaction = db()->beginTransaction();
            $success = false;

            try {
                if (!$menu->save()) {
                    throw new Exception(CHtml::errorSummary($menu), 422);
                }

                $menuItemErrors = false;
                MenuItem::model()->deleteAllByAttributes(['menu_id' => $menu->menu_id]);
                if (!empty($menuItems)) {
                    foreach ($menuItems as $menuItemModel) {
                        $menuItemModel->menu_id = (int)$menu->menu_id;
                        if (!$menuItemModel->save()) {
                            $menuItemErrors = true;
                        }
                    }
                }

                if ($menuItemErrors) {
                    throw new Exception(t('app', 'Your form has a few errors, please fix them and try again!'));
                }

                $transaction->commit();
                $success = true;
            } catch (Exception $e) {
                $transaction->rollback();
            }

            if ($success) {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => $success,
                'menu'       => $menu,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['menus/update', 'id' => $menu->menu_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('menus', 'Update menu'),
            'pageHeading'     => t('menus', 'Update menu'),
            'pageBreadcrumbs' => [
                t('menus', 'Menus') => createUrl('menus/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('menu', 'menuItem', 'menuItems'));
    }

    /**
     * Delete an existing article
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
        $menu = Menu::model()->findByPk((int)$id);

        if (empty($menu)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $menu->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['menus/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $menu,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Run a bulk action against the menus
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionBulk_action()
    {
        $action  = request()->getPost('bulk_action');
        $items   = array_unique(array_map('intval', (array)request()->getPost('bulk_item', [])));

        if ($action == Menu::BULK_ACTION_DELETE && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                $menu = Menu::model()->findByPk((int)$item);

                if (empty($menu)) {
                    continue;
                }

                $menu->delete();
                $affected++;
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        }

        $defaultReturn = request()->getServer('HTTP_REFERER', ['menus/index']);
        $this->redirect(request()->getPost('returnUrl', $defaultReturn));
    }
}
