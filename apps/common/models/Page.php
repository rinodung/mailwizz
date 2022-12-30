<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Page
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.5
 */

/**
 * This is the model class for table "page".
 *
 * The followings are the available columns in table 'page':
 * @property integer $page_id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 */
class Page extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{page}}';
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
            ['slug', 'unique'],
            ['status', 'in', 'range' => array_keys($this->getStatusesList())],

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
        $relations = [];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'page_id'    => t('pages', 'Page'),
            'title'      => t('pages', 'Title'),
            'slug'       => t('pages', 'Slug'),
            'content'    => t('pages', 'Content'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
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
            'title' => t('pages', 'My page title'),
            'slug'  => t('pages', 'my-page-title'),
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
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

        $criteria->compare('title', $this->title, true);
        $criteria->compare('status', $this->status);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder' => [
                    'page_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Page the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var Page $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function generateSlug(): string
    {
        $string  = !empty($this->slug) ? $this->slug : $this->title;
        $slug    = URLify::filter($string);
        $page_id = (int)$this->page_id;

        $criteria = new CDbCriteria();
        $criteria->addCondition('page_id != :id AND slug = :slug');
        $criteria->params = [':id' => $page_id, ':slug' => $slug];
        $exists = self::model()->find($criteria);

        $i = 0;
        while (!empty($exists)) {
            ++$i;
            $slug = preg_replace('/^(.*)(\d+)$/six', '$1', $slug);
            $slug = URLify::filter($slug . ' ' . $i);
            $criteria = new CDbCriteria();
            $criteria->addCondition('page_id != :id AND slug = :slug');
            $criteria->params = [':id' => $page_id, ':slug' => $slug];
            $exists = self::model()->find($criteria);
        }

        return $slug;
    }

    /**
     * @param bool $absolute
     * @return string
     */
    public function getPermalink(bool $absolute = false): string
    {
        return apps()->getAppUrl('frontend', 'page/' . $this->slug, $absolute);
    }

    /**
     * @param int $length
     * @return string
     */
    public function getExcerpt(int $length = 200): string
    {
        return StringHelper::truncateLength((string)$this->content, $length);
    }

    /**
     * @return bool
     */
    public function getIsActive(): bool
    {
        return $this->getStatusIs(self::STATUS_ACTIVE);
    }

    /**
     * @param string $slug
     *
     * @return Page|null
     */
    public static function findBySlug(string $slug): ?self
    {
        return self::model()->findByAttributes(['slug' => $slug, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        $this->slug = $this->generateSlug();

        return parent::beforeValidate();
    }
}
