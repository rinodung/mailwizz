<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ArticlesController
 *
 * Handles the actions for articles related tasks
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
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('articles.js')]);
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, slug',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all available articles
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $article = new Article('search');
        $article->unsetAttributes();

        // for filters.
        $article->attributes = (array)request()->getQuery($article->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('articles', 'View articles'),
            'pageHeading'     => t('articles', 'View articles'),
            'pageBreadcrumbs' => [
                t('articles', 'Articles') => createUrl('articles/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('article'));
    }

    /**
     * Create a new article
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $article = new Article();
        $articleToCategory = new ArticleToCategory();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($article->getModelName(), []))) {
            $article->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$article->getModelName()]['content'])) {
                $article->content = (string)ioFilter()->purify($post[$article->getModelName()]['content']);
            }
            if (!$article->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                if ($categories = (array)request()->getPost($articleToCategory->getModelName(), [])) {
                    foreach ($categories as $category_id) {
                        $articleToCategory = new ArticleToCategory();
                        $articleToCategory->article_id = (int)$article->article_id;
                        $articleToCategory->category_id = (int)$category_id;
                        $articleToCategory->save();
                    }
                }
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'article'   => $article,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['articles/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('articles', 'Create new article'),
            'pageHeading'     => t('articles', 'Create new article'),
            'pageBreadcrumbs' => [
                t('articles', 'Articles') => createUrl('articles/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('article', 'articleToCategory'));
    }

    /**
     * Update existing article
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $article = Article::model()->findByPk((int)$id);

        if (empty($article)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $articleToCategory = new ArticleToCategory();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($article->getModelName(), []))) {
            $article->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$article->getModelName()]['content'])) {
                $article->content = (string)ioFilter()->purify($post[$article->getModelName()]['content']);
            }
            if (!$article->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                ArticleToCategory::model()->deleteAllByAttributes(['article_id' => $article->article_id]);
                if ($categories = (array)request()->getPost($articleToCategory->getModelName(), [])) {
                    foreach ($categories as $category_id) {
                        $articleToCategory = new ArticleToCategory();
                        $articleToCategory->article_id = (int)$article->article_id;
                        $articleToCategory->category_id = (int)$category_id;
                        $articleToCategory->save();
                    }
                }
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'article'   => $article,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['articles/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('articles', 'Update article'),
            'pageHeading'     => t('articles', 'Update article'),
            'pageBreadcrumbs' => [
                t('articles', 'Articles') => createUrl('articles/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('article', 'articleToCategory'));
    }

    /**
     * Delete an existing article
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
        $article = Article::model()->findByPk((int)$id);

        if (empty($article)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $article->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['articles/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $article,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Generate the slug for an article based on the article title
     *
     * @return void
     * @throws CException
     */
    public function actionSlug()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['articles/index']);
        }

        $article = new Article();
        $article->article_id = (int)request()->getPost('article_id');
        $article->slug = (string)request()->getPost('string');

        $category = new ArticleCategory();
        $category->slug = (string)$article->slug;

        $article->slug = $category->generateSlug();
        $article->slug = $article->generateSlug();

        $this->renderJson(['result' => 'success', 'slug' => $article->slug]);
    }
}
