<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCdn
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.4
 */

class OptionCdn extends OptionBase
{
    /**
     * @var string
     */
    public $enabled = self::TEXT_NO;

    /**
     * @var string
     */
    public $subdomain;

    /**
     * @var string
     */
    public $use_for_email_assets = self::TEXT_NO;

    /**
     * @var string
     */
    protected $_categoryName = 'system.cdn';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['subdomain', '_validateSubdomain'],
            ['enabled, use_for_email_assets', 'in', 'range' => array_keys($this->getYesNoOptions())],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = [
            'enabled'              => $this->t('Enabled'),
            'subdomain'            => $this->t('Sub domain'),
            'use_for_email_assets' => $this->t('Use for email assets'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'subdomain' => 'https://d160eil82t111i.cloudfront.net',
        ];
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'enabled'              => $this->t('Whether the feature is enabled.'),
            'subdomain'            => $this->t('The CDN sub domain where the assets will be published and loaded from. You can include the http:// or https:// prefix scheme.'),
            'use_for_email_assets' => $this->t('Whether to publish the email assets, such as images, over the CDN.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateSubdomain(string $attribute, array $params = []): void
    {
        $validator = new CUrlValidator();
        $validator->allowEmpty = true;
        if (!empty($this->$attribute) && stripos($this->$attribute, 'http') !== 0) {
            $this->$attribute = 'http://' . $this->$attribute;
        }
        if (!empty($this->$attribute) && !$validator->validateValue($this->$attribute)) {
            $this->addError('subdomain', $this->t('Subdomain is not valid!'));
        }
    }

    /**
     * @return string
     */
    public function getSubdomain(): string
    {
        return (string)$this->subdomain;
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return $this->enabled === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getUseForEmailAssets(): bool
    {
        return $this->use_for_email_assets === self::TEXT_YES;
    }
}
