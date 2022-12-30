<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionSocialLinks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.5
 */

class OptionSocialLinks extends OptionBase
{
    /**
     * @var string
     */
    public $facebook = '';

    /**
     * @var string
     */
    public $twitter = '';

    /**
     * @var string
     */
    public $linkedin = '';

    /**
     * @var string
     */
    public $instagram = '';

    /**
     * @var string
     */
    public $youtube = '';

    /**
     * @var string
     */
    protected $_categoryName = 'system.social_links';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['facebook, twitter, linkedin, instagram, youtube', 'length', 'max' => 255],
            ['facebook, twitter, linkedin, instagram, youtube', 'url'],
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
            'facebook'  => $this->t('Facebook'),
            'twitter'   => $this->t('Twitter'),
            'linkedin'  => $this->t('Linkedin'),
            'instagram' => $this->t('Instagram'),
            'youtube'   => $this->t('Youtube'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'facebook'  => $this->t('Your business facebook url'),
            'twitter'   => $this->t('Your business twitter url'),
            'linkedin'  => $this->t('Your business linkedin url'),
            'instagram' => $this->t('Your business instagram url'),
            'youtube'   => $this->t('Your business youtube url'),
        ];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }
}
