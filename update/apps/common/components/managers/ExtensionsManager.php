<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ExtensionsManager
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class ExtensionsManager extends CApplicationComponent
{
    /**
     * @var array
     */
    public $paths = [];

    /**
     * @var array
     */
    protected $_extensions = [];

    /**
     * @var array
     */
    protected $_coreExtensions = [];

    /**
     * @var array
     */
    protected $_errors = [];

    /**
     * @var array
     */
    protected $_coreExtensionsList = [];

    /**
     * @throws CException
     */
    public function loadAllExtensions(): void
    {
        static $_called = false;
        if ($_called) {
            return;
        }
        $_called = true;

        if (!is_array($this->paths) || empty($this->paths) ||  count($this->_extensions) > 0) {
            return;
        }

        $sort = [];

        foreach ($this->paths as $path) {
            if (!isset($path['alias'], $path['priority'])) {
                continue;
            }
            $sort[] = (int)$path['priority'];
        }

        if (empty($sort)) {
            return;
        }

        array_multisort($sort, $this->paths);

        foreach ($this->paths as $pathData) {
            if (!isset($pathData['alias'], $pathData['priority'])) {
                continue;
            }

            $path = $pathData['alias'];
            $_path = (string)Yii::getPathOfAlias($path);

            if (!is_dir($_path)) {
                continue;
            }

            $extensions = FileSystemHelper::getDirectoryNames($_path);

            foreach ($extensions as $extName) {
                $className  = StringHelper::simpleCamelCase($extName);
                $className .= 'Ext';

                if (class_exists($className, false)) {
                    continue;
                }

                if (!is_file($extFilePath = $_path . '/' . $extName . '/' . $className . '.php')) {
                    continue;
                }

                $component = Yii::createComponent([
                    'class'    => $path . '.' . $extName . '.' . $className,
                ]);

                if (!($component instanceof ExtensionInit)) {
                    continue;
                }

                if (in_array($extName, $this->_coreExtensionsList)) {
                    $this->_coreExtensions[$extName] = $component;
                } else {
                    $this->_extensions[$extName] = $component;
                }

                /** @var ReflectionClass<ExtensionInit> $reflection */
                $reflection = $component->getReflection();

                Yii::setPathOfAlias($component->getPathAliasWithPrefix(), dirname((string)$reflection->getFilename()));
                Yii::setPathOfAlias($component->getPathAliasWithSuffix(), dirname((string)$reflection->getFilename()));
            }
        }

        $sort = [];
        foreach ($this->_coreExtensions as $ext) {
            $sort[] = (int)$ext->priority;
        }
        array_multisort($sort, $this->_coreExtensions);

        $sort = [];
        foreach ($this->_extensions as $ext) {
            $sort[] = (int)$ext->priority;
        }
        array_multisort($sort, $this->_extensions);

        /** @var ExtensionInit[] $extensions */
        $extensions = array_merge($this->_coreExtensions, $this->_extensions);

        foreach ($extensions as $extName => $ext) {
            if (!$this->isExtensionEnabled((string)$extName)) {
                continue;
            }

            /** @var OptionCommon $common */
            $common = container()->get(OptionCommon::class);

            // since 1.3.5.9
            if (version_compare($common->version, '1.3.5.9', '<=') && !options()->get('system.extension.' . $extName . '.version')) {
                options()->set('system.extension.' . $extName . '.version', $ext->version);
            }

            // since 2.0.15
            // if the extension must update, do not run it anymore
            if ($ext->getMustUpdate()) {

                // make sure this applies only for backend
                if (apps()->getCurrentAppName() === 'backend') {

                    // make sure this gets triggered only in backend web interface for logged in users.
                    if (app()->hasComponent('user') && user()->getId()) {
                        notify()->addInfo(t('extensions', 'The extension "{name}" needs updating from version {v1} to version {v2}! Please click {here} to run the update!', [
                            '{name}' => $ext->name,
                            '{v1}'   => options()->get('system.extension.' . $extName . '.version', '1.0'),
                            '{v2}'   => $ext->version,
                            '{here}' => CHtml::link(t('app', 'here'), createUrl('extensions/update', ['id' => $ext->getDirName()])),
                        ]));
                    }
                }

                // just skip to next extension
                continue;
            }

            // since 2.0.15
            // check update, just in backend
            if (apps()->getCurrentAppName() === 'backend') {
                $ext->checkUpdate();
            }

            $allowed    = (array)$ext->allowedApps;
            $notAllowed = (array)$ext->notAllowedApps;

            if (!is_array($allowed)) {
                $allowed = [];
            }

            if (!is_array($notAllowed)) {
                $notAllowed = [];
            }

            if (count($notAllowed) == 0 && count($allowed) == 0) {
                continue;
            }

            if (count($notAllowed) > 0 && (in_array(MW_APP_NAME, $notAllowed) || array_search('*', $notAllowed) !== false)) {
                continue;
            }

            if (count($allowed) > 0 && (!in_array(MW_APP_NAME, $allowed) && array_search('*', $allowed) === false)) {
                continue;
            }

            if (is_cli() && !$ext->cliEnabled) {
                continue;
            }

            $ext->run();
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function extensionExists(string $name): bool
    {
        return !empty($this->_extensions[$name]) || !empty($this->_coreExtensions[$name]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isExtensionEnabled(string $name): bool
    {
        return $this->extensionExists($name) && options()->get('system.extension.' . $name . '.status', 'disabled') === 'enabled';
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function extensionMustUpdate(string $name): bool
    {
        /** @var ExtensionInit|null $instance */
        $instance = $this->getExtensionInstance($name);

        if (empty($instance)) {
            return false;
        }

        return $instance->getMustUpdate();
    }

    /**
     * @param string $name
     * @param string $defaultValue
     *
     * @return string
     */
    public function getExtensionDatabaseVersion(string $name, string $defaultValue = '2.0.0'): string
    {
        if (!$this->extensionExists($name)) {
            return $defaultValue;
        }
        return (string)options()->get('system.extension.' . $name . '.version', $defaultValue);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function enableExtension(string $name): bool
    {
        if (!$this->extensionExists($name)) {
            $this->_errors[] = t('extensions', 'The extension does not exists.');
            return false;
        }

        if ($this->isExtensionEnabled($name)) {
            $this->_errors[] = t('extensions', 'The extension is already enabled.');
            return false;
        }

        /** @var ExtensionInit $instance */
        $instance = $this->getExtensionInstance($name);

        // since 1.3.4.5
        if (!empty($instance->minAppVersion) && version_compare(MW_VERSION, $instance->minAppVersion, '<')) {
            $this->_errors[] = t('extensions', 'The extension {ext} require your application to be at least version {version} but you are currently using version {appVersion}.', [
                '{ext}'         => $instance->name,
                '{version}'     => $instance->minAppVersion,
                '{appVersion}'  => MW_VERSION,
            ]);
            return false;
        }

        if ($instance->beforeEnable() === false) {
            $this->_errors[] = t('extensions', 'Enabling the extension {ext} has failed.', [
                '{ext}' => $instance->name,
            ]);
            return false;
        }

        options()->set('system.extension.' . $name . '.status', 'enabled');

        // since 1.3.5.9
        if (!options()->get('system.extension.' . $name . '.version')) {
            options()->set('system.extension.' . $name . '.version', $instance->version);
        }

        $instance->afterEnable();

        return true;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function disableExtension(string $name): bool
    {
        if (!$this->extensionExists($name)) {
            $this->_errors[] = t('extensions', 'The extension does not exists.');
            return false;
        }

        if (!$this->isExtensionEnabled($name)) {
            return true;
        }

        /** @var ExtensionInit $instance */
        $instance = $this->getExtensionInstance($name);

        if (!$instance->getCanBeDisabled()) {
            $this->_errors[] = t('extensions', 'The extension cannot be disabled by configuration.');
            return false;
        }

        if ($instance->beforeDisable() === false) {
            $this->_errors[] = t('extensions', 'The extension could not be disabled.');
            return false;
        }

        options()->set('system.extension.' . $name . '.status', 'disabled');

        $instance->afterDisable();

        return true;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function updateExtension(string $name): bool
    {
        if (!$this->extensionExists($name)) {
            $this->_errors[] = t('extensions', 'The extension does not exists.');
            return false;
        }

        if (!$this->isExtensionEnabled($name)) {
            $this->_errors[] = t('extensions', 'The extension has to be enabled in order to update it.');
            return false;
        }

        if (!$this->extensionMustUpdate($name)) {
            $this->_errors[] = t('extensions', 'The extension is already at the latest version.');
            return false;
        }

        /** @var ExtensionInit $instance */
        $instance = $this->getExtensionInstance($name);

        if (!$instance->update()) {
            $this->_errors[] = t('extensions', 'The extension could not be updated.');
            return false;
        }

        options()->set('system.extension.' . $name . '.version', $instance->version);

        return true;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function deleteExtension(string $name): bool
    {
        if (!$this->extensionExists($name)) {
            $this->_errors[] = t('extensions', 'The extension does not exists.');
            return false;
        }

        if (!$this->disableExtension($name)) {
            return false;
        }

        /** @var ExtensionInit $instance */
        $instance = $this->getExtensionInstance($name);

        if (!$instance->getCanBeDeleted()) {
            $this->_errors[] = t('extensions', 'The extension cannot be deleted by configuration.');
            return false;
        }

        if ($instance->beforeDelete() === false) {
            $this->_errors[] = t('extensions', 'The extension cannot be deleted.');
            return false;
        }

        options()->removeCategory('system.extension.' . $name);

        $instance->afterDelete();

        /** @var ReflectionClass<ExtensionInit> $refl */
        $refl = $instance->getReflection();

        if ($refl->getFilename()) {
            $dirToDelete = dirname((string)$refl->getFilename());
            if (file_exists($dirToDelete) && is_dir($dirToDelete)) {
                FileSystemHelper::deleteDirectoryContents($dirToDelete, true, 1);
            }
        }

        return true;
    }

    /**
     * @param string $error
     *
     * @return ExtensionsManager
     */
    public function addError(string $error): self
    {
        $this->_errors[] = $error;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->_errors);
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->_errors;
    }

    /**
     * @return ExtensionsManager
     */
    public function resetErrors(): self
    {
        $this->_errors = [];
        return $this;
    }

    /**
     * @return array
     */
    public function getExtensions(): array
    {
        return $this->_extensions;
    }

    /**
     * @param string $name
     *
     * @return ExtensionInit|null
     */
    public function getExtensionInstance($name)
    {
        return !empty($this->_extensions[$name]) ?
            $this->_extensions[$name] :
            (!empty($this->_coreExtensions[$name]) ? $this->_coreExtensions[$name] : null);
    }

    /**
     * @return array
     */
    public function getCoreExtensions(): array
    {
        return $this->_coreExtensions;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isCoreExtension(string $name): bool
    {
        return in_array($name, $this->_coreExtensionsList);
    }

    /**
     * @param array $extensions
     *
     * @return ExtensionsManager
     */
    public function setCoreExtensionsList(array $extensions): self
    {
        $this->_coreExtensionsList = CMap::mergeArray(
            (array)FileSystemHelper::getDirectoryNames((string)Yii::getPathOfAlias('common.extensions')),
            $extensions
        );
        return $this;
    }

    /**
     * @return array
     */
    public function getCoreExtensionsList(): array
    {
        return (array)$this->_coreExtensionsList;
    }

    /**
     * @return array
     */
    public function getAllExtensions(): array
    {
        return array_merge($this->_coreExtensions, $this->_extensions);
    }

    /**
     * @return array
     */
    public function getAllExtensionsNames(): array
    {
        return array_keys($this->getAllExtensions());
    }

    /**
     * @param string $sqlFile
     *
     * @return bool
     * @throws CDbException
     */
    public function runQueriesFromSqlFile(string $sqlFile): bool
    {
        if (!is_file($sqlFile)) {
            return false;
        }

        foreach (CommonHelper::getQueriesFromSqlFile($sqlFile, db()->tablePrefix) as $query) {
            db()->createCommand($query)->execute();
        }

        return true;
    }
}
