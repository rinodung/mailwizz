<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DependencyInjectionContainer
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class DependencyInjectionContainer extends CApplicationComponent
{
    /**
     * @var League\Container\Container
     */
    private $_container;

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->registerOptionModels();
        $this->registerMiscClasses();
    }

    /**
     * @return League\Container\Container
     */
    public function getContainer(): League\Container\Container
    {
        if ($this->_container === null) {
            $this->_container = new League\Container\Container();
        }

        return $this->_container;
    }

    /**
     * Register the option models
     *
     * @return $this
     */
    protected function registerOptionModels(): self
    {
        if (!defined('MW_APPS_PATH')) {
            return $this;
        }

        $finder = (new Symfony\Component\Finder\Finder())
            ->files()
            ->name('Option*.php')
            ->in(MW_APPS_PATH . '/common/models/option');

        foreach ($finder as $file) {
            $className = basename($file->getFilename(), '.php');
            $this->getContainer()->add($className, $className);
        }

        return $this;
    }

    /**
     * Register misc classes
     *
     * @return $this
     */
    protected function registerMiscClasses(): self
    {
        if (!defined('MW_APPS_PATH')) {
            return $this;
        }

        $this->getContainer()->add(CustomerSubaccountHelper::class, CustomerSubaccountHelper::class);

        return $this;
    }
}
