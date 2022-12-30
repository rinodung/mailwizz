<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ArticleRelatedArticlesWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class ArticleRelatedArticlesWidget extends CWidget
{
    /**
     * @var Article
     */
    public $article;

    /**
     * @var int
     */
    public $limit = 8;

    /**
     * @var int
     */
    public $excerptLength = 100;

    /**
     * @var int
     */
    public $columns = 4;

    /**
     * @var string
     */
    public $columnsCssClass = 'col-lg-3';

    /**
     * @throws CException
     *
     * @return void
     */
    public function run()
    {
        if (empty($this->article->activeCategories)) {
            return;
        }

        $categories = [];
        foreach ($this->article->categories as $category) {
            $categories[] = (int)$category->category_id;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('t.status', Article::STATUS_PUBLISHED);
        $criteria->addCondition('t.article_id != :id');
        $criteria->params[':id'] = $this->article->article_id;
        $criteria->with = [
            'activeCategories' => [
                'together'    => true,
                'joinType'    => 'INNER JOIN',
            ],
        ];
        $criteria->addInCondition('activeCategories.category_id', $categories);
        $criteria->limit = (int)$this->limit;

        /** @var Article[] $articles */
        $articles = Article::model()->findAll($criteria);
        if (empty($articles)) {
            return;
        }

        $columns = [];
        while (!empty($articles)) {
            for ($i = 0; $i < (int)$this->columns; ++$i) {
                if (empty($articles)) {
                    break;
                }
                if (!isset($columns[$i])) {
                    $columns[$i] = [];
                }
                $columns[$i][] = array_shift($articles);
            }
        }

        $this->render('related', compact('columns'));
    }
}
