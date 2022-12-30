<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class HtmlBlocksExtBackendSettingsController extends ExtensionController
{
    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->getExtension()->getPathOfAlias('backend.views.settings');
    }

    /**
     * Default action
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        /** @var HtmlBlocksExtCommon $model */
        $model = container()->get(HtmlBlocksExtCommon::class);

        if (request()->getIsPostRequest()) {
            $model->attributes = (array)ioFilter()->purify(request()->getOriginalPost($model->getModelName(), []));
            if ($model->save()) {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('Html blocks'),
            'pageHeading'     => $this->t('Html blocks'),
            'pageBreadcrumbs' => [
                t('app', 'Extensions') => createUrl('extensions/index'),
                $this->t('Html blocks'),
            ],
        ]);

        $model->fieldDecorator->onHtmlOptionsSetup = [$this, '_addEditorOptions'];

        $this->render('index', compact('model'));
    }

    /**
     * Callback method to set the editor options for email footer in campaigns
     *
     * @return void
     * @param CEvent $event
     */
    public function _addEditorOptions(CEvent $event)
    {
        if (!in_array($event->params['attribute'], ['customer_footer'])) {
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
