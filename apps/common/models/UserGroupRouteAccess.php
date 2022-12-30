<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UserGroupRouteAccess
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5
 */

/**
 * This is the model class for table "user_group_route_access".
 *
 * The followings are the available columns in table 'user_group_route_access':
 * @property integer $route_id
 * @property integer $group_id
 * @property string $route
 * @property string $access
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property UserGroup $group
 */
class UserGroupRouteAccess extends ActiveRecord
{
    /**
     * Permission flags
     */
    const ALLOW = 'allow';
    const DENY = 'deny';

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $controller;

    /**
     * @var string
     */
    public $action;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{user_group_route_access}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['group_id, route', 'required'],
            ['group_id', 'numerical', 'integerOnly'=>true],
            ['group_id', 'exist', 'className' => UserGroup::class],
            ['route', 'length', 'max'=>255],
            ['access', 'length', 'max'=>5],
            ['access', 'in', 'range' => array_keys($this->getAccessOptions())],

            // The following rule is used by search().
            ['group_id', 'safe', 'on'=>'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'group' => [self::BELONGS_TO, UserGroup::class, 'group_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'route_id'       => t('user_groups', 'Route'),
            'group_id'       => t('user_groups', 'Group'),
            'route'          => t('user_groups', 'Route'),
            'access'         => t('user_groups', 'Access'),
            'name'           => t('user_groups', 'Name'),
            'description'    => t('user_groups', 'Description'),
            'controller'     => t('user_groups', 'Controller'),
            'action'         => t('user_groups', 'Action'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'route_id'       => t('user_groups', 'Route'),
            'group_id'       => t('user_groups', 'Group'),
            'route'          => t('user_groups', 'Route'),
            'access'         => t('user_groups', 'Access'),
            'name'           => t('user_groups', 'Name'),
            'description'    => t('user_groups', 'Description'),
            'controller'     => t('user_groups', 'Controller'),
            'action'         => t('user_groups', 'Action'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
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
        $criteria->compare('group_id', $this->group_id);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    'controller'   => CSort::SORT_ASC,
                    'action'       => CSort::SORT_ASC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return UserGroupRouteAccess the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var UserGroupRouteAccess $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getAccessOptions(): array
    {
        return [
            self::ALLOW => t('user_groups', ucfirst(self::ALLOW)),
            self::DENY  => t('user_groups', ucfirst(self::DENY)),
        ];
    }

    /**
     * @return bool
     */
    public function getIsAllowed(): bool
    {
        return (string)$this->access === self::ALLOW;
    }

    /**
     * @return bool
     */
    public function getIsDenied(): bool
    {
        return !$this->getIsAllowed();
    }

    /**
     * @param int $groupId
     *
     * @return array
     * @throws ReflectionException
     */
    public static function findAllByGroupId(int $groupId): array
    {
        $items = self::getRoutesFromFiles();
        $routes = [];
        foreach ($items as $index => $item) {
            $routes[$index] = ['controller' => $item['controller'], 'routes' => []];
            foreach ($item['routes'] as $action) {
                $model = self::model()->findByAttributes([
                    'group_id' => $groupId,
                    'route'    => $action['route'],
                ]);
                if (empty($model)) {
                    $model = new self();
                    $model->group_id = $groupId;
                    $model->route    = $action['route'];
                    $model->access   = self::ALLOW;
                }
                $model->name        = t('user_groups', $action['name']);
                $model->description = t('user_groups', $action['description']);
                $routes[$index]['routes'][] = $model;
            }
        }
        return $routes;
    }

    /**
     * @param int $groupId
     * @param mixed $route
     *
     * @return bool
     */
    public static function groupHasRouteAccess(int $groupId, $route): bool
    {
        if (empty($groupId) || empty($route)) {
            return true;
        }

        if (is_array($route)) {
            $hasAccess = true;
            foreach ($route as $r) {
                if (!self::groupHasRouteAccess($groupId, $r)) {
                    $hasAccess = false;
                    break;
                }
            }
            return $hasAccess;
        }

        static $hashes = [];
        $hashKey = sha1(serialize(func_get_args()));
        if (isset($hashes[$hashKey]) || array_key_exists($hashKey, $hashes)) {
            return (bool)$hashes[$hashKey];
        }

        static $groupRoutes = [];
        if (!isset($groupRoutes[$groupId]) || !array_key_exists($groupId, $groupRoutes)) {
            $criteria = new CDbCriteria();
            $criteria->select = 'route, access';
            $criteria->compare('group_id', (int)$groupId);
            $models = self::model()->findAll($criteria);
            $groupRoutes[$groupId] = [];
            foreach ($models as $model) {
                $groupRoutes[$groupId][$model->route] = $model->getIsAllowed();
            }
            unset($models);
        }

        return $hashes[$hashKey] = isset($groupRoutes[$groupId][$route]) && $groupRoutes[$groupId][$route] === false ? false : true;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    protected static function getRoutesFromFiles(): array
    {
        /** @var CWebApplication $app */
        $app = app();

        $files = [];
        foreach ($app->controllerMap as $info) {
            $files[] = (string)Yii::getPathOfAlias($info['class']) . '.php';
        }
        $files   = array_merge($files, FileSystemHelper::readDirectoryContents($app->getControllerPath(), true));
        $exclude = [
            $app->getControllerPath() . '/GuestController.php',
            $app->getControllerPath() . '/DashboardController.php',
            $app->getControllerPath() . '/AccountController.php',
        ];
        $files = array_diff($files, $exclude);
        sort($files);

        $rootPath = (string)Yii::getPathOfAlias('root.apps');
        $info     = [];
        foreach ($files as $file) {
            if (substr($file, -4) != '.php') {
                continue;
            }
            $fileNameNoExt = basename($file, '.php');
            $controllerId  = strtolower(substr($fileNameNoExt, 0, -10));

            if (!class_exists($fileNameNoExt, false)) {
                require_once $file;
            }

            $refl    = new ReflectionClass(new $fileNameNoExt($controllerId));
            $methods = $refl->getMethods(ReflectionMethod::IS_PUBLIC);
            $routes  = [];

            foreach ($methods as $method) {
                if (strpos($method->name, 'action') !== 0 || strpos($method->name, 'actions') === 0) {
                    continue;
                }
                $actionId = strtolower(substr($method->name, 6));
                $routes[] = array_merge(['route' => $controllerId . '/' . $actionId], self::extractObjectInfo($method));
            }
            $data = [
                'controller' => self::extractObjectInfo($refl),
                'routes'     => $routes,
            ];
            $info[] = $data;
        }
        return $info;
    }

    /**
     * @param mixed $reflObj
     *
     * @return array
     */
    protected static function extractObjectInfo($reflObj): array
    {
        $info = ['name' => '', 'description' => ''];
        if (!($reflObj instanceof ReflectionClass) && !($reflObj instanceof ReflectionMethod)) {
            return $info;
        }

        $comment = (string)$reflObj->getDocComment();
        if (preg_match_all('#@(.*?)\n#s', $comment, $matches)) {
            $annotations = $matches[1];
            foreach ($annotations as $annotation) {
                $annotation = trim((string)$annotation);
                if (strpos($annotation, 'routeName') === 0) {
                    $info['name'] = substr($annotation, strlen('routeName'));
                }
                if (strpos($annotation, 'routeDescription') === 0) {
                    $info['description'] = substr($annotation, strlen('routeDescription'));
                }
            }
        }
        if (empty($info['name'])) {
            if ($reflObj instanceof ReflectionMethod) {
                $info['name'] = ucfirst(str_replace('_', ' ', substr(strtolower((string)$reflObj->name), 6)));
            } elseif ($reflObj instanceof ReflectionClass) {
                $info['name'] = ucfirst(str_replace('_', ' ', substr(strtolower((string)$reflObj->name), 0, -10)));
            }
        }
        $info['name'] = (string)str_replace('Ext ', 'Extension ', (string)$info['name']);
        $info['name'] = t('user_groups', (string)$info['name']);

        if (empty($info['description'])) {
            $comment = (string)preg_replace('#@(.*?)\n#s', '', $comment);
            $comment = (string)str_replace(['*', '/', $reflObj->name], '', $comment);
            $comment = trim((string)$comment);
            $comment = (string)str_replace(["\n", "\t"], '', $comment);
            $comment = (string)preg_replace('/\s{2,}/', ' ', $comment);
            $info['description'] = trim((string)$comment);
        }
        $info['description'] = ucfirst($info['description']);
        $info['description'] = t('user_groups', $info['description']);
        return $info;
    }
}
