<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/** @var ExtensionController $controller */
$controller = controller();

/** @var string $pageMetaTitle */
$pageMetaTitle = (string)$controller->getData('pageMetaTitle');

/** @var string $pageMetaDescription */
$pageMetaDescription = (string)$controller->getData('pageMetaDescription');

/** @var string $assetsUrl */
$assetsUrl = (string)$controller->getData('assetsUrl');

/** @var string $language */
$language = (string)$controller->getData('language');

/** @var string $connectorUrl */
$connectorUrl = (string)$controller->getData('connectorUrl');

/** @var string $theme */
$theme = (string)$controller->getData('theme');

?>
<!DOCTYPE html>
<html>
	<head>
        <meta charset="<?php echo app()->charset; ?>">
        <title><?php echo html_encode($pageMetaTitle); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="<?php echo html_encode($pageMetaDescription); ?>">

		<link rel="stylesheet" type="text/css" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
		<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>

        <link rel="stylesheet" type="text/css" media="screen" href="<?php echo $assetsUrl; ?>/elfinder/css/elfinder.min.css">
        <link rel="stylesheet" type="text/css" media="screen" href="<?php echo $assetsUrl; ?>/elfinder/css/theme.css">

		<!-- Mono Theme -->
		<?php if (!empty($theme)) { ?>
		<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $theme; ?>">
		<?php } ?>

        <script type="text/javascript" src="<?php echo $assetsUrl; ?>/elfinder/js/elfinder.min.js"></script>
        <?php if ($language) { ?>
        <script type="text/javascript" src="<?php echo $assetsUrl; ?>/elfinder/js/i18n/elfinder.<?php echo $language; ?>.js"></script>
        <?php } ?>

        <script type="text/javascript" charset="utf-8">
        function getUrlParam(paramName) {
            var reParam = new RegExp('(?:[\?&]|&amp;)' + paramName + '=([^&]+)', 'i') ;
            var match = window.location.search.match(reParam) ;
            return (match && match.length > 1) ? match[1] : '' ;
        }

        var customData = {};
        <?php if (request()->enableCsrfValidation) { ?>
        customData['<?php echo request()->csrfTokenName; ?>'] = '<?php echo request()->getCsrfToken(); ?>';
        <?php } ?>

        </script>

        <?php
        // 1.4.4
        hooks()->doAction('ext_ckeditor_elfinder_filemanager_view_html_head');
        ?>

        <script type="text/javascript" charset="utf-8">
        $().ready(function() {
            var elf = $('#elfinder').elfinder({
                url : '<?php echo $connectorUrl; ?>',
                lang: '<?php echo !empty($language) ? $language : 'en'; ?>',
                customData: customData,
                getFileCallback : function(file) {

                    var options = {
                        file: file,
                        customData: customData,
                        canContinue: true
                    };

                    $('#elfinder').trigger('ext.ckeditor.elfinder.js_options.get_file_callback.start', [ options ]);
                    
                    if (options.canContinue && window.opener && window.opener.CKEDITOR) {
                        var funcNum = getUrlParam('CKEditorFuncNum');
                        window.opener.CKEDITOR.tools.callFunction(funcNum, file.url);
                        window.close();
                    }
                },
                resizable: false
            }).elfinder('instance');
        });
        </script>
	</head>
	<body>
        <div id="elfinder"></div>
	</body>
</html>
