<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Lists_overview_widgetsController
 *
 * Handles the actions that fetch the widgets for the campaigns overview page
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.6
 */

class List_overview_widgetsController extends Controller
{
    /**
     * Get the list overview html for the "ListOverviewWidget" widget
     *
     * @param string $list_uid
     * @return void
     * @throws CException
     */
    public function actionIndex($list_uid)
    {
        /** @var Lists|null $list */
        $list = $this->loadListByUid($list_uid);
        if (empty($list)) {
            $this->renderJson([
                'html' => '',
            ]);
            return;
        }

        $this->renderJson([
            'html' => $this->widget('customer.components.web.widgets.list-subscribers.ListOverviewWidget', [
                'list' => $list,
            ], true),
        ]);
    }

    /**
     * Get the list overview html for the "ListSubscribers7DaysActivityWidget" widget
     *
     * @param string $list_uid
     * @return void
     * @throws CException
     */
    public function actionWeekly_activity($list_uid)
    {
        /** @var Lists|null $list */
        $list = $this->loadListByUid($list_uid);
        if (empty($list)) {
            $this->renderJson([
                'html' => '',
            ]);
            return;
        }

        $this->renderJson([
            'html'  => $this->renderPartial('common.views.list_overview_widgets._7days_activity', [
                'list' => $list,
            ], true, true),
        ]);
    }

    /**
     * Get the list overview html for the "ListSubscribersGrowthWidget" widget
     *
     * @param string $list_uid
     * @return void
     * @throws CException
     */
    public function actionSubscribers_growth($list_uid)
    {
        /** @var Lists|null $list */
        $list = $this->loadListByUid($list_uid);
        if (empty($list)) {
            $this->renderJson([
                'html' => '',
            ]);
            return;
        }

        $this->renderJson([
            'html'  => $this->renderPartial('common.views.list_overview_widgets._subscribers_growth', [
                'list' => $list,
            ], true, true),
        ]);
    }

    /**
     * Get the list overview html for the "ListCounterBoxesAveragesWidget" widget
     *
     * @param string $list_uid
     * @return void
     * @throws CException
     */
    public function actionCounter_boxes_averages($list_uid)
    {
        /** @var Lists|null $list */
        $list = $this->loadListByUid($list_uid);
        if (empty($list)) {
            $this->renderJson([
                'html' => '',
            ]);
            return;
        }

        $this->renderJson([
            'html' => $this->widget('customer.components.web.widgets.list-subscribers.ListCounterBoxesAveragesWidget', [
                'list' => $list,
            ], true),
        ]);
    }

    /**
     * @param string $list_uid
     *
     * @return Lists|null
     */
    public function loadListByUid(string $list_uid): ?Lists
    {
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)customer()->getId());
        $criteria->compare('list_uid', $list_uid);
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE]);

        /** @var Lists|null $model */
        $model = Lists::model()->find($criteria);

        return $model;
    }

    /**
     * @param CAction $action
     *
     * @return bool
     * @throws CException
     */
    protected function beforeAction($action)
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
            return false;
        }

        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageLists()) {
            $this->redirect(['dashboard/index']);
        }

        return parent::beforeAction($action);
    }
}
