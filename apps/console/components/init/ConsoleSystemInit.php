<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ConsoleSystemInit
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.1
 */

class ConsoleSystemInit extends CApplicationComponent
{
    /**
     * @var bool
     */
    protected $_hasRanOnBeginRequest = false;

    /**
     * @var bool
     */
    protected $_hasRanOnEndRequest = false;

    /**
     * ConsoleSystemInit::init()
     *
     * Init the console system and attach the event handlers
     *
     * @return void
     * @throws CException
     */
    public function init()
    {
        parent::init();

        // attach the event handler to the onBeginRequest event
        app()->attachEventHandler('onBeginRequest', [$this, 'runOnBeginRequest']);

        // attach the event handler to the onEndRequest event
        app()->attachEventHandler('onEndRequest', [$this, 'runOnEndRequest']);
    }

    /**
     * This will run on begin of request
     * It's important since when updating the app, if the app is online the console commands will fail
     * and the campaigns will remain stuck
     *
     * @param CEvent $event
     *
     * @return void
     */
    public function runOnBeginRequest(CEvent $event)
    {
        if ($this->_hasRanOnBeginRequest) {
            return;
        }

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        // if the site offline, stop.
        if (!($common->getIsSiteOnline())) {
            // since 1.3.4.8
            // if it's the update command then just go ahead.
            if (!empty($_SERVER['argv']) && !empty($_SERVER['argv'][1]) && in_array($_SERVER['argv'][1], ['update', 'auto-update'])) {
                // mark the event as completed
                $this->_hasRanOnBeginRequest = true;
                // and continue execution by returing from this method
                return;
            }

            // otherwise stop execution
            app()->end();
        }

        // and mark the event as completed.
        $this->_hasRanOnBeginRequest = true;
    }

    /**
     * This is kept as reference for future additions
     *
     * @param CEvent $event
     *
     * @return void
     */
    public function runOnEndRequest(CEvent $event)
    {
        if ($this->_hasRanOnEndRequest) {
            return;
        }

        // and mark the event as completed.
        $this->_hasRanOnEndRequest = true;
    }
}
