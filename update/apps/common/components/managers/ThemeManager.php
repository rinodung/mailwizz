<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ThemeManager
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3
 */

class ThemeManager extends CThemeManager
{
    /**
     * @var array
     */
    protected $themesInstances = [];

    /**
     * @var array
     */
    protected $_errors = [];

    /**
     * @param string $name
     *
     * @return CTheme|null
     * @throws CException
     */
    public function getTheme($name)
    {
        $themePath = $this->getBasePath() . DIRECTORY_SEPARATOR . $name;
        $className = StringHelper::simpleCamelCase($name);
        $className.='Theme';

        if (!is_dir($themePath) || !is_file($themePath . DIRECTORY_SEPARATOR . $className . '.php')) {
            return null;
        }

        $class = Yii::import($this->themeClass, true);

        /** @var CTheme $theme */
        $theme = new $class($name, $themePath, $this->getBaseUrl() . '/' . $name);

        return $theme;
    }

    /**
     * @inheritDoc
     */
    public function getThemeNames()
    {
        return array_keys($this->getAppThemes());
    }

    /**
     * @inheritDoc
     */
    public function getBasePath()
    {
        return parent::getBasePath();
    }

    /**
     * @param string $value
     *
     * @return void
     * @throws CException
     */
    public function setBasePath($value)
    {
        parent::setBasePath($value);
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return parent::getBaseUrl();
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function setBaseUrl($value)
    {
        parent::setBaseUrl($value);
    }

    /**
     * Start custom implementation of the manager
     *
     * @param string $appName
     *
     * @return bool
     * @throws ReflectionException
     */
    public function setAppTheme(string $appName = ''): bool
    {
        /** @var CWebApplication $app */
        $app = app();

        if ($app->getTheme()) {
            return true;
        }

        /** @var string $appName */
        $appName = $this->correctAppName($appName);

        $enabledThemeName = (string)options()->get('system.theme.' . $appName . '.enabled_theme');
        if (empty($enabledThemeName)) {
            return false;
        }

        if (!$this->isThemeEnabled($enabledThemeName, $appName)) {
            return false;
        }

        $this->registerAssets();
        $app->setTheme($enabledThemeName);

        /** @var ThemeInit|null $instance */
        $instance = $this->getThemeInstance($enabledThemeName, $appName);

        if (empty($instance)) {
            return false;
        }

        /** @var ReflectionClass<ThemeInit> $reflection */
        $reflection = $instance->getReflection();

        Yii::setPathOfAlias($instance->getPathAliasWithPrefix(), dirname((string)$reflection->getFilename()));
        Yii::setPathOfAlias($instance->getPathAliasWithSuffix(), dirname((string)$reflection->getFilename()));

        $instance->run();

        return true;
    }

    /**
     * @param string $appName
     *
     * @return array
     */
    public function getAppThemes(string $appName = ''): array
    {
        /** @var string $appName */
        $appName = $this->correctAppName($appName);

        static $themes = [];
        if (!empty($themes[$appName])) {
            return (array)$themes[$appName];
        }

        $themes[$appName]   = [];
        $webApps            = apps()->getWebApps();
        $searchReplace      = [];

        foreach ($webApps as $webApp) {
            $searchReplace[$webApp . '/'] = $appName . '/';
        }

        $basePath = $this->getBasePath();
        $basePath = (string)str_replace(MW_ROOT_PATH, '', $basePath);
        $basePath = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $basePath);
        $basePath = MW_ROOT_PATH . $basePath;

        if (!is_dir($basePath)) {
            return $themes[$appName];
        }

        $themesFolders = (array)FileSystemHelper::getDirectoryNames($basePath);
        $reservedNames = (array)$webApps;

        foreach ($themesFolders as $folderName) {
            if (in_array($folderName, $reservedNames)) {
                continue;
            }
            $className = StringHelper::simpleCamelCase($folderName);
            $className.='Theme';

            if (!is_file($classFile = $basePath . DIRECTORY_SEPARATOR . $folderName . DIRECTORY_SEPARATOR . $className . '.php')) {
                continue;
            }

            $themes[$appName][$folderName] = $classFile;
        }

        array_multisort($themes[$appName], SORT_ASC, SORT_REGULAR);

        return $themes[$appName];
    }

    /**
     * @param string $appName
     *
     * @return array
     */
    public function getThemesInstances(string $appName = '')
    {
        $appName    = $this->correctAppName($appName);
        $themes     = $this->getAppThemes($appName);
        $instances  = [];

        foreach ($themes as $themeName => $initClass) {

            /** @var ThemeInit|null $instance */
            $instance = $this->getThemeInstance($themeName, $appName);

            if (!empty($instance)) {
                $instances[] = $instance;
            }
        }

        return $instances;
    }

    /**
     * @param string $themeName
     * @param string $appName
     *
     * @return ThemeInit|null
     */
    public function getThemeInstance(string $themeName, string $appName = ''): ?ThemeInit
    {
        $appName = $this->correctAppName($appName);
        $themes  = $this->getAppThemes($appName);

        if (!$this->themeExists($themeName, $appName)) {
            return null;
        }

        if (isset($this->themesInstances[$appName][$themeName])) {
            return $this->themesInstances[$appName][$themeName];
        }

        require_once $themes[$themeName];
        $className = basename($themes[$themeName], '.php');

        if (!isset($this->themesInstances[$appName]) || !is_array($this->themesInstances[$appName])) {
            $this->themesInstances[$appName] = [];
        }

        /** @var ThemeInit $theme */
        $theme = $this->themesInstances[$appName][$themeName] = new $className();

        return $theme;
    }

    /**
     * @param string $themeName
     * @param string $appName
     *
     * @return bool
     */
    public function themeExists(string $themeName, string $appName = ''): bool
    {
        $appName = $this->correctAppName($appName);
        return array_key_exists($themeName, $this->getAppThemes($appName));
    }

    /**
     * @param string $themeName
     * @param string $appName
     *
     * @return bool
     */
    public function isThemeEnabled(string $themeName, string $appName = ''): bool
    {
        $appName = $this->correctAppName($appName);
        return $this->themeExists($themeName, $appName) && options()->get('system.theme.' . $appName . '.enabled_theme') == $themeName;
    }

    /**
     * @param string $themeName
     * @param string $appName
     *
     * @return bool
     */
    public function enableTheme(string $themeName, string $appName = ''): bool
    {
        $appName = $this->correctAppName($appName);

        if (!$this->themeExists($themeName, $appName)) {
            $this->_errors[] = t('themes', 'The theme does not exists.');
            return false;
        }

        if ($this->isThemeEnabled($themeName, $appName)) {
            $this->_errors[] = t('themes', 'The theme is already enabled.');
            return false;
        }

        /** @var ThemeInit|null $instance */
        $instance = $this->getThemeInstance($themeName, $appName);

        if (empty($instance)) {
            return false;
        }

        if ($instance->beforeEnable() === false) {
            $this->_errors[] = t('themes', 'Enabling the theme {theme} has failed.', [
                '{theme}' => $instance->name,
            ]);
            return false;
        }

        options()->set('system.theme.' . $appName . '.enabled_theme', $themeName);

        $instance->afterEnable();

        return true;
    }

    /**
     * @param string $themeName
     * @param string $appName
     *
     * @return bool
     */
    public function disableTheme(string $themeName, string $appName = ''): bool
    {
        $appName = $this->correctAppName($appName);

        if (!$this->themeExists($themeName, $appName)) {
            $this->_errors[] = t('themes', 'The theme does not exists.');
            return false;
        }

        if (!$this->isThemeEnabled($themeName, $appName)) {
            return true;
        }

        /** @var ThemeInit|null $instance */
        $instance = $this->getThemeInstance($themeName, $appName);

        if (empty($instance)) {
            return false;
        }

        if ($instance->beforeDisable() === false) {
            $this->_errors[] = t('themes', 'The theme could not be disabled.');
            return false;
        }

        options()->remove('system.theme.' . $appName . '.enabled_theme');

        $instance->afterDisable();

        return true;
    }

    /**
     * @param string $themeName
     * @param string $appName
     *
     * @return bool
     */
    public function deleteTheme(string $themeName, string $appName = ''): bool
    {
        $appName = $this->correctAppName($appName);

        if (!$this->themeExists($themeName, $appName)) {
            $this->_errors[] = t('themes', 'The theme does not exists.');
            return false;
        }

        if (!$this->disableTheme($themeName, $appName)) {
            return false;
        }

        /** @var ThemeInit|null $instance */
        $instance = $this->getThemeInstance($themeName, $appName);

        if (empty($instance)) {
            return false;
        }

        if ($instance->beforeDelete() === false) {
            $this->_errors[] = t('themes', 'The theme cannot be deleted.');
            return false;
        }

        options()->remove('system.theme.' . $appName . '.enabled_theme');
        options()->removeCategory('system.theme.' . $appName . '.' . $instance->getDirName());
        options()->removeCategory('system.theme.' . $appName . '.' . $instance->getDirName() . '.data');

        $instance->afterDelete();

        /** @var ReflectionClass<ThemeInit> $reflection */
        $reflection = $instance->getReflection();

        /** @var string $dirToDelete */
        $dirToDelete = dirname((string)$reflection->getFilename());

        if (file_exists($dirToDelete) && is_dir($dirToDelete)) {
            FileSystemHelper::deleteDirectoryContents($dirToDelete, true, 1);
        }

        return true;
    }

    /**
     * @param string $error
     *
     * @return ThemeManager
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
     * @param string $appName
     *
     * @return string
     */
    public function correctAppName(string $appName = ''): string
    {
        if (empty($appName)) {
            $appName = apps()->getCurrentAppName();
        }
        return $appName;
    }

    /**
     * @throws ReflectionException
     */
    final protected function registerAssets(): void
    {
        $appName    = apps()->getCurrentAppName();
        $component  = $appName . 'SystemInit';

        if (!app()->hasComponent($component)) {
            return;
        }

        /** @var SystemInit $component */
        $component = app()->getComponent($component);
        if (!method_exists($component, 'registerAssets')) {
            return;
        }

        $reflection = new ReflectionMethod($component, 'registerAssets');
        if (!$reflection->isPublic()) {
            return;
        }

        $component->registerAssets();
    }
}
