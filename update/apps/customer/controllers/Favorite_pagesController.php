<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Favorite_pagesController
 *
 * Handles the actions for favorite pages related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.11
 */

class Favorite_pagesController extends Controller
{
    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, bulk_action, add_remove',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * Show available favorite pages
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $page = new FavoritePage('search');
        $page->unsetAttributes();
        $page->attributes = (array)request()->getQuery($page->getModelName(), []);
        $page->customer_id = (int)customer()->getId();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('favorite_pages', 'Favorite pages'),
            'pageHeading'     => t('favorite_pages', 'Favorite pages'),
            'pageBreadcrumbs' => [
                t('favorite_pages', 'Favorite pages') => createUrl('favorite_pages/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('page'));
    }

    /**
     * Update existing favorite page
     *
     * @param string $page_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate(string $page_uid)
    {
        $page = $this->loadPageByUid($page_uid);
        if (empty($page)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($page->getModelName(), []))) {
            $page->attributes = $attributes;
            if (!$page->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'model'     => $page,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['favorite_pages/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('favorite_pages', 'Update favorite page'),
            'pageHeading'     => t('favorite_pages', 'Update favorite page'),
            'pageBreadcrumbs' => [
                t('favorite_pages', 'Favorite pages') => createUrl('favorite_pages/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('page'));
    }

    /**
     * Delete existing favorite page
     *
     * @param string $page_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($page_uid)
    {
        $page = $this->loadPageByUid($page_uid);
        if (empty($page)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $page->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            $redirect = request()->getPost('returnUrl', ['favorite_pages/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $page,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Redirect to the page route after counting the click
     *
     * @param string $page_uid
     * @return void
     * @throws CHttpException
     */
    public function actionRedirect_to_page($page_uid)
    {
        $page = $this->loadPageByUid($page_uid);
        if (empty($page)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $page->saveCounters(['clicks_count' => 1]);

        $this->redirect(array_merge([$page->route], (array)$page->route_params));
    }

    /**
     * Run a bulk action against the favorite pages
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionBulk_action()
    {
        $action = request()->getPost('bulk_action');
        /** @var string[] $items */
        $items = array_unique((array)request()->getPost('bulk_item', []));

        if ($action == FavoritePage::BULK_ACTION_DELETE && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                /** @var FavoritePage|null $page */
                $page = $this->loadPageByUid($item);

                if (empty($page)) {
                    continue;
                }

                $page->delete();
                $affected++;
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        } elseif ($action == FavoritePage::BULK_ACTION_RESET_CLICK_COUNT && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                if (!($page = $this->loadPageByUid($item))) {
                    continue;
                }

                if (!$page->saveAttributes(['clicks_count' => 0])) {
                    continue;
                }
                $affected++;
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        }

        $defaultReturn = request()->getServer('HTTP_REFERER', ['favorite_pages/index']);
        $this->redirect(request()->getPost('returnUrl', $defaultReturn));
    }

    /**
     * Add/remove user favorite pages
     *
     * @return void
     * @throws CException
     */
    public function actionAdd_remove()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
        }

        $label        = (string)request()->getPost('label');
        $route        = (string)request()->getPost('route');
        $route_params = (string)request()->getPost('route_params');

        if (empty($route) || empty($label)) {
            $this->renderJson([
                'status'  => 'error',
                'message' => t('app', 'Something went wrong'),
            ]);
        }

        $page = FavoritePage::model()->findByAttributes([
            'customer_id' => (int)customer()->getId(),
            'route_hash'  => sha1($route . $route_params),
        ]);

        $status = 'success';
        if ($page) {
            $message                      = t('favorite_pages', 'The page has been removed from favorites');
            $favoritePageWidgetColorClass = 'favorite-page-gray';
            $dataConfirmText              = t('favorite_pages', 'Are you sure you want to add this page to favorites?');
            $title                        = t('favorite_pages', 'Please click here to add this page to favorites');

            if (!$page->delete()) {
                $status  = 'error';
                $message = t('favorite_pages', 'The page cannot be removed from favorites');
            }
        } else {
            $message                      = t('favorite_pages', 'The page has been added to favorites successfully');
            $favoritePageWidgetColorClass = 'favorite-page-green';
            $dataConfirmText              = t('favorite_pages', 'Are you sure you want to remove this page from favorites?');
            $title                        = t('favorite_pages', 'Please click here to remove this page from favorites');

            $page          = new FavoritePage();
            $page->customer_id = (int)customer()->getId();
            $page->route   = $route;
            $page->label   = $label;

            if (!empty($route_params)) {
                $page->route_params = (array)unserialize($route_params);
            }

            if (!$page->save()) {
                $status  = 'error';
                $message = t('favorite_pages', 'The page cannot be added to favorites');
            }
        }

        $this->renderJson([
            'status'                       => $status,
            'message'                      => $message,
            'favoritePageWidgetColorClass' => $favoritePageWidgetColorClass,
            'dataConfirmText'              => $dataConfirmText,
            'title'                        => $title,
            'sideMenuItems'                => FavoritePage::buildTopPagesMenu(),
        ]);
    }

    /**
     * @param string $page_uid
     *
     * @return FavoritePage|null
     */
    protected function loadPageByUid(string $page_uid): ?FavoritePage
    {
        $criteria = new CDbCriteria();
        $criteria->compare('page_uid', $page_uid);
        $criteria->compare('customer_id', (int)customer()->getId());

        /** @var FavoritePage|null $model */
        $model = FavoritePage::model()->find($criteria);

        return $model;
    }
}
