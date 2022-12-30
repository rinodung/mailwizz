<?php declare(strict_types=1);
/** @var Controller $controller */
$controller = controller();

/** @var string $pageMetaTitle */
$pageMetaTitle = (string)$controller->getData('pageMetaTitle');

/** @var string $pageMetaDescription */
$pageMetaDescription = (string)$controller->getData('pageMetaDescription');

/** @var string $content */
$content = (string)$controller->getData('content');

/** @var array $attributes */
$attributes = (array)$controller->getData('attributes');
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
    <body class="<?php echo html_encode((string)$controller->getBodyClasses()); ?>" style="width: <?php echo html_encode((string)$attributes['width']); ?>; height: <?php echo html_encode((string)$attributes['height']); ?>;">
    <?php $controller->getAfterOpeningBodyTag(); ?>
        <div class="container-fluid">
            <div class="row">
                <div id="notify-container">
                    <?php echo notify()->show(); ?>
                </div>
                <div class="col-lg-12">
                    <?php echo (string)$content; ?>
                </div>
            </div>
        </div>
    </body>
</html>
