<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * IpLocation
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.2
 */

/**
 * This is the model class for table "ip_location".
 *
 * The followings are the available columns in table 'ip_location':
 * @property string $location_id
 * @property string $ip_address
 * @property string $country_code
 * @property string $country_name
 * @property string $zone_name
 * @property string $city_name
 * @property string $latitude
 * @property string $longitude
 * @property string $timezone
 * @property integer $timezone_offset
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property CampaignTrackOpen[] $trackOpens
 * @property CampaignTrackUnsubscribe[] $trackUnsubscribes
 * @property CampaignTrackUrl[] $trackUrls
 */
class IpLocation extends ActiveRecord
{
    /**
     * @var int
     */
    public $counter = 0;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{ip_location}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['ip_address, country_code, country_name, latitude, longitude', 'required'],
            ['ip_address', 'length', 'max'=>15],
            ['country_code', 'length', 'max'=>3],
            ['country_name, zone_name, city_name', 'length', 'max'=>150],
            ['latitude', 'length', 'max'=>10],
            ['longitude', 'length', 'max'=>11],
            ['timezone', 'length', 'max' => 100],
            ['timezone_offset', 'numerical'],
        ];
    }

    /**
     * @return array
     */
    public function relations()
    {
        return [
            'trackOpens'        => [self::HAS_MANY, CampaignTrackOpen::class, 'location_id'],
            'trackUnsubscribes' => [self::HAS_MANY, CampaignTrackUnsubscribe::class, 'location_id'],
            'trackUrls'         => [self::HAS_MANY, CampaignTrackUrl::class, 'location_id'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'location_id'    => t('ip_location', 'Location'),
            'ip_address'     => t('ip_location', 'Ip address'),
            'country_code'   => t('ip_location', 'Country code'),
            'country_name'   => t('ip_location', 'Country name'),
            'zone_name'      => t('ip_location', 'Zone name'),
            'city_name'      => t('ip_location', 'City name'),
            'latitude'       => t('ip_location', 'Latitude'),
            'longitude'      => t('ip_location', 'Longitude'),
            'timezone'       => t('ip_location', 'Timezone'),
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
     */
    public function search()
    {
        $criteria = new CDbCriteria();

        return new CActiveDataProvider($this, [
            'criteria' => $criteria,
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return IpLocation the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var IpLocation $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $separator
     * @param array $attributes
     * @return string
     */
    public function getLocation(string $separator = ', ', array $attributes = []): string
    {
        if (empty($attributes)) {
            $attributes = ['country_name', 'zone_name', 'city_name'];
        }

        $location = [];
        foreach ($attributes as $attribute) {
            if (!empty($this->$attribute)) {
                $location[] = $this->$attribute;
            }
        }

        return implode($separator, $location);
    }

    /**
     * @param string $ipAddress
     *
     * @return IpLocation|null
     * @throws MaxMind\Db\Reader\InvalidDatabaseException
     */
    public static function findByIp(string $ipAddress): ?IpLocation
    {
        if (empty($ipAddress)) {
            return null;
        }

        static $cache = [];
        if (isset($cache[$ipAddress]) || array_key_exists($ipAddress, $cache)) {
            return $cache[$ipAddress];
        }

        $location = self::model()->findByAttributes([
            'ip_address' => $ipAddress,
        ]);

        if (!empty($location)) {
            return $cache[$ipAddress] = $location;
        }

        $attributes = self::createAttributesFromResponse($ipAddress);
        if (empty($attributes)) {
            return $cache[$ipAddress] = null;
        }

        $location = new self();
        $location->attributes = $attributes;
        if (!$location->save()) {
            return $cache[$ipAddress] = null;
        }

        return $cache[$ipAddress] = $location;
    }

    /**
     * @param string $timezone
     * @param string $time
     *
     * @return int
     */
    public static function getTimezoneOffset(string $timezone = 'UTC', string $time = 'now'): int
    {
        try {
            $originDtz = new DateTimeZone($timezone);
            $originDt  = new DateTime($time, $originDtz);

            $offset = $originDtz->getOffset($originDt);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);

            $offset = 0;
        }

        return $offset;
    }

    /**
     * @param string $remote
     * @param string $origin
     *
     * @return int
     */
    public static function calculateTimezonesOffset(string $remote, string $origin = 'UTC'): int
    {
        return (int)self::getTimezoneOffset($remote) - (int)self::getTimezoneOffset($origin);
    }

    /**
     * @param string $ipAddress
     *
     * @return array
     * @throws MaxMind\Db\Reader\InvalidDatabaseException
     */
    public static function createAttributesFromResponse(string $ipAddress): array
    {
        $dbFile = (string)app_param('ip.location.maxmind.db.path', '');

        static $hasDbFile = null;
        if ($hasDbFile === null) {
            $hasDbFile = is_file($dbFile);
        }

        if (!$hasDbFile) {
            return [];
        }

        $reader = new MaxMind\Db\Reader($dbFile);

        try {
            /** @var array $response */
            $response = $reader->get($ipAddress);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            $response = [];
        }

        $reader->close();

        if (empty($response) || !is_array($response)) {
            return [];
        }

        $attributes = [
            'ip_address'    => $ipAddress,
            'country_code'  => !isset($response['country']['iso_code']) ? null : strtoupper((string)$response['country']['iso_code']),
            'country_name'  => !isset($response['country']['names']['en']) ? null : ucwords(strtolower((string)$response['country']['names']['en'])),
            'zone_name'     => !isset($response['subdivisions'][0]['names']['en']) ? null : ucwords(strtolower((string)$response['subdivisions'][0]['names']['en'])),
            'city_name'     => !isset($response['city']['names']['en']) ? null : ucwords(strtolower((string)$response['city']['names']['en'])),
            'latitude'      => !isset($response['location']['latitude']) ? 0 : (float)$response['location']['latitude'],
            'longitude'     => !isset($response['location']['longitude']) ? 0 : (float)$response['location']['longitude'],
            'timezone'      => !isset($response['location']['time_zone']) ? null : $response['location']['time_zone'],
        ];

        $attributes['timezone_offset'] = empty($attributes['timezone']) ? null : self::calculateTimezonesOffset($attributes['timezone']);

        return $attributes;
    }

    /**
     * @return bool
     * @throws MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function updateTimezoneInfo(): bool
    {
        if ($this->timezone !== null && $this->timezone_offset !== null) {
            return true;
        }

        if (!($attributes = self::createAttributesFromResponse($this->ip_address))) {
            return false;
        }

        if ($attributes['timezone'] === null || $attributes['timezone_offset'] === null) {
            return false;
        }

        $this->attributes = $attributes;

        return (bool)$this->save();
    }
}
