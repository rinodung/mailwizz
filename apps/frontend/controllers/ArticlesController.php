<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ArticlesController
 *
 * Handles the actions for artciles related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class ArticlesController extends Controller
{
    /**
     * List available published articles
     *
     * @return void
     */
    public function actionIndex()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('status', Article::STATUS_PUBLISHED);
        $criteria->order = 'article_id DESC';

        $count = Article::model()->count($criteria);

        $pages = new CPagination($count);
        $pages->pageSize = 10;
        $pages->applyLimit($criteria);

        $articles = Article::model()->findAll($criteria);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('articles', 'Helpful articles'),
            'pageBreadcrumbs' => [],
        ]);

        $this->render('index', compact('articles', 'pages'));
    }

    /**
     * List available published articles belonging to a category
     *
     * @param string $slug
     *
     * @return void
     * @throws CHttpException
     */
    public function actionCategory($slug)
    {
        $category = $this->loadCategoryModel($slug);

        $criteria = new CDbCriteria();
        $criteria->compare('t.status', Article::STATUS_PUBLISHED);
        $criteria->with = [
            'activeCategories' => [
                'select'    => 'activeCategories.category_id',
                'together'  => true,
                'joinType'  => 'INNER JOIN',
                'condition' => 'activeCategories.category_id = :cid',
                'params'    => [':cid' => $category->category_id],
            ],
        ];
        $criteria->order = 't.article_id DESC';

        $count = Article::model()->count($criteria);

        $pages = new CPagination($count);
        $pages->pageSize = 10;
        $pages->applyLimit($criteria);

        $articles = Article::model()->findAll($criteria);

        $this->setData([
            'pageMetaTitle'         => $this->getData('pageMetaTitle') . ' | ' . $category->name,
            'pageMetaDescription'   => StringHelper::truncateLength($category->description, 150),
        ]);

        clientScript()->registerLinkTag('canonical', null, createAbsoluteUrl($this->getRoute(), ['slug' => $slug]));
        clientScript()->registerLinkTag('shortlink', null, createAbsoluteUrl($this->getRoute(), ['slug' => $slug]));

        $this->render('category', compact('category', 'articles', 'pages'));
    }

    /**
     * View a single article details
     *
     * @param string $slug
     *
     * @return void
     * @throws CHttpException
     */
    public function actionView($slug)
    {
        $article = $this->loadArticleModel($slug);
        if ($article->status != Article::STATUS_PUBLISHED) {
            if (user()->getId()) {
                notify()->addInfo(t('articles', 'This article is unpublished, only site admins can see it!'));
            } else {
                throw new CHttpException(404, t('app', 'The requested page does not exist.'));
            }
        }

        $this->setData([
            'pageMetaTitle'         => $this->getData('pageMetaTitle') . ' | ' . $article->title,
            'pageMetaDescription'   => StringHelper::truncateLength($article->content, 150),
        ]);

        clientScript()->registerLinkTag('canonical', null, createAbsoluteUrl($this->getRoute(), ['slug' => $slug]));
        clientScript()->registerLinkTag('shortlink', null, createAbsoluteUrl($this->getRoute(), ['slug' => $slug]));

        $this->render('view', compact('article'));
    }

    /**
     * @param string $slug
     *
     * @return ArticleCategory
     * @throws CHttpException
     */
    public function loadCategoryModel(string $slug): ArticleCategory
    {
        $model = ArticleCategory::model()->findByAttributes([
            'slug'      => $slug,
            'status'    => ArticleCategory::STATUS_ACTIVE,
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }

    /**
     * @param string $slug
     *
     * @return Article
     * @throws CHttpException
     */
    public function loadArticleModel(string $slug): Article
    {
        $model = Article::model()->findByAttributes([
            'slug' => $slug,
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
