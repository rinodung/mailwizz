<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ArticleToCategory
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "article_to_category".
 *
 * The followings are the available columns in table 'article_to_category':
 * @property integer $article_id
 * @property integer $category_id
 */
class ArticleToCategory extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{article_to_category}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['article_id', 'exist', 'className' => Article::class],
            ['category_id', 'exist', 'className' => ArticleCategory::class],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'categories' => [self::HAS_MANY, ArticleCategory::class, 'category_id'],
            'articles' => [self::HAS_MANY, Article::class, 'article_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'article_id'  => $this->t('Article'),
            'category_id' => $this->t('Category'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ArticleToCategory the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ArticleToCategory $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function getTranslationCategory(): string
    {
        return 'articles';
    }
}
