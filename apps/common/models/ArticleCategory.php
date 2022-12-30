<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ArticleCategory
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "article_category".
 *
 * The followings are the available columns in table 'article_category':
 * @property integer $category_id
 * @property integer $parent_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property ArticleCategory $parent
 * @property ArticleCategory[] $categories
 * @property Article[] $articles
 */
class ArticleCategory extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{article_category}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, slug, status', 'required'],
            ['parent_id', 'numerical', 'integerOnly' => true],
            ['parent_id', 'exist', 'attributeName' => 'category_id'],
            ['name', 'length', 'max' => 200],
            ['slug', 'length', 'max' => 250],
            ['slug', 'unique'],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['description', 'safe'],

            // The following rule is used by search().
            ['name, status', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'parent' => [self::BELONGS_TO, ArticleCategory::class, 'parent_id'],
            'categories' => [self::HAS_MANY, ArticleCategory::class, 'parent_id'],
            'articles' => [self::MANY_MANY, Article::class, '{{article_to_category}}(category_id, article_id)'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'category_id'   => $this->t('Category'),
            'parent_id'     => $this->t('Parent'),
            'name'          => $this->t('Name'),
            'slug'          => $this->t('Slug'),
            'description'   => $this->t('Description'),
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
     * @throws CException
     */
    public function search()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('name', $this->name, true);
        $criteria->compare('status', $this->status, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    'category_id'   => CSort::SORT_ASC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ArticleCategory the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ArticleCategory $model */
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
        $string = !empty($this->slug) ? $this->slug : $this->name;
        $slug = URLify::filter((string)$string);
        $category_id = (int)$this->category_id;

        $criteria = new CDbCriteria();
        $criteria->addCondition('category_id != :id AND slug = :slug');
        $criteria->params = [':id' => $category_id, ':slug' => $slug];
        $exists = self::model()->find($criteria);

        $i = 0;
        while (!empty($exists)) {
            ++$i;
            $slug = preg_replace('/^(.*)(\-\d+)$/six', '$1', $slug);
            $slug = URLify::filter($slug . ' ' . $i);
            $criteria = new CDbCriteria();
            $criteria->addCondition('category_id != :id AND slug = :slug');
            $criteria->params = [':id' => $category_id, ':slug' => $slug];
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
        if ($event->params['attribute'] == 'description') {
            $options = [];
            if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
                $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
            }
            $options['id']      = CHtml::activeId($this, 'description');
            $options['height']  = 100;
            $options['toolbar'] = 'Simple';
            $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
        }
    }

    /**
     * @param int|null $parentId
     * @param string $separator
     *
     * @return array
     */
    public function getRelationalCategoriesArray(?int $parentId = null, $separator = ' -> '): array
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'category_id, parent_id, name';
        if (empty($parentId)) {
            $criteria->condition = 'parent_id IS NULL';
        } else {
            $criteria->compare('parent_id', (int)$parentId);
        }
        $criteria->addCondition('slug IS NOT NULL');
        $criteria->order = 'slug ASC';

        $categories = [];
        $results = self::model()->findAll($criteria);
        foreach ($results as $result) {
            // dont allow selecting a child as a parent
            if (!empty($this->category_id) && $this->category_id == $result->category_id) {
                continue;
            }
            $categories[$result->category_id] = $result->name;
            $children = $this->getRelationalCategoriesArray((int)$result->category_id);
            foreach ($children as $childId => $childName) {
                $categories[$childId] = $result->name . $separator . $childName;
            }
        }
        return $categories;
    }

    /**
     * @param string $separator
     *
     * @return string
     */
    public function getParentNameTrail(string $separator = ' -> '): string
    {
        $nameTrail = [$this->name];

        if (!empty($this->parent_id)) {
            $criteria = new CDbCriteria();
            $criteria->select = 'category_id, parent_id, name';
            $criteria->compare('category_id', (int)$this->parent_id);
            $parent = self::model()->find($criteria);

            if (!empty($parent)) {
                $nameTrail[] = $parent->getParentNameTrail();
            }
        }

        $nameTrail = array_reverse($nameTrail);
        return implode($separator, $nameTrail);
    }

    /**
     * @param bool $absolute
     *
     * @return string
     */
    public function getPermalink(bool $absolute = false): string
    {
        return apps()->getAppUrl('frontend', 'articles/' . $this->slug, $absolute);
    }

    /**
     * @return array
     */
    public function getStatusesArray(): array
    {
        return [
            ''                      => t('app', 'Choose'),
            self::STATUS_ACTIVE     => t('app', 'Active'),
            self::STATUS_INACTIVE   => t('app', 'Inactive'),
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
            'name'  => $this->t('My category name'),
            'slug'  => $this->t('my-category-name'),
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
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
        $article = new Article();
        $article->slug = $this->slug;
        $this->slug    = $article->generateSlug();
        $this->slug    = $this->generateSlug();

        return parent::beforeValidate();
    }
}
