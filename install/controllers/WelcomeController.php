<?php defined('MW_INSTALLER_PATH') or exit('No direct script access allowed');

/**
 * WelcomeController
 * 
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com> 
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */
 
class WelcomeController extends Controller
{
    public function actionIndex()
    {
        // start clean
        $_SESSION = array();
        
        $this->validateRequest();
        
        if (getSession('welcome')) {
            redirect('index.php?route=requirements');
        }
        
        $this->data['marketPlaces'] = $this->getMarketPlaces();
        
        $this->data['pageHeading'] = 'Welcome';
        $this->data['breadcrumbs'] = array(
            'Welcome' => 'index.php?route=welcome',
        );
        
        $this->render('welcome');
    }
    
    protected function validateRequest()
    {
        if (!getPost('next')) {
            return;
        }
        
        $marketPlace  = getPost('market_place');
        $purchaseCode = getPost('purchase_code');
		
		$siteName        = getPost('site_name');
		$siteTagline     = getPost('site_tagline');
		$siteDescription = getPost('site_description');
		
        $termsConsent = getPost('terms_consent');
        
        if (empty($marketPlace)) {
            $this->addError('market_place', 'Please enter the market place from where you have bought the license!');
        }
        
        if (empty($purchaseCode)) {
            $this->addError('purchase_code', 'Please enter the purchase code!');
        }

	    if (empty($siteName)) {
		    $this->addError('site_name', 'Please enter the site name!');
	    } elseif (strlen($siteName) < 2 || strlen($siteName) > 20) {
		    $this->addError('site_name', 'The site name length must be between 2 and 20 characters!');
	    } elseif (stripos($siteName, 'mailwizz') !== false || stripos($siteName, 'mail wizz') !== false) {
		    $this->addError('site_name', 'The site name cannot contain the word "mailwizz"!');
	    }

	    if (empty($siteTagline)) {
		    $this->addError('site_tagline', 'Please enter the site tagline!');
	    } elseif (strlen($siteTagline) < 2 || strlen($siteTagline) > 100) {
		    $this->addError('site_tagline', 'The site tagline length must be between 2 and 100 characters!');
	    } elseif (stripos($siteTagline, 'mailwizz') !== false || stripos($siteTagline, 'mail wizz') !== false) {
		    $this->addError('site_tagline', 'The site tagline cannot contain the word "mailwizz"!');
	    }

	    if (empty($siteDescription)) {
		    $this->addError('site_description', 'Please enter the site description!');
	    } elseif (strlen($siteDescription) < 2 || strlen($siteDescription) > 200) {
		    $this->addError('site_description', 'The site description length must be between 2 and 200 characters!');
	    } elseif (stripos($siteDescription, 'mailwizz') !== false || stripos($siteDescription, 'mail wizz') !== false) {
		    $this->addError('site_description', 'The site description cannot contain the word "mailwizz"!');
	    }
        
        if (empty($termsConsent)) {
            $this->addError('terms_consent', 'You have to agree with our Terms and Conditions in order to proceed!');
        }
        
        if ($this->hasErrors()) {
            $this->addError('general', 'Your form has a few errors, please fix them and try again!');
	        return;
        }
        
        // license check.
		$licenseData = array(
			'first_name' => 'PROWEBBER',
			'last_name' => 'SCRIPTZ',
			'email' => 'mailwizz@pw.com',
			'market_place' => 'envato',
			'purchase_code' => 'prowebber.ru xxxxxxxx',
		);
        
        setSession('license_data', $licenseData);
		setSession('site_data', [
			'site_name'         => $siteName,
			'site_tagline'      => $siteTagline,
			'site_description'  => $siteDescription,
		]);
        setSession('welcome', 1);
    }
    
    public function getMarketPlaces()
    {
        return array(
            'envato'    => 'Envato',
            'mailwizz'  => 'MailWizz',
        );
    }

}