<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * HtmlBlocksExt
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class HtmlBlocksExt extends ExtensionInit
{
    /**
     * @var string
     */
    public $name = 'Html blocks';

    /**
     * @var string
     */
    public $description = 'Inject html blocks in various app sections';

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
    public $allowedApps = ['backend', 'customer'];

    /**
     * @var bool
     */
    protected $_canBeDeleted = false;

    /**
     * @var bool
     */
    protected $_canBeDisabled = true;

    /**
     * @inheritDoc
     */
    public function run()
    {
        $this->importClasses('common.models.*');

        // register the common model in container for singleton access
        container()->add(HtmlBlocksExtCommon::class, HtmlBlocksExtCommon::class);

        if ($this->isAppName('backend')) {

            // handle all backend related tasks
            $this->backendApp();
        } elseif ($this->isAppName('customer')) {

            // handle all customer related tasks
            $this->customerApp();
        }
    }

    /**
     * @return string
     */
    public function getPageUrl()
    {
        return $this->createUrl('settings/index');
    }

    /**
     * @param Controller $controller
     *
     * @return void
     */
    public function _customerInjectFooterHtml($controller)
    {
        /** @var HtmlBlocksExtCommon $model */
        $model = container()->get(HtmlBlocksExtCommon::class);

        echo $model->getCustomerFooter();
    }

    /**
     * Handle all backend related tasks
     *
     * @return void
     */
    protected function backendApp()
    {
        $this->addUrlRules([
            ['settings/index', 'pattern' => 'extensions/html-blocks/settings'],
            ['settings/<action>', 'pattern' => 'extensions/html-blocks/settings/*'],
        ]);

        $this->addControllerMap([
            'settings' => [
                'class' => 'backend.controllers.HtmlBlocksExtBackendSettingsController',
            ],
        ]);
    }

    /**
     * Handle all customer related tasks
     *
     * @return void
     */
    protected function customerApp()
    {
        if (!customer()->getId()) {
            return;
        }

        hooks()->addAction('layout_footer_html', [$this, '_customerInjectFooterHtml']);
    }
}
