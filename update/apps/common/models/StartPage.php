<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * StartPage
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.9.2
 */

/**
 * This is the model class for table "{{start_page}}".
 *
 * The followings are the available columns in table '{{start_page}}':
 * @property integer $page_id
 * @property string $application
 * @property string $route
 * @property string $icon
 * @property string $icon_color
 * @property string $heading
 * @property string $content
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 */
class StartPage extends ActiveRecord
{
    /**
     * @var string
     */
    public $search_icon = '';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{start_page}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['application, route', 'required'],
            ['application, route, icon, heading', 'length', 'max' => 255],
            ['application', 'in', 'range' => array_keys($this->getApplications())],
            ['application', '_validateApplication'],
            ['route', '_validateRoute'],
            ['icon', 'in', 'range' => $this->getIcons()],
            ['icon_color', 'length', 'is' => 6],
            ['icon_color', '_validateIconColor'],

            ['application, route, icon, heading, content', 'safe', 'on' => 'search'],
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
            'page_id'     => t('start_pages', 'Page'),
            'application' => t('start_pages', 'Application'),
            'route'       => t('start_pages', 'Route'),
            'icon'        => t('start_pages', 'Icon'),
            'heading'     => t('start_pages', 'Heading'),
            'content'     => t('start_pages', 'Content'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'application' => t('start_pages', 'The application where this page applies'),
            'route'       => t('start_pages', 'The url route (controller/action) where this page applies, i.e: campaigns/index'),
            'heading'     => t('start_pages', 'The heading of the page'),

            'search_icon' => t('start_pages', 'Start by typing a few characters from the icon name'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'search_icon' => t('start_pages', 'Search icon, i.e: envelope'),
            'route'       => 'campaigns/index',
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

        $criteria->compare('application', $this->application);
        $criteria->compare('route', $this->route, true);
        $criteria->compare('icon', $this->icon, true);
        $criteria->compare('heading', $this->heading, true);
        $criteria->compare('content', $this->content, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    'page_id'     => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return StartPage the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var StartPage $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getApplications(): array
    {
        $webApps = apps()->getWebApps();
        $apps    = [];

        foreach ($webApps as $webApp) {
            $apps[$webApp] = t('start_pages', ucfirst($webApp));
        }

        return $apps;
    }

    /**
     * @return array
     */
    public function getIcons(): array
    {
        return $this->getGlyphiconIcons() + $this->getFontAwesomeIcons() + $this->getIonIcons();
    }

    /**
     * @return array
     */
    public function getFontAwesomeIcons(): array
    {
        $url     = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css';
        $pattern = '#\.(fa-([a-z0-9\-\_]+)):before#i';

        return $this->getIconsFromUrlByPattern($url, $pattern);
    }

    /**
     * @return array
     */
    public function getIonIcons(): array
    {
        $url     = 'https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css';
        $pattern = '#\.(ion-([a-z0-9\-\_]+)):before#i';

        return $this->getIconsFromUrlByPattern($url, $pattern);
    }

    /**
     * @return array
     */
    public function getGlyphiconIcons(): array
    {
        $url     = 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.1.1/css/bootstrap.min.css';
        $pattern = '#\.(glyphicon-([a-z0-9\-\_]+)):before#i';

        return $this->getIconsFromUrlByPattern($url, $pattern);
    }

    /**
     * @param string $url
     * @param string $pattern
     *
     * @return array
     */
    public function getIconsFromUrlByPattern(string $url, string $pattern): array
    {
        $cacheKey = sha1(__METHOD__ . $url . $pattern);
        if (($icons = cache()->get($cacheKey)) !== false) {
            return $icons;
        }
        $icons = [];

        try {
            $content = (string)(new GuzzleHttp\Client())->get($url)->getBody();
        } catch (Exception $e) {
            $content = '';
        }

        if (empty($content)) {
            return $icons;
        }

        if (!preg_match_all($pattern, $content, $matches)) {
            return $icons;
        }

        $icons = array_filter(array_unique((array)$matches[1]));
        $icons = (array)ioFilter()->stripClean($icons);
        $icons = array_map(['CHtml', 'encode'], $icons);

        cache()->set($cacheKey, $icons);

        return $icons;
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
            $this->addError($attribute, t('start_pages', 'The route does not seem to be valid!'));
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('application', (string)$this->application);
        $criteria->compare('route', (string)$this->route);
        $criteria->addCondition('page_id != :pid');
        $criteria->params[':pid'] = (int)$this->page_id;

        $exists = self::model()->find($criteria);

        if (!empty($exists)) {
            $this->addError($attribute, t('start_pages', 'The application/route combo is already taken!'));
            return;
        }
    }

    /**
     * @return array
     */
    public function getAvailableTags(): array
    {
        return [
            '[CUSTOMER_BASE_URL]' => t('start_pages', 'Customer base url, useful for links generation.'),
            '[BACKEND_BASE_URL]'  => t('start_pages', 'Backend base url, useful for links generation.'),
            '[FRONTEND_BASE_URL]' => t('start_pages', 'Frontend base url, useful for links generation.'),
            '[API_BASE_URL]'      => t('start_pages', 'Frontend base url, useful for links generation.'),
        ];
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateApplication(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('application', (string)$this->application);
        $criteria->compare('route', (string)$this->route);
        $criteria->addCondition('page_id != :pid');
        $criteria->params[':pid'] = (int)$this->page_id;

        $exists = self::model()->find($criteria);

        if (!empty($exists)) {
            $this->addError($attribute, t('start_pages', 'The application/route combo is already taken!'));
            return;
        }
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateIconColor(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        if (empty($this->$attribute)) {
            return;
        }

        if (!CommonHelper::functionExists('ctype_xdigit')) {
            return;
        }

        if (!ctype_xdigit((string)$this->$attribute)) {
            $this->addError($attribute, t('start_pages', 'Given color code does not seem to be a valid hex code!'));
        }
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->content = StringHelper::decodeSurroundingTags($this->content);
        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->content = StringHelper::decodeSurroundingTags($this->content);
        parent::afterFind();
    }
}
