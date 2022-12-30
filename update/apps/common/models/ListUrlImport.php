<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListUrlImport
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.9.5
 */

/**
 * This is the model class for table "{{list_url_import}}".
 *
 * The followings are the available columns in table '{{list_url_import}}':
 * @property integer $url_id
 * @property integer $list_id
 * @property string $url
 * @property integer $failures
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Lists $list
 */
class ListUrlImport extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_url_import}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['url, status', 'required'],
            ['url', 'length', 'max'=>255],
            ['url', 'url'],
            ['url', '_validateUrl'],
            ['status', 'in', 'range' => array_keys($this->getStatusesList())],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'list' => [self::BELONGS_TO, Lists::class, 'list_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'url_id'    => t('lists', 'Url'),
            'list_id'   => t('lists', 'List'),
            'url'       => t('lists', 'Url'),
            'failures'  => t('lists', 'Failures'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListUrlImport the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListUrlImport $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateUrl(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        if (!in_array($this->getExtension(), ['.csv'])) {
            $this->addError($attribute, t('lists', 'Please make sure your url points to a .txt or a .csv file!'));
            return;
        }

        if (!$this->getIsUrlValid()) {
            $this->addError($attribute, t('lists', 'The specific url does not seem to be valid, please double check it and try again.'));
            return;
        }
    }

    /**
     * @return bool
     */
    public function getIsUrlValid(): bool
    {
        if (empty($this->url) || !FilterVarHelper::url($this->url)) {
            return false;
        }

        if (!in_array($this->getExtension(), ['.csv'])) {
            return false;
        }

        try {
            $response = (new GuzzleHttp\Client())->head($this->url, [
                'timeout' => 5,
            ]);
        } catch (Exception $e) {
            return false;
        }

        return (int)$response->getStatusCode() === 200;
    }

    /**
     * @return string
     */
    public function getDownloadPath(): string
    {
        $basePath = (string)Yii::getPathOfAlias('common.runtime.list-import-url');
        return $basePath . '/' . (int)$this->url_id . $this->getExtension();
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        if (empty($this->url)) {
            return '';
        }

        $ext = explode('.', $this->url);

        return '.' . end($ext);
    }

    /**
     * @return bool
     */
    public function download(): bool
    {
        if ($this->getIsNewRecord()) {
            return false;
        }

        $storagePath = dirname($this->getDownloadPath());
        if (!file_exists($storagePath) || !is_dir($storagePath)) {
            if (!mkdir($storagePath)) {
                return false;
            }
        }

        if (is_file($this->getDownloadPath())) {
            unlink($this->getDownloadPath());
        }
        touch($this->getDownloadPath());
        chmod($this->getDownloadPath(), 0777);

        if (!($fp = fopen($this->getDownloadPath(), 'w+'))) {
            return false;
        }

        try {
            (new GuzzleHttp\Client())->get($this->url, [
                'timeout' => 5,
                'sink'    => $fp,
            ]);
        } catch (Exception $e) {
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if ((int)$this->failures >= 3) {
            $this->failures = 0;
            $this->status   = self::STATUS_INACTIVE;
        }

        return parent::beforeSave();
    }
}
