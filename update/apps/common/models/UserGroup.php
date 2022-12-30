<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UserGroup
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5
 */

/**
 * This is the model class for table "user_group".
 *
 * The followings are the available columns in table 'user_group':
 * @property integer $group_id
 * @property string $name
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property User[] $users
 * @property User[] $usersCount
 * @property UserGroupRouteAccess[] $routeAccess
 */
class UserGroup extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{user_group}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name', 'required'],
            ['name', 'length', 'max' => 255],
            ['name', 'unique'],

            // The following rule is used by search().
            ['name', 'safe', 'on'=>'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'users'       => [self::HAS_MANY, User::class, 'group_id'],
            'usersCount'  => [self::STAT, User::class, 'group_id'],
            'routeAccess' => [self::HAS_MANY, UserGroupRouteAccess::class, 'group_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'group_id'   => t('user_groups', 'Group'),
            'name'       => t('user_groups', 'Name'),
            'usersCount' => t('user_groups', 'Users count'),
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

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    'group_id'   => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return UserGroup the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var UserGroup $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function getAllRoutesAccess(): array
    {
        return UserGroupRouteAccess::findAllByGroupId((int)$this->group_id);
    }

    /**
     * @param string|array $route
     *
     * @return bool
     */
    public function hasRouteAccess($route): bool
    {
        return UserGroupRouteAccess::groupHasRouteAccess((int)$this->group_id, $route);
    }

    /**
     * @return array
     */
    public static function getAllAsOptions(): array
    {
        static $options;
        if ($options !== null) {
            return $options;
        }
        $options = [];
        $models  = self::model()->findAll();
        foreach ($models as $model) {
            $options[$model->group_id] = $model->name;
        }
        return $options;
    }
}
