<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Article
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "article".
 *
 * The followings are the available columns in table 'article':
 * @property integer $article_id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property ArticleCategory[] $categories
 * @property ArticleCategory[] $activeCategories
 */
class Article extends ActiveRecord
{
    /**
     * Flags for various statuses
     */
    const STATUS_PUBLISHED = 'published';
    const STATUS_UNPUBLISHED = 'unpublished';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{article}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['title, slug, content, status', 'required'],
            ['title', 'length', 'max' => 200],
            ['slug', 'length', 'max' => 255],
            ['status', 'in', 'range' => [self::STATUS_PUBLISHED, self::STATUS_UNPUBLISHED]],

            // The following rule is used by search().
            ['title, status', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'categories' => [self::MANY_MANY, ArticleCategory::class, '{{article_to_category}}(article_id, category_id)'],
            'activeCategories' => [self::MANY_MANY, ArticleCategory::class, '{{article_to_category}}(article_id, category_id)', 'condition' => 'activeCategories.status = :st', 'params' => [':st' => ArticleCategory::STATUS_ACTIVE]],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'article_id' => $this->t('Article'),
            'title'      => $this->t('Title'),
            'slug'       => $this->t('Slug'),
            'content'    => $this->t('Content'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     *
     * @throws CException
     */
    public function search()
    {
        $criteria = new CDbCriteria();

        $criteria->compare('title', $this->title, true);
        $criteria->compare('status', $this->status, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder' => [
                    'article_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Article the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var Article $model */
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

    /**
     * @return string
     */
    public function generateSlug(): string
    {
        $string = !empty($this->slug) ? $this->slug : $this->title;
        $slug = URLify::filter((string)$string);
        $article_id = (int)$this->article_id;

        $criteria = new CDbCriteria();
        $criteria->addCondition('article_id != :id AND slug = :slug');
        $criteria->params = [':id' => $article_id, ':slug' => $slug];
        $exists = self::model()->find($criteria);

        $i = 0;
        while (!empty($exists)) {
            ++$i;
            $slug = preg_replace('/^(.*)(\d+)$/six', '$1', $slug);
            $slug = URLify::filter($slug . ' ' . $i);
            $criteria = new CDbCriteria();
            $criteria->addCondition('article_id != :id AND slug = :slug');
            $criteria->params = [':id' => $article_id, ':slug' => $slug];
            $exists = self::model()->find($criteria);
        }

        return $slug;
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _setDefaultEditorForContent(CEvent $event)
    {
        if ($event->params['attribute'] == 'content') {
            $options = [];
            if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
                $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
            }
            $options['id'] = CHtml::activeId($this, 'content');
            $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
        }
    }

    /**
     * @return array
     */
    public function getSelectedCategoriesArray(): array
    {
        $selectedCategories = [];
        if (!$this->getIsNewRecord()) {
            $categories = ArticleToCategory::model()->findAllByAttributes(['article_id' => (int)$this->article_id]);
            foreach ($categories as $category) {
                $selectedCategories[] = (int)$category->category_id;
            }
        }
        return $selectedCategories;
    }

    /**
     * @return array
     */
    public function getAvailableCategoriesArray(): array
    {
        $category = new ArticleCategory();
        return $category->getRelationalCategoriesArray();
    }

    /**
     * @param bool $absolute
     *
     * @return string
     */
    public function getPermalink(bool $absolute = false): string
    {
        return apps()->getAppUrl('frontend', 'article/' . $this->slug, $absolute);
    }

    /**
     * @return array
     */
    public function getStatusesArray(): array
    {
        return [
            ''                          => t('app', 'Choose'),
            self::STATUS_PUBLISHED      => $this->t('Published'),
            self::STATUS_UNPUBLISHED    => $this->t('Unpublished'),
        ];
    }

    /**
     * @return string
     */
    public function getStatusText(): string
    {
        return $this->getStatusesArray()[$this->status] ?? $this->status;
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'title' => $this->t('My article title'),
            'slug'  => $this->t('my-article-title'),
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @param int $length
     *
     * @return string
     */
    public function getExcerpt(int $length = 200): string
    {
        return StringHelper::truncateLength($this->content, $length);
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        $this->fieldDecorator->onHtmlOptionsSetup = [$this, '_setDefaultEditorForContent'];
        parent::afterConstruct();
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->fieldDecorator->onHtmlOptionsSetup = [$this, '_setDefaultEditorForContent'];
        parent::afterFind();
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        $category = new ArticleCategory();
        $category->slug = $this->slug;
        $this->slug = $category->generateSlug();
        $this->slug = $this->generateSlug();

        return parent::beforeValidate();
    }
}
