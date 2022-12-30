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

/** @var array $pageBreadcrumbs */
$pageBreadcrumbs = (array)$controller->getData('pageBreadcrumbs');

/** @var string $content */
$content = (string)$controller->getData('content');

/** @var Customer $customer */
$customer = customer()->getModel();

$parentAccountName = '';

if (is_subaccount()) {
    $parentAccountName = $customer->getFullName();

    /** @var Customer $customer */
    $customer = subaccount()->customer();
}

?>
<!DOCTYPE html>
<html dir="<?php echo html_encode((string)$controller->getHtmlOrientation()); ?>">
<head>
    <meta charset="<?php echo app()->charset; ?>">
    <title><?php echo html_encode($pageMetaTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo html_encode($pageMetaDescription); ?>">
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
                    <?php if (!is_subaccount()) { ?>
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
                    <?php } ?>
                    <li class="dropdown tasks-menu">
                        <a href="javascript:;" class="header-account-stats dropdown-toggle" data-url="<?php echo createUrl('account/usage'); ?>" data-toggle="dropdown" title="<?php echo t('customers', 'Account usage'); ?>">
                            <i class="fa fa-tasks"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="header"><?php echo t('customers', 'Account usage'); ?></li>
                            <li>
                                <ul class="menu">
                                    <li>
                                        <a href="#"><h3><?php echo t('app', 'Please wait, processing...'); ?></h3></a>
                                    </li>
                                </ul>
                            </li>
                            <li class="footer">
                                <a href="javascript:;" class="header-account-stats-refresh"><?php echo IconHelper::make('refresh') . t('app', 'Refresh'); ?></a>
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
                            <span><?php echo ($fullName = $customer->getFullName()) ? html_encode($fullName) : t('app', 'Welcome'); ?> <i class="caret"></i></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="user-header bg-light-blue">
                                <img src="<?php echo html_encode($customer->getAvatarUrl(90, 90)); ?>" class="img-circle"/>
                                <p>
                                    <?php echo ($fullName = $customer->getFullName()) ? html_encode($fullName) : t('app', 'Welcome'); ?>
                                    <?php if (!empty($parentAccountName)) {
    echo sprintf('<br /><small>%s: <br />%s</small>', t('app', 'Account holder'), html_encode($parentAccountName));
} ?>
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
                <?php if (!hooks()->hasActions('customer_layout_main_sidebar_user_panel')) { ?>
                    <div class="pull-left image">
                        <img src="<?php echo html_encode((string)$customer->getAvatarUrl(90, 90)); ?>" class="img-circle" />
                    </div>
                    <div class="pull-left info">
                        <p><?php echo ($fullName = $customer->getFullName()) ? html_encode($fullName) : t('app', 'Welcome'); ?></p>
                    </div>
                <?php } else {
    hooks()->doAction('customer_layout_main_sidebar_user_panel', $controller);
} ?>
            </div>

            <?php $controller->widget('customer.components.web.widgets.LeftSideNavigationWidget'); ?>
            <?php
            /** @var OptionCommon $common */
            $common = container()->get(OptionCommon::class);
            if ($common->getShowCustomerTimeInfo() && app_version_compare_to('1.3.4.4', '>=')) { ?>
                <div class="timeinfo">
                    <div class="pull-left"><?php echo t('app', 'Local time'); ?></div>
                    <div class="pull-right"><?php echo html_encode((string)$customer->dateTimeFormatter->formatDateTime()); ?></div>
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
            <?php echo $content; ?>
        </section>
    </div>
    <footer class="main-footer">
        <?php hooks()->doAction('layout_footer_html', $controller); ?>
        <div class="clearfix"><!-- --></div>
    </footer>
</div>
</body>
</html>
