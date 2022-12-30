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
 * @since 1.0
 */

class TourExtBackendSlideshowSlidesController extends ExtensionController
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        /** @var TourExt $extension */
        $extension = $this->getExtension();

        $this->addPageScript(['src' => $extension->getAssetsUrl() . '/js/tour.js']);
        parent::init();
    }

    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->getExtension()->getPathOfAlias('backend.views.slideshow-slides');
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
     * @param int $slideshow_id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionIndex($slideshow_id)
    {
        /** @var TourSlideshow|null $slideshow */
        $slideshow = TourSlideshow::model()->findByPk((int)$slideshow_id);

        if (empty($slideshow)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $slide = new TourSlideshowSlide('search');
        $slide->unsetAttributes();

        // for filters.
        $slide->attributes   = (array)request()->getQuery($slide->getModelName(), []);
        $slide->slideshow_id = (int)$slideshow->slideshow_id;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('View all slides'),
            'pageHeading'     => $this->t('{name} slides', ['{name}' => $slideshow->name]),
            'pageBreadcrumbs' => [
                t('app', 'Extensions') => createUrl('extensions/index'),
                $this->t('Tour')       => $this->getExtension()->createUrl('settings/index'),
                $this->t('Slideshows') => $this->getExtension()->createUrl('slideshows/index'),
                $slideshow->name . ' ' => $this->getExtension()->createUrl('slideshows/update', ['id' => $slideshow->slideshow_id]),
                $this->t('View all slides'),
            ],
        ]);

        $this->render('list', compact('slideshow', 'slide'));
    }

    /**
     * @param int $slideshow_id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCreate($slideshow_id)
    {
        /** @var TourSlideshow|null $slideshow */
        $slideshow = TourSlideshow::model()->findByPk((int)$slideshow_id);

        if (empty($slideshow)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        /** @var TourSlideshowSlide $slide */
        $slide = new TourSlideshowSlide();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($slide->getModelName(), []))) {
            $slide->attributes   = $attributes;
            $slide->slideshow_id = (int)$slideshow->slideshow_id;

            /** @var array $post */
            $post = request()->getOriginalPost('', []);
            if (isset($post[$slide->getModelName()]['content'])) {
                $slide->content = (string)ioFilter()->purify($post[$slide->getModelName()]['content']);
            }

            if (!$slide->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'model'     => $slide,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect($this->getExtension()->createUrl('slideshow_slides/index', ['slideshow_id' => $slideshow->slideshow_id]));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('Create slide'),
            'pageHeading'     => $this->t('Create new slide'),
            'pageBreadcrumbs' => [
                t('app', 'Extensions') => createUrl('extensions/index'),
                $this->t('Tour')       => $this->getExtension()->createUrl('settings/index'),
                $this->t('Slideshows') => $this->getExtension()->createUrl('slideshows/index'),
                $slideshow->name . ' ' => $this->getExtension()->createUrl('slideshows/update', ['id' => $slideshow->slideshow_id]),
                $this->t('Create slide'),
            ],
        ]);

        $slide->fieldDecorator->onHtmlOptionsSetup = [$this, '_setEditorOptions'];

        $this->render('form', compact('slideshow', 'slide'));
    }

    /**
     * @param int $slideshow_id
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($slideshow_id, $id)
    {
        /** @var TourSlideshow|null $slideshow */
        $slideshow = TourSlideshow::model()->findByPk((int)$slideshow_id);

        if (empty($slideshow)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        /** @var TourSlideshowSlide|null $slide */
        $slide = TourSlideshowSlide::model()->findByPk((int)$id);

        if (empty($slide)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($slide->getModelName(), []))) {
            $slide->attributes   = $attributes;
            $slide->slideshow_id = (int)$slideshow->slideshow_id;

            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$slide->getModelName()]['content'])) {
                $slide->content = (string)ioFilter()->purify($post[$slide->getModelName()]['content']);
            }

            if (!$slide->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'model'     => $slide,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect($this->getExtension()->createUrl('slideshow_slides/index', ['slideshow_id' => $slideshow->slideshow_id]));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('Update slide'),
            'pageHeading'     => $this->t('Update slide'),
            'pageBreadcrumbs' => [
                t('app', 'Extensions') => createUrl('extensions/index'),
                $this->t('Tour')       => $this->getExtension()->createUrl('settings/index'),
                $this->t('Slideshows') => $this->getExtension()->createUrl('slideshows/index'),
                $slideshow->name . ' ' => $this->getExtension()->createUrl('slideshows/update', ['id' => $slideshow->slideshow_id]),
                $this->t('Update slide'),
            ],
        ]);

        $slide->fieldDecorator->onHtmlOptionsSetup = [$this, '_setEditorOptions'];

        $this->render('form', compact('slideshow', 'slide'));
    }

    /**
     * @param int $id
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($id)
    {
        /** @var TourSlideshowSlide|null $model */
        $model = TourSlideshowSlide::model()->findByPk((int)$id);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $model->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', $this->getExtension()->createUrl('slideshows/index'));
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
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function _setEditorOptions(CEvent $event)
    {
        if (!in_array($event->params['attribute'], ['content'])) {
            return;
        }

        /** @var TourExt $extension */
        $extension = $this->getExtension();

        $options = [];
        if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
            $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
        }
        $options['id']          = CHtml::activeId($event->sender->owner, $event->params['attribute']);
        $options['contentsCss'] = [
            'https://fonts.googleapis.com/css?family=Raleway',
            $extension->getAssetsUrl() . '/css/editor.css',
        ];
        $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
    }
}
