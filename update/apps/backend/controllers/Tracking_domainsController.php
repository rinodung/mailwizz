<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Tracking_domainsController
 *
 * Handles the actions for tracking domains related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.6
 */

class Tracking_domainsController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->onBeforeAction = [$this, '_registerJuiBs'];
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
     * List all available tracking domains
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $domain = new TrackingDomain('search');
        $domain->unsetAttributes();

        $domain->attributes = (array)request()->getQuery($domain->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('tracking_domains', 'View tracking domains'),
            'pageHeading'     => t('tracking_domains', 'View tracking domains'),
            'pageBreadcrumbs' => [
                t('tracking_domains', 'Tracking domains') => createUrl('tracking_domains/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('domain'));
    }

    /**
     * Create a new tracking domain
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $domain = new TrackingDomain();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($domain->getModelName(), []))) {
            $domain->attributes = $attributes;
            if (!$domain->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'domain'    => $domain,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['tracking_domains/update', 'id' => $domain->domain_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('tracking_domains', 'Create new tracking domain'),
            'pageHeading'     => t('tracking_domains', 'Create new tracking domain'),
            'pageBreadcrumbs' => [
                t('tracking_domains', 'Tracking domains') => createUrl('tracking_domains/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('domain'));
    }

    /**
     * Update existing tracking domain
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $domain = TrackingDomain::model()->findByPk((int)$id);

        if (empty($domain)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($domain->getModelName(), []))) {
            $domain->attributes = $attributes;
            if (!$domain->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'domain'    => $domain,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['tracking_domains/update', 'id' => $domain->domain_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('tracking_domains', 'Update tracking domain'),
            'pageHeading'     => t('tracking_domains', 'Update tracking domain'),
            'pageBreadcrumbs' => [
                t('tracking_domains', 'Tracking domains') => createUrl('tracking_domains/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('domain'));
    }

    /**
     * Delete existing tracking domain
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
        $domain = TrackingDomain::model()->findByPk((int)$id);
        if (empty($domain)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $domain->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['tracking_domains/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $domain,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _registerJuiBs(CEvent $event)
    {
        if (in_array($event->params['action']->id, ['create', 'update'])) {
            $this->addPageStyles([
                ['src' => apps()->getBaseUrl('assets/css/jui-bs/jquery-ui-1.10.3.custom.css'), 'priority' => -1001],
            ]);
        }
    }
}
