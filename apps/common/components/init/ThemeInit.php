<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ThemeInit
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

abstract class ThemeInit extends CApplicationComponent
{
    /**
     * @var string
     */
    public $name = 'Missing theme name';

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
    public $email;

    /**
     * @var string
     */
    public $description = 'Missing theme description';

    /**
     * @var string
     */
    public $version = '1.0';

    /**
     * @return ReflectionClass<ThemeInit>
     */
    final public function getReflection(): ?ReflectionClass
    {
        static $_reflection;
        if ($_reflection) {
            return $_reflection;
        }
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
        static $_dirName;
        if ($_dirName) {
            return $_dirName;
        }

        /** @var ReflectionClass<ThemeInit> $reflection */
        $reflection = $this->getReflection();

        return $_dirName = basename(dirname((string)$reflection->getFilename()));
    }

    /**
     * @return string
     */
    final public function getPathAliasWithPrefix(): string
    {
        return 'theme-' . $this->getDirName();
    }

    /**
     * @return string
     */
    final public function getPathAliasWithSuffix(): string
    {
        return $this->getDirName() . '-theme';
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
        $appName = apps()->getCurrentAppName();
        return options()->set('system.theme.' . $appName . '.' . $this->getDirName() . '.data.' . $key, $value);
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
        $appName = apps()->getCurrentAppName();
        return options()->get('system.theme.' . $appName . '.' . $this->getDirName() . '.data.' . $key, $defaultValue);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    final public function removeOption(string $key)
    {
        if (empty($key)) {
            return false;
        }
        $appName = apps()->getCurrentAppName();
        return options()->remove('system.theme.' . $appName . '.' . $this->getDirName() . '.data.' . $key);
    }

    /**
     * @return bool
     */
    final public function removeAllOptions(): bool
    {
        $appName = apps()->getCurrentAppName();
        return options()->removeCategory('system.theme.' . $appName . '.' . $this->getDirName() . '.data');
    }

    /**
     * @return string
     */
    final public function getBaseUrl(): string
    {
        /** @var CWebApplication $app */
        $app = app();

        return $app->getTheme()->getBaseUrl();
    }

    /**
     * @return string
     */
    final public function getBasePath(): string
    {
        /** @var CWebApplication $app */
        $app = app();

        return $app->getTheme()->getBasePath();
    }

    /**
     * @return mixed
     * @throws CHttpException
     */
    public function settingsPage()
    {
        throw new CHttpException(404, t('app', 'The requested page does not exist.'));
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
     *
     * @return void
     */
    final public function importClasses(string $alias): void
    {
        try {
            Yii::import($this->getPathAlias($alias));
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
    abstract public function run();
}
