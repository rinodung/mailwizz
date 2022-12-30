<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * User_groupsController
 *
 * Handles the actions for user groups related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class User_groupsController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('user-groups.js')]);
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete', // we only allow deletion via POST request
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all available groups
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $group = new UserGroup('search');
        $group->unsetAttributes();

        // for filters.
        $group->attributes = (array)request()->getQuery($group->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('user_groups', 'View user groups'),
            'pageHeading'     => t('user_groups', 'View user groups'),
            'pageBreadcrumbs' => [
                t('users', 'Users') => createUrl('users/index'),
                t('user_groups', 'User groups') => createUrl('user_groups/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('group'));
    }

    /**
     * Create a new group
     *
     * @return void
     * @throws CException
     * @throws ReflectionException
     */
    public function actionCreate()
    {
        $group = new UserGroup('search');

        $routesAccess = $group->getAllRoutesAccess();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($group->getModelName(), []))) {
            $group->attributes = $attributes;
            if (!$group->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                /** @var array<array> $routes */
                $routes = (array)request()->getPost('UserGroupRouteAccess', []);
                if (!empty($routes)) {
                    foreach ($routesAccess as $index => $data) {
                        foreach ($data['routes'] as $route) {
                            $route->group_id = (int)$group->group_id;
                            $route->access   = UserGroupRouteAccess::ALLOW;
                            if (isset($routes[$index]['routes'][$route->route])) {
                                $route->access = $routes[$index]['routes'][$route->route];
                            }
                            $route->save();
                        }
                    }
                }
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'group'      => $group,
            ]));

            if (request()->getIsAjaxRequest()) {
                app()->end();
            }

            if ($collection->itemAt('success')) {
                $this->redirect(['user_groups/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('user_groups', 'Create new user group'),
            'pageHeading'     => t('user_groups', 'Create new user group'),
            'pageBreadcrumbs' => [
                t('users', 'Users') => createUrl('users/index'),
                t('user_groups', 'User groups') => createUrl('user_groups/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('group', 'routesAccess'));
    }

    /**
     * Update existing group
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     * @throws ReflectionException
     */
    public function actionUpdate($id)
    {
        $group = UserGroup::model()->findByPk((int)$id);

        if (empty($group)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $routesAccess = $group->getAllRoutesAccess();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($group->getModelName(), []))) {
            $group->attributes = $attributes;
            if (!$group->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                /** @var array<array> $routes */
                $routes = (array)request()->getPost('UserGroupRouteAccess', []);
                if (!empty($routes)) {
                    foreach ($routesAccess as $index => $data) {
                        foreach ($data['routes'] as $route) {
                            $route->group_id = (int)$group->group_id;
                            $route->access   = UserGroupRouteAccess::ALLOW;
                            if (isset($routes[$index]['routes'][$route->route])) {
                                $route->access = $routes[$index]['routes'][$route->route];
                            }
                            $route->save();
                        }
                    }
                }
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'group'      => $group,
            ]));

            if (request()->getIsAjaxRequest()) {
                app()->end();
            }

            if ($collection->itemAt('success')) {
                $this->redirect(['user_groups/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('users', 'Update user group'),
            'pageHeading'     => t('users', 'Update user group'),
            'pageBreadcrumbs' => [
                t('users', 'Users') => createUrl('users/index'),
                t('user_groups', 'User groups') => createUrl('user_groups/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('group', 'routesAccess'));
    }

    /**
     * Delete existing group
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
        $group = UserGroup::model()->findByPk((int)$id);

        if (empty($group)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $group->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['user_groups/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $group,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }
}
