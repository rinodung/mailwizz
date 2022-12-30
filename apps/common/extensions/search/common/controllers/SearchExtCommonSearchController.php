<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class SearchExtCommonSearchController extends ExtensionController
{
    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->getExtension()->getPathOfAlias('common.views');
    }

    /**
     * @return void
     * @throws CException
     * @throws ReflectionException
     */
    public function actionIndex()
    {
        /**
         * Allow only ajax requests here
         */
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
        }

        $search = new SearchExtSearch();
        $search->term = (string)request()->getQuery('term');

        $this->renderPartial('search-results', [
            'results' => $search->getResults(),
        ]);
    }
}
