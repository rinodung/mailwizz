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

class TourExtBackendSlideshowsController extends ExtensionController
{
    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->getExtension()->getPathOfAlias('backend.views.slideshows');
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
     * List all available items
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $slideshow = new TourSlideshow('search');
        $slideshow->unsetAttributes();

        // for filters.
        $slideshow->attributes = (array)request()->getQuery($slideshow->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('View slideshows'),
            'pageHeading'     => $this->t('View slideshows'),
            'pageBreadcrumbs' => [
                t('app', 'Extensions') => createUrl('extensions/index'),
                $this->t('Tour')       => $this->getExtension()->createUrl('settings/index'),
                $this->t('Slideshows') => $this->getExtension()->createUrl('slideshows/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('slideshow'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $slideshow = new TourSlideshow();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($slideshow->getModelName(), []))) {
            $slideshow->attributes = $attributes;

            if (!$slideshow->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'model'     => $slideshow,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect($this->getExtension()->createUrl('slideshows/index'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('View slideshows'),
            'pageHeading'     => $this->t('View slideshows'),
            'pageBreadcrumbs' => [
                t('app', 'Extensions') => createUrl('extensions/index'),
                $this->t('Tour')       => $this->getExtension()->createUrl('settings/index'),
                $this->t('Slideshows') => $this->getExtension()->createUrl('slideshows/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('slideshow'));
    }

    /**
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        /** @var TourSlideshow|null $slideshow */
        $slideshow = TourSlideshow::model()->findByPk((int)$id);

        if (empty($slideshow)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($slideshow->getModelName(), []))) {
            $slideshow->attributes = $attributes;

            if (!$slideshow->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'model'     => $slideshow,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect($this->getExtension()->createUrl('slideshows/index'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('View slideshows'),
            'pageHeading'     => $this->t('View slideshows'),
            'pageBreadcrumbs' => [
                t('app', 'Extensions') => createUrl('extensions/index'),
                $this->t('Tour')       => $this->getExtension()->createUrl('settings/index'),
                $this->t('Slideshows') => $this->getExtension()->createUrl('slideshows/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('slideshow'));
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
        /** @var TourSlideshow|null $slideshow */
        $slideshow = TourSlideshow::model()->findByPk((int)$id);

        if (empty($slideshow)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $slideshow->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', $this->getExtension()->createUrl('slideshows/index'));
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $slideshow,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }
}
