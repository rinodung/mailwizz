<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Controller
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class Controller extends BaseController
{
    /**
     * @var array
     */
    public $cacheableActions = ['index', 'view'];

    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        if (!$common->getIsApiOnline() || !$common->getIsSiteOnline()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Service Unavailable.'),
            ], 503);
            return;
        }

        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        if (empty($this->cacheableActions) || !is_array($this->cacheableActions)) {
            $this->cacheableActions = ['index', 'view'];
        }
        $cacheableActions = implode(', ', $this->cacheableActions);

        return [
            [
                'api.components.web.filters.RequestAccessFilter',
            ],

            'accessControl',

            [
                'system.web.filters.CHttpCacheFilter + ' . $cacheableActions,
                'cacheControl'              => 'no-cache, must-revalidate',
                'lastModifiedExpression'    => [$this, 'generateLastModified'],
                'etagSeedExpression'        => [$this, 'generateEtagSeed'],
            ],
        ];
    }

    /**
     * @return array
     */
    public function accessRules()
    {
        return [
            // deny every action by default unless specified otherwise in child controllers.
            ['deny'],
        ];
    }

    /**
     * @return int
     */
    public function generateLastModified()
    {
        return time();
    }

    /**
     * @return string
     * @throws CException
     */
    public function generateEtagSeed()
    {
        $params = (array)request()->getQuery('', []);

        return $this->getId() . $this->getAction()->id . serialize($params) . $this->generateLastModified();
    }
}
