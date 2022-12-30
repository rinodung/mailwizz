<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Sending_domainsController
 *
 * Handles the actions for sending domains related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.7
 */

class Sending_domainsController extends Controller
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
     * List all available sending domains
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $domain = new SendingDomain('search');
        $domain->unsetAttributes();
        $domain->attributes = (array)request()->getQuery($domain->getModelName(), []);

        if ($errors = $domain->getRequirementsErrors()) {
            notify()->addError(t('sending_domains', 'Your system misses a few PHP functions/extensions in order to use this feature.'));
            notify()->addError($errors);
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('sending_domains', 'View sending domains'),
            'pageHeading'     => t('sending_domains', 'View sending domains'),
            'pageBreadcrumbs' => [
                t('sending_domains', 'Sending domains') => createUrl('sending_domains/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('domain'));
    }

    /**
     * Create a new sending domain
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $domain = new SendingDomain();

        if ($errors = $domain->getRequirementsErrors()) {
            notify()->addError(t('sending_domains', 'Your system misses a few PHP functions/extensions in order to use this feature.'));
            notify()->addError($errors);
        }

        if (!$errors && request()->getIsPostRequest() && ($attributes = (array)request()->getPost($domain->getModelName(), []))) {
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
                $this->redirect(['sending_domains/update', 'id' => $domain->domain_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('sending_domains', 'Create new sending domain'),
            'pageHeading'     => t('sending_domains', 'Create new sending domain'),
            'pageBreadcrumbs' => [
                t('sending_domains', 'Sending domains') => createUrl('sending_domains/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('domain'));
    }

    /**
     * Update existing sending domain
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $domain = SendingDomain::model()->findByPk((int)$id);

        if (empty($domain)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($errors = $domain->getRequirementsErrors()) {
            notify()->addError(t('sending_domains', 'Your system misses a few PHP functions/extensions in order to use this feature.'));
            notify()->addError($errors);
        }

        if (!$errors && request()->getIsPostRequest() && ($attributes = (array)request()->getPost($domain->getModelName(), []))) {
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
                $this->redirect(['sending_domains/update', 'id' => $domain->domain_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('sending_domains', 'Update sending domain'),
            'pageHeading'     => t('sending_domains', 'Update sending domain'),
            'pageBreadcrumbs' => [
                t('sending_domains', 'Sending domains') => createUrl('sending_domains/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('domain'));
    }

    /**
     * Verify sending domain
     *
     * @param int $id
     *
     * @return void
     * @throws CHttpException
     */
    public function actionVerify($id)
    {
        $domain = SendingDomain::model()->findByPk((int)$id);
        if (empty($domain)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $found = false;
        try {
            $found = $domain->hasValidDNSTxtRecord();
        } catch (Net_DNS2_Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        if (!$found) {
            notify()->addError(t('sending_domains', 'Unable to find proper TXT record for your domain name, if you just added the records please wait for them to propagate.'));
            $this->redirect(['sending_domains/update', 'id' => $id]);
        }

        $domain->verified = SendingDomain::TEXT_YES;
        $domain->save(false);

        notify()->addSuccess(t('sending_domains', 'Your domain has been successfully verified.'));
        $this->redirect(['sending_domains/update', 'id' => $id]);
    }

    /**
     * Delete existing sending domain
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
        $domain = SendingDomain::model()->findByPk((int)$id);
        if (empty($domain)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $domain->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['sending_domains/index']);
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
