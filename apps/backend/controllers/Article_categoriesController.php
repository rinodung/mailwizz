<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Article_categoriesController
 *
 * Handles the actions for articles categories related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class Article_categoriesController extends Controller
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
     * List all the available article categories
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $category = new ArticleCategory('search');
        $category->unsetAttributes();

        // for filters.
        $category->attributes = (array)request()->getQuery($category->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('articles', 'View article categories'),
            'pageHeading'     => t('articles', 'View article categories'),
            'pageBreadcrumbs' => [
                t('articles', 'Articles')      => createUrl('articles/index'),
                t('articles', 'Categories')    => createUrl('article_categories/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('category'));
    }

    /**
     * Create a new article category
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $category   = new ArticleCategory();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($category->getModelName(), []))) {
            $category->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$category->getModelName()]['description'])) {
                $category->description = (string)ioFilter()->purify($post[$category->getModelName()]['description']);
            }
            if (!$category->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'category'  => $category,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['article_categories/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('articles', 'Create new article category'),
            'pageHeading'     => t('articles', 'Create new article category'),
            'pageBreadcrumbs' => [
                t('articles', 'Articles')      => createUrl('articles/index'),
                t('articles', 'Categories')    => createUrl('article_categories/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('category'));
    }

    /**
     * Update existing article category
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $category = ArticleCategory::model()->findByPk((int)$id);

        if (empty($category)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($category->getModelName(), []))) {
            $category->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$category->getModelName()]['description'])) {
                $category->description = (string)ioFilter()->purify($post[$category->getModelName()]['description']);
            }
            if (!$category->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'category'  => $category,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['article_categories/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'         => $this->getData('pageMetaTitle') . ' | ' . t('articles', 'Update article category'),
            'pageHeading'           => t('articles', 'Update article category'),
            'pageBreadcrumbs'       => [
                t('articles', 'Articles')      => createUrl('articles/index'),
                t('articles', 'Categories')   => createUrl('article_categories/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('category'));
    }

    /**
     * Delete exiting article category
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
        $category = ArticleCategory::model()->findByPk((int)$id);

        if (empty($category)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $category->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['article_categories/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $category,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Generate the slug of the article category based on the article category title
     *
     * @return void
     * @throws CException
     */
    public function actionSlug()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['article_categories/index']);
        }

        $category = new ArticleCategory();
        $category->category_id = (int)request()->getPost('category_id');
        $category->slug = (string)request()->getPost('string');

        $article = new Article();
        $article->slug = (string)$category->slug;

        $category->slug = $article->generateSlug();
        $category->slug = $category->generateSlug();

        $this->renderJson(['result' => 'success', 'slug' => $category->slug]);
    }
}
