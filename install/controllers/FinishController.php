<?php defined('MW_INSTALLER_PATH') or exit('No direct script access allowed');

/**
 * FinishController
 * 
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com> 
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */
 
class FinishController extends Controller
{
    public function actionIndex()
    {
        if (!getSession('cron')) {
            redirect('index.php?route=cron');
        }
        
        $this->data['pageHeading'] = 'Finish';
        $this->data['breadcrumbs'] = array(
            'Finish' => 'index.php?route=finish',
        );
        
        $this->render('finish');
    }
    
}