<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PagesController
 *
 * Handles the actions for pages
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.5
 */

class PagesController extends Controller
{
    /**
     * Redirect to home
     *
     * @return void
     */
    public function actionIndex()
    {
        $this->redirect(['site/index']);
    }

    /**
     * @param string $slug
     *
     * @return void
     * @throws CHttpException
     */
    public function actionView($slug)
    {
        $page = $this->loadPageModel($slug);

        if (!$page->getIsActive()) {
            if (user()->getId()) {
                notify()->addInfo(t('pages', 'This page is inactive, only site admins can see it!'));
            } else {
                throw new CHttpException(404, t('app', 'The requested page does not exist.'));
            }
        }

        $this->setData([
            'pageMetaTitle'       => $this->getData('pageMetaTitle') . ' | ' . $page->title,
            'pageMetaDescription' => StringHelper::truncateLength($page->content, 150),
        ]);

        clientScript()->registerLinkTag('canonical', null, createAbsoluteUrl($this->getRoute(), ['slug' => $slug]));
        clientScript()->registerLinkTag('shortlink', null, createAbsoluteUrl($this->getRoute(), ['slug' => $slug]));

        $this->render('view', compact('page'));
    }

    /**
     * @param string $slug
     *
     * @return Page
     * @throws CHttpException
     */
    public function loadPageModel(string $slug): Page
    {
        $model = Page::model()->findByAttributes([
            'slug' => $slug,
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
