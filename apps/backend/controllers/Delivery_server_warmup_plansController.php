<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Delivery_server_warmup_plansController
 *
 * Handles the actions for delivery servers warmup plans related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.10
 */

class Delivery_server_warmup_plansController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        $this->addPageScript(['src' => apps()->getBaseUrl('assets/js/delivery-server-warmup-plans.js')]);
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, activate',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List delivery server warmup plans
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $plan = new DeliveryServerWarmupPlan('search');
        $plan->unsetAttributes();
        $plan->attributes = (array)request()->getQuery($plan->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('warmup_plans', 'View plans'),
            'pageHeading'     => t('warmup_plans', 'View plans'),
            'pageBreadcrumbs' => [
                t('warmup_plans', 'Warmup plans')   => createUrl('delivery_server_warmup_plans/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('plan'));
    }

    /**
     * Create a new delivery server warmup plan
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $plan = new DeliveryServerWarmupPlan();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($plan->getModelName(), []))) {
            $plan->attributes = $attributes;

            $success = false;
            $transaction = db()->beginTransaction();
            try {
                if (!$plan->save()) {
                    throw new Exception(t('warmup_plans', 'Could not save the plan'));
                }

                if (!$plan->createSchedules()) {
                    throw new Exception(t('warmup_plans', 'Could not save the schedules'));
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
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'model'     => $plan,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['delivery_server_warmup_plans/update', 'id' => $plan->plan_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('warmup_plans', 'Create new plan'),
            'pageHeading'     => t('warmup_plans', 'Create new plan'),
            'pageBreadcrumbs' => [
                t('warmup_plans', 'Warmup plans') => createUrl('delivery_server_warmup_plans/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('plan'));
    }

    /**
     * Update existing delivery server warmup plan
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        /** @var DeliveryServerWarmupPlan $plan */
        $plan = $this->loadModel((int)$id);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($plan->getModelName(), []))) {
            $plan->attributes = $attributes;

            $success = false;
            $transaction = db()->beginTransaction();
            try {
                if (!$plan->save()) {
                    throw new Exception(t('warmup_plans', 'Could not save the plan'));
                }
                if (!$plan->getIsActive()) {
                    if (!$plan->createSchedules()) {
                        throw new Exception(t('warmup_plans', 'Could not save the schedules'));
                    }
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
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'model'     => $plan,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['delivery_server_warmup_plans/update', 'id' => $plan->plan_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('warmup_plans', 'Update plan'),
            'pageHeading'     => t('warmup_plans', 'Update plan'),
            'pageBreadcrumbs' => [
                t('warmup_plans', 'Warmup plans')   => createUrl('delivery_server_warmup_plans/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('plan'));
    }

    /**
     * Delete existing delivery server warmup plan
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
        /** @var DeliveryServerWarmupPlan $plan */
        $plan = $this->loadModel((int)$id);

        $plan->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            $redirect = request()->getPost('returnUrl', ['delivery_server_warmup_plans/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $plan,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Activate existing delivery server warmup plan
     *
     * @param int $id
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     * @throws Exception
     */
    public function actionActivate($id)
    {
        /** @var DeliveryServerWarmupPlan $plan */
        $plan = $this->loadModel((int)$id);

        if ($plan->saveStatus(DeliveryServerWarmupPlan::STATUS_ACTIVE)) {
            notify()->addSuccess(t('warmup_plans', 'Your warmup plan was successfully activated.'));
        }

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            $redirect = request()->getPost('returnUrl', ['delivery_server_warmup_plans/update', 'id' => $plan->plan_id]);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $plan,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     *  Autocomplete for search
     *
     * @param string $term
     *
     * @return void
     * @throws CException
     */
    public function actionAutocomplete($term)
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['delivery_server_warmup_plans/index']);
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'customer_id, plan_id, name';
        $criteria->compare('name', $term, true);
        $criteria->compare('status', DeliveryServerWarmupPlan::STATUS_ACTIVE);
        $criteria->limit = 10;

        $this->renderJson(DeliveryServerWarmupPlanCollection::findAll($criteria)->map(function (DeliveryServerWarmupPlan $model) {
            return [
                'warmup_plan_id' => $model->plan_id,
                'value'          => $model->getNameWithCustomer(),
            ];
        })->all());
    }

    /**
     * @param int $id
     *
     * @return DeliveryServerWarmupPlan
     * @throws CHttpException
     */
    protected function loadModel(int $id): DeliveryServerWarmupPlan
    {
        $model = DeliveryServerWarmupPlan::model()->findByPk($id);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
