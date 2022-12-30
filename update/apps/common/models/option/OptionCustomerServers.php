<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCustomerServers
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.3.1
 */

class OptionCustomerServers extends OptionBase
{
    /**
     * @var string
     */
    public $must_add_bounce_server = self::TEXT_YES;

    /**
     * @var int
     */
    public $max_delivery_servers = 0;

    /**
     * @var int
     */
    public $max_bounce_servers = 0;

    /**
     * @var int
     */
    public $max_fbl_servers = 0;

    /**
     * @var int
     */
    public $max_email_box_monitors = 0;

    /**
     * @var string
     */
    public $can_select_delivery_servers_for_campaign = self::TEXT_NO;

    /**
     * @var string
     */
    public $can_send_from_system_servers = self::TEXT_YES;

    /**
     * @var string
     */
    public $custom_headers;

    /**
     * @var array
     */
    public $allowed_server_types = [];

    /**
     * @var string
     */
    protected $_categoryName = 'system.customer_servers';

    /**
     * @var array
     */
    protected $_serverTypesList;

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['max_delivery_servers, max_bounce_servers, max_fbl_servers, max_email_box_monitors, must_add_bounce_server, can_select_delivery_servers_for_campaign, can_send_from_system_servers', 'required'],
            ['must_add_bounce_server, can_select_delivery_servers_for_campaign, can_send_from_system_servers', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['max_delivery_servers, max_bounce_servers, max_fbl_servers, max_email_box_monitors', 'numerical', 'integerOnly' => true, 'min' => -1, 'max' => 1000],
            ['allowed_server_types, custom_headers', 'safe'],
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
            'must_add_bounce_server'                    => $this->t('Must add bounce server'),
            'max_delivery_servers'                      => $this->t('Max. delivery servers'),
            'max_bounce_servers'                        => $this->t('Max. bounce servers'),
            'max_fbl_servers'                           => $this->t('Max. feedback loop servers'),
            'max_email_box_monitors'                    => $this->t('Max. email box monitors'),
            'can_select_delivery_servers_for_campaign'  => $this->t('Can select delivery servers for campaigns'),
            'can_send_from_system_servers'              => $this->t('Can send from system servers'),
            'allowed_server_types'                      => $this->t('Allowed server types'),
            'custom_headers'                            => $this->t('Custom headers'),
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
            'must_add_bounce_server'                    => '',
            'max_delivery_servers'                      => '',
            'max_bounce_servers'                        => '',
            'max_fbl_servers'                           => '',
            'max_email_box_monitors'                    => '',
            'can_select_delivery_servers_for_campaign'  => '',
            'can_send_from_system_servers'              => '',
            'allowed_server_types'                      => '',
            'custom_headers'                            => 'X-Header-A: 1111' . PHP_EOL . 'X-Header-B: 2222' . PHP_EOL . 'X-Header-B: 3333',
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
            'must_add_bounce_server'                    => $this->t('Whether customers are forced to add a bounce server for each delivery server'),
            'max_delivery_servers'                      => $this->t('How many delivery servers a customer is allowed to add, set to -1 for unlimited'),
            'max_bounce_servers'                        => $this->t('How many bounce servers a customer is allowed to add, set to -1 for unlimited'),
            'max_fbl_servers'                           => $this->t('How many feedback loop servers a customer is allowed to add, set to -1 for unlimited'),
            'max_email_box_monitors'                    => $this->t('How many email box monitors a customer is allowed to add, set to -1 for unlimited'),
            'can_select_delivery_servers_for_campaign'  => $this->t('Whether customers are able to select what delivery servers to use in campaigns'),
            'can_send_from_system_servers'              => $this->t('Whether customers can use the system servers for sending emails. If they have their own servers, this is used as a fallback mechanism when their servers are unavailable'),
            'allowed_server_types'                      => $this->t('What types of servers are customers allowed to add. This is matched against core server types'),
            'custom_headers'                            => $this->t('Custom headers that apply to all delivery servers. Please make sure you write one HeaderName:HeaderValue per line. Please note that all headers must start with the X- prefix'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getServerTypesList(): array
    {
        if ($this->_serverTypesList !== null) {
            return $this->_serverTypesList;
        }
        return $this->_serverTypesList = DeliveryServer::getTypesList();
    }

    /**
     * @return array
     */
    public function getQuotaPercentageList(): array
    {
        static $list = [];
        if (!empty($list)) {
            return $list;
        }

        for ($i = 1; $i <= 95; ++$i) {
            if ($i % 5 == 0) {
                $list[$i] = $i;
            }
        }

        return $list;
    }

    /**
     * @return string
     */
    public function getCustomHeaders(): string
    {
        return (string)$this->custom_headers;
    }

    /**
     * @return array
     */
    public function getAllowedServerTypes(): array
    {
        return (array)$this->allowed_server_types;
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        if (!is_array($this->allowed_server_types)) {
            $this->allowed_server_types = [];
        }

        $allServerTypes = $this->getServerTypesList();
        $allowedServerTypes = [];

        foreach ($this->allowed_server_types as $type => $answer) {
            if ($answer == 'yes' && isset($allServerTypes[$type])) {
                $allowedServerTypes[] = $type;
            }
        }

        $this->allowed_server_types = $allowedServerTypes;
        $this->custom_headers = DeliveryServerHelper::getOptionCustomerCustomHeadersStringFromString($this->custom_headers);

        return parent::beforeValidate();
    }
}
