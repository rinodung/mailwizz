<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailVerificationProvider
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */
class EmailVerificationProvider
{
    /**
     * @var ExtensionInit
     */
    protected $_extension;

    /**
     * @var string
     */
    protected $_id;

    /**
     * @var array
     */
    protected $_urlRules = [];

    /**
     * @var array
     */
    protected $_controllerMaps = [];

    /**
     * EmailVerificationProvidersHandler constructor.
     * @param ExtensionInit $extension
     * @param string $id
     */
    public function __construct(ExtensionInit $extension, string $id)
    {
        $this->_extension = $extension;
        $this->_id = $id;

        $this->_urlRules['backend'] = [];
        $this->_urlRules['customer'] = [];

        $this->_controllerMaps['backend'] = [];
        $this->_controllerMaps['customer'] = [];

        $backendRule = [
            $this->getId() . '/index', 'pattern' => $this->getExtensionAsSlug() . '/' . $this->getIdAsSlug(),
        ];
        $this->addBackendUrlRule($backendRule);

        $controllerMap = [
            'class' => sprintf('backend.controllers.%s', $this->getBackendControllerName()),
        ];
        $this->addBackendControllerMap($controllerMap);

        $customerRule = [
            $this->getId() . '/index', 'pattern' => $this->getExtensionAsSlug() . '/' . $this->getIdAsSlug(),
        ];
        $this->addCustomerUrlRule($customerRule);

        $controllerMap = [
            'class' => sprintf('customer.controllers.%s', $this->getCustomerControllerName()),
        ];
        $this->addCustomerControllerMap($controllerMap);
    }

    /**
     * @return void
     */
    public function register(): void
    {
        container()->add($this->getCommonClassName(), $this->getCommonClassName());
        container()->add($this->getCustomerClassName(), $this->getCustomerClassName());
    }

    /**
     * @param Customer $customer
     * @return bool
     */
    public function getHasCustomerAreaAccess(Customer $customer): bool
    {
        static $hasAccess = [];

        if (!isset($hasAccess[$customer->customer_id])) {
            $hasAccess[$customer->customer_id] = [];
        }

        if (array_key_exists($customer->customer_id, $hasAccess)) {
            if (array_key_exists($this->getId(), $hasAccess[$customer->customer_id])) {
                return $hasAccess[$customer->customer_id][$this->getId()];
            }
        }

        /** @var EmailVerificationExtBaseCommon $commonModel */
        $commonModel = container()->get($this->getCommonClassName());
        if (!$commonModel->getIsEnabled()) {
            return $hasAccess[$customer->customer_id][$this->getId()] = false;
        }

        // check if the customer is allowed
        $allowedGroups = $commonModel->getCustomerGroups();
        if (!empty($allowedGroups) && !in_array($customer->group_id, $allowedGroups)) {
            return $hasAccess[$customer->customer_id][$this->getId()] = false;
        }

        return $hasAccess[$customer->customer_id][$this->getId()] = true;
    }

    /**
     * @param array $rule
     */
    public function addBackendUrlRule(array $rule): void
    {
        $this->_urlRules['backend'] = array_merge($this->_urlRules['backend'], $rule);
    }

    /**
     * @param array $rule
     */
    public function addCustomerUrlRule(array $rule): void
    {
        $this->_urlRules['customer'] = array_merge($this->_urlRules['customer'], $rule);
    }

    /**
     * @param array $map
     */
    public function addBackendControllerMap(array $map): void
    {
        $this->_controllerMaps['backend'] = array_merge($this->_controllerMaps['backend'], $map);
    }

    /**
     * @param array $map
     */
    public function addCustomerControllerMap(array $map): void
    {
        $this->_controllerMaps['customer'] = array_merge($this->_controllerMaps['customer'], $map);
    }

    /**
     * @return array
     */
    public function getUrlRules(): array
    {
        return $this->_urlRules;
    }

    /**
     * @return array
     */
    public function getBackendUrlRules(): array
    {
        return $this->_urlRules['backend'];
    }

    /**
     * @return array
     */
    public function getCustomerUrlRules(): array
    {
        return $this->_urlRules['customer'];
    }

    /**
     * @return array
     */
    public function getBackendControllerMaps(): array
    {
        return $this->_controllerMaps['backend'];
    }

    /**
     * @return array
     */
    public function getCustomerControllerMaps(): array
    {
        return $this->_controllerMaps['customer'];
    }

    /**
     * @return array
     */
    public function getControllerMaps(): array
    {
        return $this->_controllerMaps;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->_id;
    }

    /**
     * @return string
     */
    public function getIdAsCamelCase(): string
    {
        return StringHelper::toCamelCase($this->getId(), true);
    }

    /**
     * @return string
     */
    public function getIdAsSlug(): string
    {
        return (string)str_replace('_', '-', $this->getId());
    }

    /**
     * @return string
     */
    public function getExtensionClassName(): string
    {
        return (string)str_replace($this->_extension->getPathOfAlias(), '', get_class($this->_extension));
    }

    /**
     * @return string
     */
    public function getPathOfAlias(): string
    {
        return $this->_extension->getPathOfAlias('common.models.providers');
    }

    /**
     * @return string
     */
    public function getExtensionAsSlug(): string
    {
        return (string)str_replace(['_', '-ext'], ['-', ''], StringHelper::fromCamelCase($this->getExtensionClassName()));
    }

    /**
     * @return string
     */
    public function getCommonClassName(): string
    {
        return sprintf('%s%sCommon', $this->getExtensionClassName(), $this->getIdAsCamelCase());
    }

    /**
     * @return string
     */
    public function getCustomerClassName(): string
    {
        return sprintf('%s%sCustomer', $this->getExtensionClassName(), $this->getIdAsCamelCase());
    }

    /**
     * @return string
     */
    public function getBackendControllerName(): string
    {
        return sprintf('%sBackend%sController', $this->getExtensionClassName(), $this->getIdAsCamelCase());
    }

    /**
     * @return string
     */
    public function getCustomerControllerName(): string
    {
        return sprintf('%sCustomer%sController', $this->getExtensionClassName(), $this->getIdAsCamelCase());
    }
}
