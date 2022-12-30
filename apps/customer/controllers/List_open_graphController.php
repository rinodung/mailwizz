<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List_open_graphController
 *
 * Handles the actions for list open graph tags related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.10
 */

class List_open_graphController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageLists()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        parent::init();
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CHttpException|CException
     */
    public function actionIndex($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListOpenGraph|null $listOpenGraph */
        $listOpenGraph = $list->openGraph;
        if (empty($listOpenGraph)) {
            $listOpenGraph = new ListOpenGraph();
            $listOpenGraph->list_id = (int)$list->list_id;
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($listOpenGraph->getModelName()))) {
            $listOpenGraph->attributes = $attributes;
            if ($listOpenGraph->save()) {
                notify()->addSuccess(t('lists', 'List open graph tags successfully updated!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'    => $this,
                'success'       => notify()->getHasSuccess(),
                'listOpenGraph' => $listOpenGraph,
            ]));
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('lists', 'Your mail list open graph tags'),
            'pageHeading'     => t('lists', 'List open graph tags'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('lists', 'List open graph tags'),
            ],
        ]);

        $this->render('index', compact('list', 'listOpenGraph'));
    }

    /**
     * @param string $list_uid
     *
     * @return Lists
     * @throws CHttpException
     */
    public function loadListModel(string $list_uid): Lists
    {
        $model = Lists::model()->findByAttributes([
            'list_uid'      => $list_uid,
            'customer_id'   => (int)customer()->getId(),
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
