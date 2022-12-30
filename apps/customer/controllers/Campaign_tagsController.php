<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Campaign_tagsController
 *
 * Handles the actions for customer campaign tags related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.9
 */

class Campaign_tagsController extends Controller
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
     * List available campaign tags
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $model = new CustomerCampaignTag('search');

        $model->unsetAttributes();
        $model->attributes  = (array)request()->getQuery($model->getModelName(), []);
        $model->customer_id = (int)customer()->getId();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'View campaign tags'),
            'pageHeading'     => t('campaigns', 'View campaign tags'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('campaigns', 'Tags') => createUrl('campaign_tags/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('model'));
    }

    /**
     * Create a new campaign tag
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $model = new CustomerCampaignTag();

        $model->customer_id = (int)customer()->getId();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);

            $model->attributes  = $attributes;
            $model->customer_id = customer()->getId();
            $model->content     = (string)ioFilter()->purify((string)$post[$model->getModelName()]['content']);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'model'     => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['campaign_tags/update', 'tag_uid' => $model->tag_uid]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Create new tag'),
            'pageHeading'     => t('campaigns', 'Create new campaign tag'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('campaigns', 'Tags') => createUrl('campaign_tags/index'),
                t('app', 'Create new'),
            ],
        ]);

        $model->fieldDecorator->onHtmlOptionsSetup = [$this, '_addEditorOptions'];

        $this->render('form', compact('model'));
    }

    /**
     * Update existing campaign tag
     *
     * @param string $tag_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($tag_uid)
    {
        $model = CustomerCampaignTag::model()->findByAttributes([
            'tag_uid'     => $tag_uid,
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);

            $model->attributes  = $attributes;
            $model->customer_id = customer()->getId();
            $model->content     = (string)ioFilter()->purify((string)$post[$model->getModelName()]['content']);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'model'     => $model,
            ]));
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Update tag'),
            'pageHeading'     => t('campaigns', 'Update campaign tag'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('campaigns', 'Tags') => createUrl('campaign_tags/index'),
                t('app', 'Update'),
            ],
        ]);

        $model->fieldDecorator->onHtmlOptionsSetup = [$this, '_addEditorOptions'];

        $this->render('form', compact('model'));
    }

    /**
     * Delete existing campaign tag
     *
     * @param string $tag_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($tag_uid)
    {
        $model = CustomerCampaignTag::model()->findByAttributes([
            'tag_uid'     => $tag_uid,
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $model->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['campaign_tags/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $model,
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
        $models = CustomerCampaignTag::model()->findAllByAttributes([
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($models)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('campaigns-custom-tags.csv');

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

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _addEditorOptions(CEvent $event)
    {
        if (!in_array($event->params['attribute'], ['content'])) {
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
