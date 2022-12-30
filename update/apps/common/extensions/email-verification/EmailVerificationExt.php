<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailVerificationExt
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 */

class EmailVerificationExt extends ExtensionInit
{
    /**
     * @var string
     */
    public $name = 'Email verification';

    /**
     * @var string
     */
    public $description = 'Check email address validity.';

    /**
     * @var string
     */
    public $version = '2.0.0';

    /**
     * @var string
     */
    public $minAppVersion = '2.0.0';

    /**
     * @var string
     */
    public $author = 'MailWizz Development Team';

    /**
     * @var string
     */
    public $website = 'https://www.mailwizz.com/';

    /**
     * @var string
     */
    public $email = 'support@mailwizz.com';

    /**
     * @var array
     */
    public $allowedApps = ['*'];

    /**
     * @var bool
     */
    public $cliEnabled = true;

    /**
     * @inheritDoc
     */
    public function run()
    {
        $this->importClasses('common.models.*');
        $this->importClasses('common.models.providers.*');

        // global collection
        if (!app_param('extensions.email-checkers.emails')) {
            app_param_set('extensions.email-checkers.emails', new CMap());
        }

        $handler = new EmailVerificationProvidersHandler($this);
        $handler->registerEmailCheckersProviders();

        if ($this->isAppName('backend')) {
            $handler->backendApp();
        }

        if ($this->isAppName('customer')) {
            $handler->customerApp();
        }

        $handler->runCheckers();
    }

    /**
     * @inheritDoc
     */
    public function getPageUrl()
    {
        return $this->createUrl('providers/index');
    }
}
