<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SiteController
 *
 * Default api application controller
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class SiteController extends Controller
{
    /**
     * @return array
     */
    public function accessRules()
    {
        return [
            // allow all users on all actions
            ['allow'],
        ];
    }

    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     *
     * By default we don't return any information from this action.
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $this->renderJson();
    }

    /**
     * This is the action to handle external exceptions.
     *
     * @return void
     * @throws CException
     */
    public function actionError()
    {
        if ($error = app()->getErrorHandler()->error) {
            if ($error['code'] === 404) {
                $error['message'] = t('app', 'Page not found.');
            }
            $this->renderJson([
                'status'    => 'error',
                'error'        => html_encode($error['message']),
            ], $error['code']);
        }
    }
}
