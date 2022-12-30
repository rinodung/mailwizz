<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionSpfDkim
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.6
 */

class OptionSpfDkim extends OptionBase
{
    /**
     * @var string
     */
    public $spf = '';

    /**
     * @var string
     */
    public $dmarc = '';

    /**
     * @var string
     */
    public $dkim_public_key = '';

    /**
     * @var string
     */
    public $dkim_private_key = '';

    /**
     * @var string
     */
    public $update_sending_domains = self::TEXT_NO;

    /**
     * @var string
     */
    protected $_categoryName = 'system.dns.spf_dkim';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['spf, dmarc', 'safe'],
            ['dkim_private_key', 'match', 'pattern' => '/-----BEGIN\sRSA\sPRIVATE\sKEY-----(.*)-----END\sRSA\sPRIVATE\sKEY-----/sx'],
            ['dkim_public_key', 'match', 'pattern' => '/-----BEGIN\sPUBLIC\sKEY-----(.*)-----END\sPUBLIC\sKEY-----/sx'],
            ['dkim_private_key, dkim_public_key', 'length', 'max' => 10000],
            ['update_sending_domains', 'in', 'range' => array_keys($this->getYesNoOptions())],

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
            'spf'                    => $this->t('The SPF value'),
            'dmarc'                  => $this->t('The DMARC value'),
            'dkim_private_key'       => $this->t('Dkim private key'),
            'dkim_public_key'        => $this->t('Dkim public key'),
            'update_sending_domains' => $this->t('Update sending domains'),
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
            'spf'              => 'v=spf1 mx ~all',
            'dmarc'            => 'v=DMARC1; p=none',
            'dkim_private_key' => "-----BEGIN RSA PRIVATE KEY-----\n ... \n-----END RSA PRIVATE KEY-----",
            'dkim_public_key'  => "-----BEGIN PUBLIC KEY-----\n ... \n-----END PUBLIC KEY-----",
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
            'spf'                       => $this->t('The SPF value'),
            'dmarc'                     => $this->t('The DMARC value'),
            'update_sending_domains'    => $this->t('Whether to update the sending domains with the new keys and force them to be revalidated'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return string
     */
    public function getSpf(): string
    {
        return (string)$this->spf;
    }

    /**
     * @return string
     */
    public function getDmarc(): string
    {
        return (string)$this->dmarc;
    }

    /**
     * @return string
     */
    public function getDkimPublicKey(): string
    {
        return (string)$this->dkim_public_key;
    }

    /**
     * @return string
     */
    public function getDkimPrivateKey(): string
    {
        return (string)$this->dkim_private_key;
    }
}
