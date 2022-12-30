<?php defined('MW_INSTALLER_PATH') or exit('No direct script access allowed');

/**
 * This file is part of the MailWizz EMA application.
 * 
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com> 
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */
 
?>
<div class="callout callout-info">
    Thank you for purchasing MailWizz EMA.<br /> 
    Let's start installing the application on your server.
</div>

<form method="post">
    
    <div class="box box-primary borderless">
        
        <!-- section start -->
        <div class="box-header">
            <h3 class="box-title">License Information</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="required">I bought the license from: <span class="required">*</span></label>
                        <select data-placement="top" title="Marketplace" data-content="Please select the Marketplace from where you have purchased MailWizz. If you are not sure, select Envato." class="form-control has-help-text<?php echo $context->getError('market_place') ? ' error':'';?>" name="market_place">
                            <?php foreach ($marketPlaces as $marketPlace => $marketPlaceName) { ?>
                                <option value="<?php echo $marketPlace?>"<?php echo getPost('market_place', '') == $marketPlace ? ' selected="selected"':'';?>><?php echo $marketPlaceName;?></option>
                            <?php } ?>
                        </select>
                        <?php if ($error = $context->getError('market_place')) { ?>
                            <div class="errorMessage" style="display: block;"><?php echo $error;?></div>
                        <?php } ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="required">Purchase code <span class="required">*</span></label>
                        <input data-placement="top" title="Purchase code" data-content="Your purchase code is your license key which you have received after you bought MailWizz." placeholder="Your purchase code" class="form-control has-help-text<?php echo $context->getError('purchase_code') ? ' error':'';?>" name="purchase_code" type="text" value="<?php echo getPost('purchase_code', '');?>"/>
                        <?php if ($error = $context->getError('purchase_code')) { ?>
                            <div class="errorMessage" style="display: block;"><?php echo $error;?></div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- section end -->

        <!-- section start -->
        <div class="box-header">
            <h3 class="box-title">Site Information</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="required">Name <span class="required">*</span></label>
                        <input data-placement="top" title="Site name" data-content="The site name will be used in various areas of the application and emails, please use a unique and descriptive name between 2 and 20 characters." placeholder="Your site name" class="form-control has-help-text<?php echo $context->getError('site_name') ? ' error':'';?>" name="site_name" type="text" value="<?php echo getPost('site_name', '');?>"/>
					    <?php if ($error = $context->getError('site_name')) { ?>
                            <div class="errorMessage" style="display: block;"><?php echo $error;?></div>
					    <?php } ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="required">Tagline <span class="required">*</span></label>
                        <input data-placement="top" title="Site tagline" data-content="The site tagline will be used in various areas of the application and emails, please use a good tagline between 2 and 100 characters." placeholder="Your site tagline" class="form-control has-help-text<?php echo $context->getError('site_tagline') ? ' error':'';?>" name="site_tagline" type="text" value="<?php echo getPost('site_tagline', '');?>"/>
			            <?php if ($error = $context->getError('site_tagline')) { ?>
                            <div class="errorMessage" style="display: block;"><?php echo $error;?></div>
			            <?php } ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="required">Description <span class="required">*</span></label>
                        <input data-placement="top" title="Site description" data-content="The site description will be used in various areas of the application and emails, please use a description between 2 and 200 characters." placeholder="Your site description" class="form-control has-help-text<?php echo $context->getError('site_description') ? ' error':'';?>" name="site_description" type="text" value="<?php echo getPost('site_description', '');?>"/>
				        <?php if ($error = $context->getError('site_description')) { ?>
                            <div class="errorMessage" style="display: block;"><?php echo $error;?></div>
				        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- section end -->
        <div class="box-footer">
            <div class="pull-left">
                <div class="form-group">
                    <input name="terms_consent" type="checkbox" value="1"<?php echo getPost('terms_consent', '') ? ' checked' : '';?>/> I agree with the <a href="https://www.mailwizz.com/terms" target="_blank">Terms and Conditions</a>.
		            <?php if ($error = $context->getError('terms_consent')) { ?>
                        <div class="errorMessage" style="display: block;"><?php echo $error;?></div>
		            <?php } ?>
                </div>
            </div>
            <div class="pull-right">
                <button class="btn btn-primary btn-flat" value="1" name="next"><?php echo IconHelper::make('fa-arrow-circle-o-right');?> Next</button>
            </div>
            <div class="clearfix"><!-- --></div>        
        </div>
    </div>
</form>