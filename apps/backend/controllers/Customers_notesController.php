<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Customers_notesController
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

class Customers_notesController extends Controller
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
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $note = new CustomerNote('search');
        $note->unsetAttributes();
        $note->attributes = (array)request()->getQuery($note->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('notes', 'View notes'),
            'pageHeading'     => t('notes', 'View notes'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                t('notes', 'Notes')   => createUrl('customers_notes/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('note'));
    }

    /**
     * Create a new note
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $note = new CustomerNote();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($note->getModelName(), []))) {
            $note->attributes = $attributes;
            $note->user_id    = (int)user()->getId();

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
                $this->redirect(['customers_notes/index']);
            }
        }

        $note->fieldDecorator->onHtmlOptionsSetup = [$this, '_setEditorOptions'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('notes', 'Create new note'),
            'pageHeading'     => t('notes', 'Create new note'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                t('notes', 'Notes') => createUrl('customers_notes/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('note'));
    }

    /**
     * Update existing note
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        /** @var CustomerNote|null $note */
        $note = CustomerNote::model()->findByPk((int)$id);

        if (empty($note)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($note->getModelName(), []))) {
            $note->attributes = $attributes;
            $note->user_id    = (int)user()->getId();

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
                $this->redirect(['customers_notes/index']);
            }
        }

        $note->fieldDecorator->onHtmlOptionsSetup = [$this, '_setEditorOptions'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('notes', 'Update note'),
            'pageHeading'     => t('notes', 'Update note'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                t('notes', 'Notes')   => createUrl('customers_notes/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('note'));
    }

    /**
     * View note
     *
     * @param int $id
     *
     * @return void
     * @throws CHttpException
     */
    public function actionView($id)
    {
        /** @var CustomerNote|null $note */
        $note = CustomerNote::model()->findByPk((int)$id);

        if (empty($note)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('notes', 'View note'),
            'pageHeading'     => t('notes', 'View note'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                t('notes', 'Notes')   => createUrl('customers_notes/index'),
                t('app', 'View'),
            ],
        ]);

        $this->render('view', compact('note'));
    }

    /**
     * Delete existing customer note
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
        /** @var CustomerNote|null $note */
        $note = CustomerNote::model()->findByPk((int)$id);

        if (empty($note)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $note->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            $redirect = request()->getPost('returnUrl', ['customers_notes/index']);
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
}
