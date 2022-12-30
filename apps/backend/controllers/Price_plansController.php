<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Price_plansController
 *
 * Handles the actions for price plans related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

class Price_plansController extends Controller
{
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
     * List all available price plans
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $pricePlan = new PricePlan('search');
        $pricePlan->unsetAttributes();
        $pricePlan->attributes = (array)request()->getQuery($pricePlan->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('price_plans', 'View price plans'),
            'pageHeading'     => t('price_plans', 'View price plans'),
            'pageBreadcrumbs' => [
                t('price_plans', 'Price plans') => createUrl('price_plans/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('pricePlan'));
    }

    /**
     * Create a new price plan
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $pricePlan        = new PricePlan();
        $pricePlanDisplay = new PricePlanCustomerGroupDisplay();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($pricePlan->getModelName(), []))) {
            $pricePlan->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$pricePlan->getModelName()]['description'])) {
                $pricePlan->description = (string)ioFilter()->purify($post[$pricePlan->getModelName()]['description']);
            }
            if (!$pricePlan->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));

                PricePlanCustomerGroupDisplay::model()->deleteAllByAttributes([
                    'plan_id' => $pricePlan->plan_id,
                ]);

                /** @var array<array> $ids */
                $ids = (array)request()->getPost($pricePlanDisplay->getModelName(), []);
                $ids = (array)($ids['group_id'] ?? []);
                $ids = array_filter(array_unique(array_map('intval', $ids)));

                foreach ($ids as $id) {
                    $relation = new PricePlanCustomerGroupDisplay();
                    $relation->plan_id  = (int)$pricePlan->plan_id;
                    $relation->group_id = (int)$id;
                    $relation->save();
                }
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'pricePlan' => $pricePlan,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['price_plans/update', 'id' => $pricePlan->plan_id]);
            }
        }

        $pricePlan->fieldDecorator->onHtmlOptionsSetup = [$this, '_addEditorOptions'];
        $pricePlanDisplaySelected = [];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('price_plans', 'Create new price plan'),
            'pageHeading'     => t('price_plans', 'Create new price plan'),
            'pageBreadcrumbs' => [
                t('price_plans', 'Price plans') => createUrl('price_plans/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('pricePlan', 'pricePlanDisplay', 'pricePlanDisplaySelected'));
    }

    /**
     * Update existing price plan
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $pricePlan = PricePlan::model()->findByPk((int)$id);

        if (empty($pricePlan)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $pricePlanDisplay = new PricePlanCustomerGroupDisplay();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($pricePlan->getModelName(), []))) {
            $pricePlan->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$pricePlan->getModelName()]['description'])) {
                $pricePlan->description = (string)ioFilter()->purify($post[$pricePlan->getModelName()]['description']);
            }
            if (!$pricePlan->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));

                PricePlanCustomerGroupDisplay::model()->deleteAllByAttributes([
                    'plan_id' => $pricePlan->plan_id,
                ]);

                /** @var array<array> $ids */
                $ids = (array)request()->getPost($pricePlanDisplay->getModelName(), []);
                $ids = (array)($ids['group_id'] ?? []);
                $ids = array_filter(array_unique(array_map('intval', $ids)));

                foreach ($ids as $id) {
                    $relation = new PricePlanCustomerGroupDisplay();
                    $relation->plan_id  = (int)$pricePlan->plan_id;
                    $relation->group_id = (int)$id;
                    $relation->save();
                }
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'pricePlan' => $pricePlan,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['price_plans/update', 'id' => $pricePlan->plan_id]);
            }
        }

        $pricePlan->fieldDecorator->onHtmlOptionsSetup = [$this, '_addEditorOptions'];

        $pricePlanDisplaySelected = [];
        $pricePlanDisplayModels   = PricePlanCustomerGroupDisplay::model()->findAllByAttributes([
            'plan_id' => $pricePlan->plan_id,
        ]);
        foreach ($pricePlanDisplayModels as $model) {
            $pricePlanDisplaySelected[$model->group_id] = ['selected' => 'selected'];
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('price_plans', 'Update price plan'),
            'pageHeading'     => t('price_plans', 'Update price plan'),
            'pageBreadcrumbs' => [
                t('price_plans', 'Price plans') => createUrl('price_plans/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('pricePlan', 'pricePlanDisplay', 'pricePlanDisplaySelected'));
    }

    /**
     * Create a copy of an existing price plan
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCopy($id)
    {
        $pricePlan = PricePlan::model()->findByPk((int)$id);
        if (empty($pricePlan)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($pricePlan->copy()) {
            notify()->addSuccess(t('price_plans', 'Your price plan has been successfully copied!'));
        } else {
            notify()->addError(t('price_plans', 'Unable to copy the price plan!'));
        }

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', ['price_plans/index']));
        }
    }

    /**
     * Delete existing price plan
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
        $pricePlan = PricePlan::model()->findByPk((int)$id);

        if (empty($pricePlan)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $pricePlan->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['price_plans/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $pricePlan,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Autocomplete for price plans
     *
     * @param string $term
     *
     * @return void
     * @throws CException
     */
    public function actionAutocomplete($term)
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['price_plans/index']);
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'plan_id, name';
        $criteria->compare('name', $term, true);
        $criteria->limit = 10;

        $this->renderJson(PricePlanCollection::findAll($criteria)->map(function (PricePlan $model) {
            return ['plan_id' => $model->plan_id, 'value' => $model->name];
        })->all());
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _addEditorOptions(CEvent $event)
    {
        if (!in_array($event->params['attribute'], ['description'])) {
            return;
        }

        $options = [];
        if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
            $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
        }
        $options['id'] = CHtml::activeId($event->sender->owner, $event->params['attribute']);
        $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
    }
}
