<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * StartPagesWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.9.2
 */

class StartPagesWidget extends CWidget
{
    /**
     * @var CAttributeCollection
     */
    public $collection;

    /**
     * @var bool
     */
    public $enabled = false;

    /**
     * @var string
     */
    public $application = '';

    /**
     * @var string
     */
    public $route = '';

    /**
     * @var StartPage|null
     */
    public $page;

    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        clientScript()->registerCssFile(apps()->getBaseUrl('assets/css/start-page.css'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        if (!$this->enabled || !$this->collection->itemAt('renderGrid')) {
            return;
        }

        if (!$this->application) {
            $this->application = apps()->getCurrentAppName();
        }

        if (!$this->route) {
            $this->route = app()->getController()->getRoute();
        }

        if (empty($this->page) || !($this->page instanceof StartPage)) {
            $this->page = StartPage::model()->findByAttributes([
                'application' => $this->application,
                'route'       => $this->route,
            ]);
        }

        if (empty($this->page)) {
            return;
        }

        $this->collection->add('renderGrid', false);

        $searchReplace = [
            '[CUSTOMER_BASE_URL]'   => apps()->getAppBaseUrl('customer'),
            '[BACKEND_BASE_URL]'    => apps()->getAppBaseUrl('backend'),
            '[FRONTEND_BASE_URL]'   => apps()->getAppBaseUrl('frontend'),
            '[API_BASE_URL]'        => apps()->getAppBaseUrl('api'),
        ];

        $page        = $this->page;
        $pageContent = $page->content;
        $pageHeading = $page->heading;
        $pageContent = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $pageContent);
        $pageHeading = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $pageHeading);

        $this->render('start-page', [
            'page'        => $page,
            'pageContent' => $pageContent,
            'pageHeading' => $pageHeading,
        ]);
    }
}
