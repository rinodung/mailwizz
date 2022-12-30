<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ArticleCategoriesWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class ArticleCategoriesWidget extends CWidget
{
    /**
     * @var Article
     */
    public $article;

    /**
     * @var array
     */
    public $except = [];

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
            if (in_array($category->category_id, (array)$this->except)) {
                continue;
            }
            $url = createUrl('articles/category', ['slug' => $category->slug]);
            $categories[] = CHtml::link($category->name, $url, ['title' => $category->name]);
        }

        if (empty($categories)) {
            return;
        }

        $this->render('categories', compact('categories'));
    }
}
