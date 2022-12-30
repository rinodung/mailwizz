<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Customer_groupsController
 *
 * Handles the actions for customer groups related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

class Customer_groupsController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('customer-groups.js')]);
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, copy',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List available customer groups
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $group = new CustomerGroup('search');
        $group->unsetAttributes();
        $group->attributes = (array)request()->getQuery($group->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'View groups'),
            'pageHeading'     => t('customers', 'View groups'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                t('customers', 'Groups')    => createUrl('customer_groups/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('group'));
    }

    /**
     * Create a new customer group
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $group   = new CustomerGroup();

        $common          = new CustomerGroupOptionCommon();
        $servers         = new CustomerGroupOptionServers();
        $trackingDomains = new CustomerGroupOptionTrackingDomains();
        $sendingDomains  = new CustomerGroupOptionSendingDomains();
        $lists           = new CustomerGroupOptionLists();
        $campaigns       = new CustomerGroupOptionCampaigns();
        $surveys         = new CustomerGroupOptionSurveys();
        $quotaCounters   = new CustomerGroupOptionQuotaCounters();
        $sending         = new CustomerGroupOptionSending();
        $cdn             = new CustomerGroupOptionCdn();
        $api             = new CustomerGroupOptionApi();
        $subaccounts     = new CustomerGroupOptionSubaccounts();

        $models = [
            'common'          => $common,
            'servers'         => $servers,
            'trackingDomains' => $trackingDomains,
            'sendingDomains'  => $sendingDomains,
            'lists'           => $lists,
            'campaigns'       => $campaigns,
            'surveys'         => $surveys,
            'quotaCounters'   => $quotaCounters,
            'sending'         => $sending,
            'cdn'             => $cdn,
            'api'             => $api,
            'subaccounts'     => $subaccounts,
        ];

        $models = (array)hooks()->applyFilters('backend_controller_customer_groups_option_models', $models);

        $criteria = new CDbCriteria();
        $criteria->addCondition('customer_id IS NULL');
        $criteria->addInCondition('status', [DeliveryServer::STATUS_ACTIVE, DeliveryServer::STATUS_IN_USE]);
        $allDeliveryServers      = DeliveryServer::model()->findAll($criteria);
        $assignedDeliveryServers = [];
        $deliveryServerToCustomerGroup = new DeliveryServerToCustomerGroup();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($group->getModelName(), []))) {
            $transaction = db()->beginTransaction();
            $error = $success = null;

            try {
                $group->attributes = $attributes;
                if (!$group->save()) {
                    throw new Exception($error = t('app', 'Your form has a few errors, please fix them and try again!'));
                }
                $success = t('app', 'Your form has been successfully saved!');

                /** @var array $post */
                $post = (array)request()->getOriginalPost('', []);

                /**
                 * @var string $key
                 * @var OptionBase $model
                 */
                foreach ($models as $key => $model) {
                    /** @phpstan-ignore-next-line */
                    $model->setGroup($group);
                    $model->attributes = (array)request()->getPost($model->getModelName(), []);

                    if ($model instanceof CustomerGroupOptionCampaigns && isset($post[$model->getModelName()]['email_header'])) {
                        $model->email_header = (string)ioFilter()->purify($post[$model->getModelName()]['email_header']);
                    }

                    if ($model instanceof CustomerGroupOptionCampaigns && isset($post[$model->getModelName()]['email_footer'])) {
                        $model->email_footer = (string)ioFilter()->purify($post[$model->getModelName()]['email_footer']);
                    }

                    if ($model instanceof CustomerGroupOptionCommon && isset($post[$model->getModelName()]['notification_message'])) {
                        $model->notification_message = (string)ioFilter()->purify($post[$model->getModelName()]['notification_message']);
                    }

                    if ($model instanceof CustomerGroupOptionSending && isset($post[$model->getModelName()]['quota_notify_email_content'])) {
                        $model->quota_notify_email_content = (string)ioFilter()->purify($post[$model->getModelName()]['quota_notify_email_content']);
                    }

                    if (!$model->save()) {
                        $error = true;
                    }
                }

                $assignedDeliveryServers = array_map('intval', (array)request()->getPost($deliveryServerToCustomerGroup->getModelName(), []));

                if ($error) {
                    throw new Exception($error = t('app', 'Your form has a few errors, please fix them and try again!'));
                }

                foreach ($assignedDeliveryServers as $server_id) {
                    $exists = DeliveryServer::model()->findByPk((int)$server_id);
                    if (empty($exists)) {
                        continue;
                    }
                    $deliveryServerToGroup = new DeliveryServerToCustomerGroup();
                    $deliveryServerToGroup->group_id  = (int)$group->group_id;
                    $deliveryServerToGroup->server_id = (int)$server_id;
                    $deliveryServerToGroup->save(false);
                }

                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollback();
                $error = $e->getMessage();
                $success = null;
            }

            if ($success) {
                notify()->addSuccess($success);
            } else {
                notify()->addError($error);
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'group'     => $group,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['customer_groups/update', 'id' => $group->group_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'Create new group'),
            'pageHeading'     => t('customers', 'Create new customer group'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                t('customers', 'Groups')    => createUrl('customer_groups/index'),
                t('app', 'Create new'),
            ],
        ]);

        $campaigns->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];
        $common->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];
        $sending->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];

        // Getting tabs for generate them dynamically so we can hook into and add more
        $tabs = $this->getTabs($models);

        $this->render('form', CMap::mergeArray($models, compact(
            'group',
            'tabs',
            'allDeliveryServers',
            'assignedDeliveryServers',
            'deliveryServerToCustomerGroup'
        )));
    }

    /**
     * Update existing customer group
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $group = CustomerGroup::model()->findByPk((int)$id);

        if (empty($group)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $common = new CustomerGroupOptionCommon();
        $common->setGroup($group);

        $servers = new CustomerGroupOptionServers();
        $servers->setGroup($group);

        $trackingDomains = new CustomerGroupOptionTrackingDomains();
        $trackingDomains->setGroup($group);

        $sendingDomains = new CustomerGroupOptionSendingDomains();
        $sendingDomains->setGroup($group);

        $lists = new CustomerGroupOptionLists();
        $lists->setGroup($group);

        $campaigns = new CustomerGroupOptionCampaigns();
        $campaigns->setGroup($group);

        $surveys = new CustomerGroupOptionSurveys();
        $surveys->setGroup($group);

        $quotaCounters = new CustomerGroupOptionQuotaCounters();
        $quotaCounters->setGroup($group);

        $sending = new CustomerGroupOptionSending();
        $sending->setGroup($group);

        $cdn = new CustomerGroupOptionCdn();
        $cdn->setGroup($group);

        $api = new CustomerGroupOptionApi();
        $api->setGroup($group);

        $subaccounts = new CustomerGroupOptionSubaccounts();
        $subaccounts->setGroup($group);

        $models = [
            'common'          => $common,
            'servers'         => $servers,
            'trackingDomains' => $trackingDomains,
            'sendingDomains'  => $sendingDomains,
            'lists'           => $lists,
            'campaigns'       => $campaigns,
            'surveys'         => $surveys,
            'quotaCounters'   => $quotaCounters,
            'sending'         => $sending,
            'cdn'             => $cdn,
            'api'             => $api,
            'subaccounts'     => $subaccounts,
        ];

        $models = (array)hooks()->applyFilters('backend_controller_customer_groups_option_models', $models);

        $criteria = new CDbCriteria();
        $criteria->addCondition('customer_id IS NULL');
        $criteria->addInCondition('status', [DeliveryServer::STATUS_ACTIVE, DeliveryServer::STATUS_IN_USE]);
        $allDeliveryServers      = DeliveryServer::model()->findAll($criteria);
        $assignedDeliveryServers = [];
        $deliveryServerToCustomerGroup = new DeliveryServerToCustomerGroup();
        $assignedDeliveryServersModels = DeliveryServerToCustomerGroup::model()->findAllByAttributes(['group_id' => (int)$group->group_id]);
        foreach ($assignedDeliveryServersModels as $mdl) {
            $assignedDeliveryServers[] = (int)$mdl->server_id;
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($group->getModelName(), []))) {
            $transaction = db()->beginTransaction();
            $error = $success = null;

            try {
                $group->attributes = $attributes;
                if (!$group->save()) {
                    throw new Exception($error = t('app', 'Your form has a few errors, please fix them and try again!'));
                }
                $success = t('app', 'Your form has been successfully saved!');

                /** @var array $post */
                $post = (array)request()->getOriginalPost('', []);

                /**
                 * @var string $key
                 * @var OptionBase $model
                 */
                foreach ($models as $key => $model) {
                    $model->attributes = (array)request()->getPost($model->getModelName(), []);

                    if ($model instanceof CustomerGroupOptionCampaigns && isset($post[$model->getModelName()]['email_header'])) {
                        $model->email_header = (string)ioFilter()->purify($post[$model->getModelName()]['email_header']);
                    }

                    if ($model instanceof CustomerGroupOptionCampaigns && isset($post[$model->getModelName()]['email_footer'])) {
                        $model->email_footer = (string)ioFilter()->purify($post[$model->getModelName()]['email_footer']);
                    }

                    if ($model instanceof CustomerGroupOptionCommon && isset($post[$model->getModelName()]['notification_message'])) {
                        $model->notification_message = (string)ioFilter()->purify($post[$model->getModelName()]['notification_message']);
                    }

                    if ($model instanceof CustomerGroupOptionSending && isset($post[$model->getModelName()]['quota_notify_email_content'])) {
                        $model->quota_notify_email_content = (string)ioFilter()->purify($post[$model->getModelName()]['quota_notify_email_content']);
                    }

                    if (!$model->save()) {
                        $error = true;
                    }
                }

                $assignedDeliveryServers = array_map('intval', (array)request()->getPost($deliveryServerToCustomerGroup->getModelName(), []));
                DeliveryServerToCustomerGroup::model()->deleteAllByAttributes(['group_id' => (int)$group->group_id]);

                if ($error) {
                    throw new Exception($error = t('app', 'Your form has a few errors, please fix them and try again!'));
                }

                foreach ($assignedDeliveryServers as $server_id) {
                    $exists = DeliveryServer::model()->findByPk((int)$server_id);
                    if (empty($exists)) {
                        continue;
                    }
                    $deliveryServerToGroup = new DeliveryServerToCustomerGroup();
                    $deliveryServerToGroup->group_id  = (int)$group->group_id;
                    $deliveryServerToGroup->server_id = (int)$server_id;
                    $deliveryServerToGroup->save(false);
                }

                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollback();
                $error = $e->getMessage();
                $success = null;
            }

            if ($success) {
                notify()->addSuccess($success);
            } else {
                notify()->addError($error);
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'group'     => $group,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['customer_groups/update', 'id' => $group->group_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'Update group'),
            'pageHeading'     => t('customers', 'Update customer group'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                t('customers', 'Groups')    => createUrl('customer_groups/index'),
                t('app', 'Update'),
            ],
        ]);

        $campaigns->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];
        $common->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];
        $sending->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];

        // Getting tabs for generate them dynamically so we can hook into and add more
        $tabs = $this->getTabs($models);

        $this->render('form', CMap::mergeArray($models, compact(
            'group',
            'tabs',
            'allDeliveryServers',
            'assignedDeliveryServers',
            'deliveryServerToCustomerGroup'
        )));
    }

    /**
     * Copy customer group
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCopy($id)
    {
        $group = CustomerGroup::model()->findByPk((int)$id);

        if (empty($group)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $group->copy();

        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('campaigns', 'Your customer group was successfully copied!'));
            $this->redirect(request()->getPost('returnUrl', ['customer_groups/index']));
        }
    }

    /**
     * Delete existing customer group
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
        $group = CustomerGroup::model()->findByPk((int)$id);

        if (empty($group)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $delete = true;

        /** @var OptionCustomerRegistration $optionCustomerRegistration */
        $optionCustomerRegistration = container()->get(OptionCustomerRegistration::class);
        if ($group->group_id == $optionCustomerRegistration->getDefaultGroup()) {
            notify()->addWarning(t('app', 'This group cannot be removed since it is the default group for registration process'));
            $delete = false;
        }

        /** @var OptionCustomerSending $optionCustomerSending */
        $optionCustomerSending = container()->get(OptionCustomerSending::class);
        if ($delete && $group->group_id == $optionCustomerSending->getMoveToGroupId()) {
            notify()->addWarning(t('app', 'This group cannot be removed since it is used for moving customers in when their quota is reached'));
            $delete = false;
        }

        if ($delete) {
            $criteria = new CDbCriteria();
            $criteria->compare('t.code', 'system.customer_sending.move_to_group_id');
            $criteria->compare('t.value', $group->group_id);
            $criteria->addCondition('t.group_id != :gid');
            $criteria->params[':gid'] = (int)$group->group_id;
            $model = CustomerGroupOption::model()->find($criteria);
            if (!empty($model)) {
                $delete = false;
            }
        }

        if ($delete) {
            $group->preDeleteCheckDone = true;
            $group->delete();
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
        }

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            $redirect = request()->getPost('returnUrl', ['customer_groups/index']);
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
     * Reset sending quota
     *
     * @param int $id
     *
     * @return void
     * @throws CDbException
     * @throws CHttpException
     */
    public function actionReset_sending_quota($id)
    {
        $group = CustomerGroup::model()->findByPk((int)$id);

        if (empty($group)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $group->resetSendingQuota();

        notify()->addSuccess(t('customers', 'The sending quota has been successfully reseted!'));

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', ['customer_groups/index']));
        }
    }

    /**
     * Callback method to set the editor options for email footer in campaigns
     *
     * @param CEvent $event
     *
     * @return void
     */
    public function _setupEditorOptions(CEvent $event)
    {
        if (!in_array($event->params['attribute'], ['email_header', 'email_footer', 'notification_message', 'quota_notify_email_content'])) {
            return;
        }

        $options = [];
        if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
            $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
        }
        $options['id'] = CHtml::activeId($event->sender->owner, $event->params['attribute']);

        if ($event->params['attribute'] == 'notification_message') {
            $options['height'] = 100;
        }

        if ($event->params['attribute'] == 'quota_notify_email_content') {
            $options['height'] = 200;
        }

        $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
    }

    /**
     * @param array $models
     * @return array
     */
    protected function getTabs(array $models): array
    {
        $tabs = [
            [
                'id'    => 'common',
                'label' => t('settings', 'Common'),
                'view'  => 'option-views/_common',
                'model' => $models['common'],
            ],
            [
                'id'    => 'servers',
                'label' => t('settings', 'Servers'),
                'view'  => 'option-views/_servers',
                'model' => $models['servers'],
            ],
            [
                'id'    => 'tracking-domains',
                'label' => t('settings', 'Tracking domains'),
                'view'  => 'option-views/_tracking-domains',
                'model' => $models['trackingDomains'],
            ],
            [
                'id'    => 'sending-domains',
                'label' => t('settings', 'Sending domains'),
                'view'  => 'option-views/_sending-domains',
                'model' => $models['sendingDomains'],
            ],
            [
                'id'    => 'lists',
                'label' => t('settings', 'Lists'),
                'view'  => 'option-views/_lists',
                'model' => $models['lists'],
            ],
            [
                'id'    => 'campaigns',
                'label' => t('settings', 'Campaigns'),
                'view'  => 'option-views/_campaigns',
                'model' => $models['campaigns'],
            ],
            [
                'id'    => 'surveys',
                'label' => t('settings', 'Surveys'),
                'view'  => 'option-views/_surveys',
                'model' => $models['surveys'],
            ],
            [
                'id'    => 'quota-counters',
                'label' => t('settings', 'Quota counters'),
                'view'  => 'option-views/_quota',
                'model' => $models['quotaCounters'],
            ],
            [
                'id'    => 'sending',
                'label' => t('settings', 'Sending'),
                'view'  => 'option-views/_sending',
                'model' => $models['sending'],
            ],
            [
                'id'    => 'cdn',
                'label' => t('settings', 'CDN'),
                'view'  => 'option-views/_cdn',
                'model' => $models['cdn'],
            ],
            [
                'id'    => 'api',
                'label' => t('settings', 'Api'),
                'view'  => 'option-views/_api',
                'model' => $models['api'],
            ],
            [
                'id'    => 'subaccounts',
                'label' => t('settings', 'Subaccounts'),
                'view'  => 'option-views/_subaccounts',
                'model' => $models['subaccounts'],
            ],
        ];

        return (array)hooks()->applyFilters('backend_controller_customer_groups_option_tabs', $tabs, $models);
    }
}
