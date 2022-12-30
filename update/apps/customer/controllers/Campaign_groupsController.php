<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Campaign_groupsController
 *
 * Handles the actions for campaign groups related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

class Campaign_groupsController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageCampaigns()) {
            $this->redirect(['dashboard/index']);
        }
    }

    /**
     * @return array
     * @throws CException
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List available campaign groups
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $group = new CampaignGroup('search');

        $group->unsetAttributes();
        $group->attributes  = (array)request()->getQuery($group->getModelName(), []);
        $group->customer_id = (int)customer()->getId();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'View groups'),
            'pageHeading'     => t('campaigns', 'View groups'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('campaigns', 'Groups') => createUrl('campaign_groups/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('group'));
    }

    /**
     * Create a new campaign group
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $group = new CampaignGroup();

        $group->customer_id = (int)customer()->getId();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($group->getModelName(), []))) {
            $group->attributes  = $attributes;
            $group->customer_id = customer()->getId();
            if (!$group->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'group'     => $group,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['campaign_groups/update', 'group_uid' => $group->group_uid]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Create new group'),
            'pageHeading'     => t('campaigns', 'Create new campaign group'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('campaigns', 'Groups') => createUrl('campaign_groups/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('group'));
    }

    /**
     * Update existing campaign group
     *
     * @param string $group_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($group_uid)
    {
        $group = CampaignGroup::model()->findByAttributes([
            'group_uid'    => $group_uid,
            'customer_id'  => (int)customer()->getId(),
        ]);

        if (empty($group)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($group->getModelName(), []))) {
            $group->attributes = $attributes;
            $group->customer_id= customer()->getId();
            if (!$group->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'group'     => $group,
            ]));
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Update group'),
            'pageHeading'     => t('campaigns', 'Update campaign group'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('campaigns', 'Groups') => createUrl('campaign_groups/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('group'));
    }

    /**
     * Delete existing campaign group
     *
     * @param string $group_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($group_uid)
    {
        $group = CampaignGroup::model()->findByAttributes([
            'group_uid'   => $group_uid,
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($group)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $group->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['campaign_groups/index']);
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

    /**
     * Export
     *
     * @return void
     */
    public function actionExport()
    {
        $models = CampaignGroup::model()->findAllByAttributes([
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($models)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('campaign-groups.csv');

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
