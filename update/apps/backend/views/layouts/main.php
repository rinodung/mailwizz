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

/** @var string $pageMetaTitle */
$pageMetaTitle = (string)$controller->getData('pageMetaTitle');

/** @var string $pageMetaDescription */
$pageMetaDescription = (string)$controller->getData('pageMetaDescription');

/** @var array $pageBreadcrumbs */
$pageBreadcrumbs = (array)$controller->getData('pageBreadcrumbs');

/** @var string $content */
$content = (string)$controller->getData('content');

/** @var User $user */
$user = user()->getModel();

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
    <header class="main-header">
        <nav class="navbar navbar-static-top">
            <!-- Sidebar toggle button-->
            <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                <span class="sr-only">Toggle navigation</span>
            </a>
            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav">
					<?php hooks()->doAction('layout_top_navbar_menu_items_start', $controller); ?>
                    <li class="dropdown messages-menu">
                        <a href="javascript:;" class="header-messages dropdown-toggle" data-url="<?php echo createUrl('messages/header'); ?>" data-toggle="dropdown" title="<?php echo t('customers', 'Messages'); ?>">
                            <i class="fa fa-envelope"></i>
                            <span class="label label-success"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="header"> <!----> </li>
                            <li>
                                <ul class="menu">
                                    <li></li>
                                </ul>
                            </li>
                            <li class="footer">
                                <a href="<?php echo createUrl('messages/index'); ?>"><?php echo t('messages', 'See all messages'); ?></a>
                            </li>
                        </ul>
                    </li>
                    <li class="dropdown skin-mode-menu">
                        <a href="javascript:" class="skin-dark-mode-toggle" title="<?php echo t('app', 'Toggle dark mode'); ?>">
                            <i class="fa fa-adjust"></i>
                        </a>
                    </li>
                    <li class="dropdown user user-menu">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="glyphicon glyphicon-user"></i>
                            <span><?php echo ($fullName = $user->getFullName()) ? html_encode((string)$fullName) : t('app', 'Welcome'); ?><i class="caret"></i></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="user-header bg-light-blue">
                                <img src="<?php echo html_encode((string)$user->getAvatarUrl(90, 90)); ?>" class="img-circle"/>
                                <p>
									<?php echo ($fullName = $user->getFullName()) ? html_encode((string)$fullName) : t('app', 'Welcome'); ?>
                                </p>
                            </li>
                            <li class="user-footer">
                                <div class="pull-left">
                                    <a href="<?php echo createUrl('account/index'); ?>" class="btn btn-default btn-flat"><?php echo t('app', 'My Account'); ?></a>
                                </div>
                                <div class="pull-right">
                                    <a href="<?php echo createUrl('account/logout'); ?>" class="btn btn-default btn-flat"><?php echo t('app', 'Logout'); ?></a>
                                </div>
                            </li>
                        </ul>
                    </li>
					<?php hooks()->doAction('layout_top_navbar_menu_items_end', $controller); ?>
                </ul>
            </div>

        </nav>
    </header>
    <aside class="main-sidebar">
        <section class="sidebar">

            <div class="user-panel">
				<?php if (!hooks()->hasActions('backend_layout_main_sidebar_user_panel')) { ?>
                    <div class="pull-left image">
                        <img src="<?php echo html_encode((string)$user->getAvatarUrl(90, 90)); ?>" class="img-circle" />
                    </div>
                    <div class="pull-left info">
                        <p><?php echo ($fullName = $user->getFullName()) ? html_encode((string)$fullName) : t('app', 'Welcome'); ?></p>
                    </div>
				<?php } else {
    hooks()->doAction('backend_layout_main_sidebar_user_panel', $controller);
} ?>
            </div>

			<?php $controller->widget('backend.components.web.widgets.LeftSideNavigationWidget'); ?>
			<?php
            /** @var OptionCommon $common */
            $common = container()->get(OptionCommon::class);
            if ($common->getShowBackendTimeInfo()) { ?>
                <div class="timeinfo">
                    <div class="pull-left"><?php echo t('app', 'Local time'); ?></div>
                    <div class="pull-right"><?php echo html_encode((string)$user->dateTimeFormatter->formatDateTime()); ?></div>
                    <div class="clearfix"><!-- --></div>
                    <div class="pull-left"><?php echo t('app', 'System time'); ?></div>
                    <div class="pull-right"><?php echo date('Y-m-d H:i:s'); ?></div>
                    <div class="clearfix"><!-- --></div>
                </div>
			<?php } ?>
        </section>
        <!-- /.sidebar -->
    </aside>
    <div class="content-wrapper">
        <section class="content-header">
            <h1><?php echo !empty($pageHeading) ? $pageHeading : '&nbsp;'; ?></h1>
			<?php
            $controller->widget('zii.widgets.CBreadcrumbs', [
                'tagName'               => 'ol',
                'separator'             => '',
                'htmlOptions'           => ['class' => 'breadcrumb'],
                'activeLinkTemplate'    => '<li><a href="{url}">{label}</a>  <span class="divider"></span></li>',
                'inactiveLinkTemplate'  => '<li class="active">{label} </li>',
                'homeLink'              => CHtml::tag('li', [], CHtml::link(t('app', 'Dashboard'), createUrl('dashboard/index')) . '<span class="divider"></span>'),
                'links'                 => hooks()->applyFilters('layout_page_breadcrumbs', $pageBreadcrumbs),
            ]);
            ?>
        </section>
        <section class="content">
            <div id="notify-container">
				<?php echo notify()->show(); ?>
            </div>
            <div class="pull-left">
                <?php
                $controller->widget('common.components.web.widgets.FavoritePageWidget', [
                    'label' => !empty($pageHeading) ? $pageHeading : '',
                ]);
                ?>
            </div>
			<?php echo (string)$content; ?>
        </section>
    </div>
    <footer class="main-footer">
		<?php hooks()->doAction('layout_footer_html', $controller); ?>
        <div class="pull-right no-print">
			<?php echo t('app', 'Processed by version {version} in {seconds} seconds using {memory} mb of memory', [
                '{version}' => MW_VERSION,
                '{seconds}' => round(Yii::getLogger()->getExecutionTime(), 3),
                '{memory}'  => round(Yii::getLogger()->getMemoryUsage() / 1024 / 1024, 3),
            ]); ?>
        </div>
        <div class="clearfix"><!-- --></div>
    </footer>

</div>
</body>
</html>
