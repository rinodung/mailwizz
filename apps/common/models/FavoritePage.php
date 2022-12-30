<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * FavoritePage
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.11
 */

/**
 * This is the model class for table "favorite_page".
 *
 * The followings are the available columns in table 'favorite_page':
 * @property integer|null $page_id
 * @property string $page_uid
 * @property integer|null $customer_id
 * @property integer|null $user_id
 * @property string $label
 * @property string $route
 * @property mixed $route_params
 * @property string $route_hash
 * @property integer $clicks_count
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property User $user
 * @property Customer $customer
 *
 */
class FavoritePage extends ActiveRecord
{
    /**
     * Flag for bulk reset click counters
     */
    const BULK_ACTION_RESET_CLICK_COUNT = 'reset-click-count';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{favorite_page}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules =  [
            ['label, route', 'required'],
            ['label, route', 'length', 'max' => 255],
            ['route', '_validateRoute'],

            ['customer_id, user_id, label, route', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'user'     => [self::BELONGS_TO, User::class, 'user_id'],
            'customer' => [self::BELONGS_TO, Customer::class, 'customer_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels =  [
            'page_id'      => t('favorite_pages', 'Page'),
            'page_uid'     => t('favorite_pages', 'Page'),
            'customer_id'  => t('favorite_pages', 'Customer'),
            'user_id'      => t('favorite_pages', 'User'),
            'label'        => t('favorite_pages', 'Label'),
            'route'        => t('favorite_pages', 'Route'),
            'route_params' => t('favorite_pages', 'Route params'),
            'route_hash'   => t('favorite_pages', 'Route hash'),
            'clicks_count' => t('favorite_pages', 'Clicks count'),
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
        $criteria->with = [];

        $criteria->compare('t.label', $this->label, true);
        $criteria->compare('t.route', $this->route, true);

        if (!empty($this->customer_id)) {
            if (is_numeric($this->customer_id)) {
                $criteria->compare('t.customer_id', $this->customer_id);
            } else {
                $criteria->with['customer'] = [
                    'condition' => 'customer.email LIKE :name OR customer.first_name LIKE :name OR customer.last_name LIKE :name',
                    'params'    => [':name' => '%' . $this->customer_id . '%'],
                ];
            }
        } elseif ($this->customer_id === null) {
            $criteria->addCondition('t.customer_id IS NULL');
        }

        if (!empty($this->user_id)) {
            if (is_numeric($this->user_id)) {
                $criteria->compare('t.user_id', $this->user_id);
            } else {
                $criteria->with['user'] = [
                    'condition' => 'user.email LIKE :name OR user.first_name LIKE :name OR user.last_name LIKE :name',
                    'params'    => [':name' => '%' . $this->user_id . '%'],
                ];
            }
        } elseif ($this->user_id === null) {
            $criteria->addCondition('t.user_id IS NULL');
        }

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder' => [
                    'page_id'   => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return FavoritePage the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var FavoritePage $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $page_uid
     *
     * @return FavoritePage|null
     */
    public function findByUid(string $page_uid): ?self
    {
        return self::model()->findByAttributes([
            'page_uid' => $page_uid,
        ]);
    }

    /**
     * @return string
     */
    public function generateUid(): string
    {
        $unique = StringHelper::uniqid();
        $exists = $this->findByUid($unique);

        if (!empty($exists)) {
            return $this->generateUid();
        }

        return $unique;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return (string)$this->page_uid;
    }

    /**
     * @return array
     */
    public function getBulkActionsList(): array
    {
        return CMap::mergeArray(parent::getBulkActionsList(), [
            self::BULK_ACTION_RESET_CLICK_COUNT => t('favorite_pages', 'Reset the clicks counter'),
        ]);
    }

    /**
     * @param int $limit
     * @return array
     */
    public static function getTopPages(int $limit = 5): array
    {
        if (!apps()->isAppName('customer') && !apps()->isAppName('backend')) {
            return [];
        }

        $criteria = new CDbCriteria();
        if (apps()->isAppName('customer')) {
            $criteria->compare('customer_id', (int)customer()->getId());
        } elseif (apps()->isAppName('backend')) {
            $criteria->compare('user_id', (int)user()->getId());
        }

        $criteria->order = 'clicks_count DESC, date_added DESC';
        $criteria->limit = $limit;

        return static::model()->findAll($criteria);
    }

    /**
     * @param int $limit
     * @return array
     */
    public static function getTopPagesAsMenuItems(int $limit = 10): array
    {
        $links = static::getTopPages($limit);

        $links = array_map(function (FavoritePage $item) {
            return [
                'url'    => createUrl('favorite_pages/redirect_to_page', ['page_uid' => $item->page_uid]),
                'label'  => StringHelper::truncateLength($item->label, 24),
                'active' => '',
            ];
        }, $links);

        return array_merge($links, [
            ['url' => ['favorite_pages/index'], 'label' => t('favorite_pages', 'Favorite pages'), 'active' => strpos(controller()->getRoute(), 'favorite_pages/index') === 0],
        ]);
    }

    /**
     * @param int $limit
     * @return string
     */
    public static function buildTopPagesMenu(int $limit = 10): string
    {
        $links = static::getTopPagesAsMenuItems($limit);

        $menuItems = array_map(function (array $item) {
            $link = CHtml::link(IconHelper::make('fa fa-circle text-primary') . $item['label'], $item['url'], [
                'class'               => $item['linkOptions']['class'] ?? '',
                'data-original-title' => '',
                'title'               => '',
            ]);

            return CHtml::tag('li', [
                'class' => $item['active'] ? 'active' : '',
            ], $link);
        }, $links);

        return implode('', $menuItems);
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateRoute(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        if (strpos($this->$attribute, '/') === false) {
            $this->addError($attribute, t('favorite_pages', 'The route does not seem to be valid!'));
            return;
        }

        $criteria = new CDbCriteria();

        if (apps()->isAppName('customer')) {
            $criteria->compare('customer_id', (int)customer()->getId());
        } elseif (apps()->isAppName('backend')) {
            $criteria->compare('user_id', (int)user()->getId());
        }
        $criteria->compare('route_hash', (string)$this->route_hash);
        $criteria->addCondition('page_id != :pid');
        $criteria->params[':pid'] = (int)$this->page_id;

        $exists = self::model()->find($criteria);

        if (!empty($exists)) {
            $this->addError($attribute, t('favorite_pages', 'The user/route combo is already taken!'));
        }
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        if (!empty($this->route_params)) {
            $this->route_params = serialize($this->route_params);
        } else {
            $this->route_params = null;
        }

        $this->route_hash = sha1($this->route . (string)$this->route_params);

        return parent::beforeValidate();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (empty($this->page_uid)) {
            $this->page_uid = $this->generateUid();
        }

        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        parent::afterFind();

        if (!empty($this->route_params)) {
            $this->route_params = unserialize((string)$this->route_params);
        }
    }
}
