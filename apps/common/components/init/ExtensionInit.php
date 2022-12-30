<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ExtensionInit
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

abstract class ExtensionInit extends CApplicationComponent
{
    /**
     * @var string
     */
    public $name = 'Missing extension name';

    /**
     * @var string
     */
    public $author = 'Unknown';

    /**
     * @var string
     */
    public $website = 'javascript:;';

    /**
     * @var string
     */
    public $email = 'missing@email.com';

    /**
     * @var string
     */
    public $description = 'Missing extension description';

    /**
     * @var int
     */
    public $priority = 0;

    /**
     * @var string
     */
    public $version = '1.0';

    /**
     * @var string
     */
    public $minAppVersion = '1.0';

    /**
     * @var bool
     */
    public $cliEnabled = false;

    /**
     * @var array
     */
    public $notAllowedApps = [];

    /**
     * @var array
     */
    public $allowedApps = [];

    /**
     * @var bool
     */
    protected $_canBeDisabled = true;

    /**
     * @var bool
     */
    protected $_canBeDeleted = true;

    /**
     * @var CAttributeCollection
     */
    protected $_data;

    /**
     * @return bool
     */
    final public function getIsEnabled(): bool
    {
        return $this->getManager()->isExtensionEnabled($this->getDirName());
    }

    /**
     * @return bool
     */
    final public function getCanBeDisabled(): bool
    {
        if ($this->getManager()->isCoreExtension($this->getDirName())) {
            return $this->_canBeDisabled;
        }
        return true;
    }

    /**
     * @return bool
     */
    final public function getCanBeDeleted(): bool
    {
        if ($this->getManager()->isCoreExtension($this->getDirName())) {
            return $this->_canBeDeleted;
        }
        return true;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return OptionsManager
     */
    final public function setOption(string $key, $value): OptionsManager
    {
        if (empty($key)) {
            return options();
        }

        return options()->set('system.extension.' . $this->getDirName() . '.data.' . $key, $value);
    }

    /**
     * @param string $key
     * @param mixed $defaultValue
     *
     * @return mixed|null
     */
    final public function getOption(string $key, $defaultValue = null)
    {
        if (empty($key)) {
            return $defaultValue;
        }

        return options()->get('system.extension.' . $this->getDirName() . '.data.' . $key, $defaultValue);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    final public function removeOption(string $key): bool
    {
        if (empty($key)) {
            return false;
        }

        return options()->remove('system.extension.' . $this->getDirName() . '.data.' . $key);
    }

    /**
     * @return bool
     */
    final public function removeAllOptions(): bool
    {
        return options()->removeCategory('system.extension.' . $this->getDirName() . '.data');
    }

    /**
     * @return ReflectionClass<ExtensionInit>
     */
    final public function getReflection(): ?ReflectionClass
    {
        try {
            $_reflection = new ReflectionClass($this);
        } catch (Exception $e) {
            $_reflection = null;
        }
        return $_reflection;
    }

    /**
     * @return string
     */
    final public function getDirName(): string
    {
        /** @var ReflectionClass<ExtensionInit> $reflection */
        $reflection = $this->getReflection();

        return basename((string)dirname((string)$reflection->getFilename()));
    }

    /**
     * @return string
     */
    final public function getPathAliasWithPrefix(): string
    {
        return 'ext-' . $this->getDirName();
    }

    /**
     * @return string
     */
    final public function getPathAliasWithSuffix(): string
    {
        return $this->getDirName() . '-ext';
    }

    /**
     * @param string $append
     *
     * @return string
     */
    final public function getPathAlias(string $append = ''): string
    {
        return $this->getPathAliasWithSuffix() . ($append ? '.' . $append : '');
    }

    /**
     * @param string $append
     *
     * @return string
     */
    final public function getPathOfAlias(string $append = ''): string
    {
        return (string)Yii::getPathOfAlias($this->getPathAlias($append));
    }

    /**
     * @param string $message
     * @param mixed $params
     *
     * @return string
     */
    final public function t(string $message, $params = []): string
    {
        return t($this->getTranslationCategory(), $message, $params);
    }

    /**
     * @return string
     */
    final public function getTranslationCategory(): string
    {
        return str_replace('-', '_', $this->getDirName());
    }

    /**
     * @return string
     */
    final public function getRoutePrefix(): string
    {
        return str_replace('-', '_', $this->getPathAlias()) . '_';
    }

    /**
     * @param string $route
     * @param array $params
     * @param string $ampersand
     *
     * @return string
     */
    final public function createUrl(string $route, array $params = [], string $ampersand = '&'): string
    {
        return createUrl($this->getRoute($route), $params, $ampersand);
    }

    /**
     * @param string $route
     * @param array $params
     * @param string $schema
     * @param string $ampersand
     *
     * @return string
     */
    final public function createAbsoluteUrl(string $route, array $params = [], string $schema = '', string $ampersand = '&'): string
    {
        return createAbsoluteUrl($this->getRoute($route), $params, $schema, $ampersand);
    }

    /**
     * @param string $route
     *
     * @return string
     */
    final public function getRoute(string $route): string
    {
        return $this->getRoutePrefix() . $route;
    }

    /**
     * @param array $rules
     */
    final public function addUrlRules(array $rules = []): void
    {
        foreach ($rules as $index => $rule) {
            if (!isset($rule[0]) || !is_string($rule[0])) {
                continue;
            }
            if (stripos($rule[0], $this->getRoutePrefix()) !== 0) {
                $rules[$index][0] = $this->getRoute($rule[0]);
            }
        }
        urlManager()->addRules($rules);
    }

    /**
     * @param array $map
     */
    final public function addControllerMap(array $map = []): void
    {
        /** @var CWebApplication $app */
        $app = app();

        foreach ($map as $key => $value) {
            if (stripos($key, $this->getRoutePrefix()) !== 0) {
                $key = $this->getRoute($key);
            }

            if (is_array($value) && isset($value['class']) && stripos($value['class'], $this->getPathOfAlias()) !== 0) {
                $value['class'] = $this->getPathAlias($value['class']);
            }

            $app->controllerMap[$key] = $value;
        }
    }

    /**
     * @return ExtensionsManager
     */
    final public function getManager(): ExtensionsManager
    {
        return extensionsManager();
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    final public function isAppName(string $name): bool
    {
        return apps()->isAppName($name);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     *
     * @return $this
     * @throws CException
     */
    final public function setData($key, $value = null)
    {
        if (!is_array($key) && $value !== null) {
            /** @var CAttributeCollection $data */
            $data = $this->getData();
            $data->mergeWith([$key => $value], false);
        } elseif (is_array($key)) {
            /** @var CAttributeCollection $data */
            $data = $this->getData();
            $data->mergeWith($key, false);
        }
        return $this;
    }

    /**
     * @param mixed $key
     * @param mixed $defaultValue
     *
     * @return array|CAttributeCollection|mixed
     * @throws CException
     */
    final public function getData($key = null, $defaultValue = null)
    {
        if (!($this->_data instanceof CAttributeCollection)) {
            $this->_data = new CAttributeCollection($this->_data);
            $this->_data->caseSensitive=true;
        }

        if ($key !== null) {
            return $this->_data->contains($key) ? $this->_data->itemAt($key) : $defaultValue;
        }

        return $this->_data;
    }

    /**
     * @return bool
     */
    final public function getMustUpdate(): bool
    {
        return $this->getIsEnabled() && version_compare($this->getDatabaseVersion(), $this->version, '<');
    }

    /**
     * @param string $defaultValue
     *
     * @return string
     */
    final public function getDatabaseVersion(string $defaultValue = '2.0.0'): string
    {
        return (string)$this->getManager()->getExtensionDatabaseVersion($this->getDirName(), $defaultValue);
    }

    /**
     * @param string $sqlFile
     *
     * @return bool
     * @throws CDbException
     */
    final public function runQueriesFromSqlFile(string $sqlFile): bool
    {
        return $this->getManager()->runQueriesFromSqlFile($sqlFile);
    }

    /**
     * @return string
     */
    public function getPageUrl()
    {
        return '';
    }

    /**
     * @param string $alias
     * @param bool $forceInclude
     */
    final public function importClasses(string $alias, bool $forceInclude = false): void
    {
        try {
            Yii::import($this->getPathAlias($alias), $forceInclude);
        } catch (Exception $e) {
        }
    }

    /**
     * @return bool
     */
    public function beforeEnable()
    {
        return true;
    }

    /**
     * @return void
     */
    public function afterEnable()
    {
    }

    /**
     * @return bool
     */
    public function beforeDisable()
    {
        return true;
    }

    /**
     * @return void
     */
    public function afterDisable()
    {
    }

    /**
     * @return bool
     */
    public function beforeDelete()
    {
        return true;
    }

    /**
     * @return void
     */
    public function afterDelete()
    {
    }

    /**
     * @return void
     */
    public function checkUpdate()
    {
    }

    /**
     * @return bool
     */
    public function update()
    {
        return true;
    }

    /**
     * @return void
     */
    abstract public function run();
}
