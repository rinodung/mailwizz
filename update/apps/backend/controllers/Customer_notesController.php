<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Customer_notesController
 *
 * Handles the actions for customer notes related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.8
 */

class Customer_notesController extends Controller
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
     * List customer notes
     *
     * @param string $customer_uid
     * @return void
     * @throws CException
     */
    public function actionIndex($customer_uid)
    {
        /** @var Customer $customer */
        $customer = $this->loadCustomerModel((string)$customer_uid);

        $note = new CustomerNote('search');
        $note->unsetAttributes();
        $note->attributes  = (array)request()->getQuery($note->getModelName(), []);
        $note->customer_id = (int)$customer->customer_id;

        $pageHeading = t('notes', 'View notes for {name}', ['{name}' => $customer->getFullName()]);
        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $pageHeading,
            'pageHeading'     => $pageHeading,
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                $customer->getFullName()   => createUrl('customers/update', ['id' => $customer->customer_id]),
                t('notes', 'Notes')   => createUrl('customer_notes/index', ['customer_uid' => $customer_uid]),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('note', 'customer'));
    }

    /**
     * Create a new note
     *
     * @param string $customer_uid
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCreate($customer_uid)
    {
        /** @var Customer $customer */
        $customer = $this->loadCustomerModel((string)$customer_uid);

        $note = new CustomerNote();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($note->getModelName(), []))) {
            $note->attributes  = $attributes;
            $note->user_id     = (int)user()->getId();
            $note->customer_id = (int)$customer->customer_id;

            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            $note->note = (string)ioFilter()->purify($post[$note->getModelName()]['note']);

            if (!$note->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'model'     => $note,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['customer_notes/index', 'customer_uid' => $customer_uid]);
            }
        }

        $note->fieldDecorator->onHtmlOptionsSetup = [$this, '_setEditorOptions'];

        $pageHeading = t('notes', 'Create new note for {name}', ['{name}' => $customer->getFullName()]);
        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $pageHeading,
            'pageHeading'     => $pageHeading,
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                $customer->getFullName()   => createUrl('customers/update', ['id' => $customer->customer_id]),
                t('notes', 'Notes')   => createUrl('customer_notes/index', ['customer_uid' => $customer_uid]),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('note', 'customer'));
    }

    /**
     * Update existing note
     *
     * @param string $customer_uid
     * @param string $note_uid
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($customer_uid, $note_uid)
    {
        /** @var Customer $customer */
        $customer = $this->loadCustomerModel((string)$customer_uid);

        /** @var CustomerNote|null $note */
        $note = CustomerNote::model()->findByAttributes([
            'customer_id'  => (int)$customer->customer_id,
            'note_uid'     => (string)$note_uid,
        ]);

        if (empty($note)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($note->getModelName(), []))) {
            $note->attributes  = $attributes;
            $note->user_id     = (int)user()->getId();
            $note->customer_id = (int)$customer->customer_id;

            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            $note->note = (string)ioFilter()->purify($post[$note->getModelName()]['note']);

            if (!$note->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'model'     => $note,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['customer_notes/index', 'customer_uid' => $customer_uid]);
            }
        }

        $note->fieldDecorator->onHtmlOptionsSetup = [$this, '_setEditorOptions'];

        $pageHeading = t('notes', 'Update note for {name}', ['{name}' => $customer->getFullName()]);
        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $pageHeading,
            'pageHeading'     => $pageHeading,
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                $customer->getFullName()   => createUrl('customers/update', ['id' => $customer->customer_id]),
                t('notes', 'Notes')   => createUrl('customer_notes/index', ['customer_uid' => $customer_uid]),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('note', 'customer'));
    }

    /**
     * View note
     *
     * @param string $customer_uid
     * @param string $note_uid
     * @return void
     * @throws CHttpException
     */
    public function actionView($customer_uid, $note_uid)
    {
        /** @var Customer $customer */
        $customer = $this->loadCustomerModel((string)$customer_uid);

        /** @var CustomerNote|null $note */
        $note = CustomerNote::model()->findByAttributes([
            'customer_id' => (int)$customer->customer_id,
            'note_uid'    => (string)$note_uid,
        ]);

        if (empty($note)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $pageHeading = t('notes', 'View note for {name}', ['{name}' => $customer->getFullName()]);
        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $pageHeading,
            'pageHeading'     => $pageHeading,
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                $customer->getFullName()   => createUrl('customers/update', ['id' => $customer->customer_id]),
                t('notes', 'Notes')   => createUrl('customer_notes/index', ['customer_uid' => $customer_uid]),
                t('app', 'View'),
            ],
        ]);

        $this->render('view', compact('note', 'customer'));
    }

    /**
     * Delete existing customer note
     *
     * @param string $customer_uid
     * @param string $note_uid
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($customer_uid, $note_uid)
    {
        /** @var Customer $customer */
        $customer = $this->loadCustomerModel((string)$customer_uid);

        /** @var CustomerNote|null $note */
        $note = CustomerNote::model()->findByAttributes([
            'customer_id' => (int)$customer->customer_id,
            'note_uid'     => (string)$note_uid,
        ]);

        if (empty($note)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $note->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            $redirect = request()->getPost('returnUrl', ['customer_notes/index', 'customer_uid' => $customer_uid]);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $note,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Callback method to set the editor options for email footer in campaigns
     *
     * @return void
     * @param CEvent $event
     */
    public function _setEditorOptions(CEvent $event)
    {
        if (!in_array($event->params['attribute'], ['note'])) {
            return;
        }

        $options = [];
        if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
            $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
        }
        $options['id'] = CHtml::activeId($event->sender->owner, $event->params['attribute']);

        if ($event->params['attribute'] === 'note') {
            $options['height'] = 300;
        }

        $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
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

    /**
     * @param string $customer_uid
     *
     * @return Customer
     * @throws CHttpException
     */
    public function loadCustomerModel(string $customer_uid): Customer
    {
        $model = Customer::model()->findByAttributes([
            'customer_uid' => $customer_uid,
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
