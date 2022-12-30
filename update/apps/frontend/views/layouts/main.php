<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

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

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var string $pageMetaTitle */
$pageMetaTitle = (string)$controller->getData('pageMetaTitle');

/** @var string $pageMetaDescription */
$pageMetaDescription = (string)$controller->getData('pageMetaDescription');

/** @var string $content */
$content = (string)$controller->getData('content');
?>
<!DOCTYPE html>
<html dir="<?php echo html_encode((string)$controller->getHtmlOrientation()); ?>">
<head>
    <meta charset="<?php echo app()->charset; ?>">
    <title><?php echo html_encode((string)$pageMetaTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo html_encode((string)$pageMetaDescription); ?>">
    <!--[if lt IE 9]>
    <script src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="//oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
</head>
<body class="<?php echo html_encode((string)$controller->getBodyClasses()); ?>">
    <?php $controller->getAfterOpeningBodyTag(); ?>
    <div class="wrapper">

	    <?php
            try {
                $controller->widget('frontend.components.web.widgets.HeaderWidget');
            } catch (Exception $e) {
            }
        ?>
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="row row-large">
                    <div class="container-fluid-large col-lg-10 col-lg-push-1 col-md-10 col-md-push-1 col-sm-12 col-xs-12">
                        <div id="notify-container">
                            <?php echo notify()->show(); ?>
                        </div>
                        <?php echo (string)$content; ?>
                    </div>
                </div>
            </div>
        </div>
	    <?php
            try {
                $controller->widget('frontend.components.web.widgets.FooterWidget');
            } catch (Exception $e) {
            }
        ?>
    </div>
</body>
</html>
