<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignActivityMapExtBackendCommon
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class CampaignActivityMapExtCommon extends ExtensionModel
{
    /**
     * @var int
     */
    public $show_opens_map = 0;

    /**
     * @var int
     */
    public $show_clicks_map = 0;

    /**
     * @var int
     */
    public $show_unsubscribes_map = 0;

    /**
     * @var int
     */
    public $translate_map = 0;

    /**
     * @var int
     */
    public $opens_at_once = 50;

    /**
     * @var int
     */
    public $clicks_at_once = 50;

    /**
     * @var int
     */
    public $unsubscribes_at_once = 50;

    /**
     * @var string
     */
    public $google_maps_api_key = '';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['show_opens_map, show_clicks_map, opens_at_once, clicks_at_once, show_unsubscribes_map, unsubscribes_at_once', 'required'],
            ['show_opens_map, show_clicks_map, show_unsubscribes_map', 'in', 'range' => array_keys($this->getOptionsDropDown())],
            ['opens_at_once, clicks_at_once, unsubscribes_at_once', 'numerical', 'integerOnly' => true, 'min' => 10, 'max' => 500],
            ['google_maps_api_key', 'length', 'max' => 1000],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'show_opens_map'        => $this->t('Show opens map'),
            'show_clicks_map'       => $this->t('Show clicks map'),
            'opens_at_once'         => $this->t('Opens at once'),
            'clicks_at_once'        => $this->t('Clicks at once'),
            'show_unsubscribes_map' => $this->t('Show unsubscribes map'),
            'unsubscribes_at_once'  => $this->t('Unsubscribes at once'),
            'google_maps_api_key'   => $this->t('Google maps API key'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'show_opens_map'        => $this->t('Whether to show a map with location opens in campaign overview'),
            'show_clicks_map'       => $this->t('Whether to show a map with location clicks in campaign overview'),
            'opens_at_once'         => $this->t('How many open records to load at once per ajax call? More records means more memory usage'),
            'clicks_at_once'        => $this->t('How many click records to load at once per ajax call? More records means more memory usage'),
            'show_unsubscribes_map' => $this->t('Whether to show a map with location from where subscribers unsubscribed in campaign overview'),
            'unsubscribes_at_once'  => $this->t('How many unsubscribe records to load at once per ajax call? More records means more memory usage'),
            'google_maps_api_key'   => $this->t('Your google maps API key. It is optional but needed if you go over the free quota assigned by Google'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @inheritDoc
     */
    public function getCategoryName(): string
    {
        return '';
    }

    /**
     * @return array
     */
    public function getOptionsDropDown(): array
    {
        return [
            0 => t('app', 'No'),
            1 => t('app', 'Yes'),
        ];
    }

    /**
     * @return bool
     */
    public function getShowOpensMap(): bool
    {
        return (int)$this->show_opens_map === 1;
    }

    /**
     * @return bool
     */
    public function getShowClicksMap(): bool
    {
        return (int)$this->show_clicks_map === 1;
    }

    /**
     * @return bool
     */
    public function getShowUnsubscribesMap(): bool
    {
        return (int)$this->show_unsubscribes_map === 1;
    }

    /**
     * @return int
     */
    public function getOpensAtOnce(): int
    {
        return (int)$this->opens_at_once;
    }

    /**
     * @return int
     */
    public function getClicksAtOnce(): int
    {
        return (int)$this->clicks_at_once;
    }

    /**
     * @return int
     */
    public function getUnsubscribesAtOnce(): int
    {
        return (int)$this->unsubscribes_at_once;
    }

    /**
     * @return string
     */
    public function getGoogleMapsApiKey(): string
    {
        return (string)$this->google_maps_api_key;
    }

    /**
     * @return bool
     */
    public function getTranslateMap(): bool
    {
        return $this->translate_map === 1;
    }
}
