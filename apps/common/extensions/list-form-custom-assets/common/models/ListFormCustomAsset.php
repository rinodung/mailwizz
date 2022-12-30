<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListFormCustomAsset
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

/**
 * This is the model class for table "{{list_form_custom_asset}}".
 *
 * The followings are the available columns in table '{{list_form_custom_asset}}':
 * @property integer $asset_id
 * @property integer $list_id
 * @property integer $type_id
 * @property string $asset_url
 * @property string $asset_type
 * @property string $date_added
 * @property string $last_updated
 *
 * The followings are the available model relations:
 * @property ListPage $list
 * @property ListPage $type
 */
class ListFormCustomAsset extends ActiveRecord
{
    /**
     * Flags
     */
    const ASSET_TYPE_CSS = 'css';
    const ASSET_TYPE_JS  = 'javascript';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_form_custom_asset}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['asset_url, asset_type', 'required'],
            ['asset_url', 'url'],
            ['asset_type', 'in', 'range' => array_keys($this->getAssetTypes())],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'list' => [self::BELONGS_TO, 'ListPage', 'list_id'],
            'type' => [self::BELONGS_TO, 'ListPage', 'type_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'asset_id'   => t('lists', 'Asset'),
            'list_id'    => t('lists', 'List'),
            'type_id'    => t('lists', 'Page type'),
            'asset_url'  => t('lists', 'Asset url'),
            'asset_type' => t('lists', 'Asset type'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'asset_id'   => '',
            'list_id'    => '',
            'type_id'    => '',
            'asset_url'  => t('lists', 'The url from where we should load the asset'),
            'asset_type' => t('lists', 'The type of the asset, css or javascript'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'asset_id'   => '',
            'list_id'    => '',
            'type_id'    => '',
            'asset_url'  => t('lists', 'i.e: http://www.some-other-website.com/assets/css/my-list-file.css'),
            'asset_type' => '',
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListFormCustomAsset the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListFormCustomAsset $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getAssetTypes(): array
    {
        return [
            self::ASSET_TYPE_CSS => ucfirst(t('lists', self::ASSET_TYPE_CSS)),
            self::ASSET_TYPE_JS  => ucfirst(t('lists', self::ASSET_TYPE_JS)),
        ];
    }

    /**
     * @return void
     */
    protected function afterValidate()
    {
        if ($this->hasErrors()) {
            parent::afterValidate();
            return;
        }

        $ext = @pathinfo($this->asset_url, PATHINFO_EXTENSION);
        if (($this->asset_type == self::ASSET_TYPE_CSS && $ext != 'css') || ($this->asset_type == self::ASSET_TYPE_JS && $ext != 'js')) {
            $this->addError('asset_type', t('lists', 'The url {url} must point to a valid {type} file.', [
                '{url}'  => $this->asset_url,
                '{type}' => $this->asset_type,
            ]));
        }

        parent::afterValidate();
    }
}
