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
 * @since 1.9.15
 */

/** @var Controller $controller */
$controller = controller();

?>

<footer class="main-footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <span class="copyright">©<?php echo date('Y'); ?> <?php echo t('app', 'All rights reserved.'); ?></span>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <?php
                if ($menus = Menu::findByZoneSlug(MenuZone::ZONE_FRONTEND_FOOTER)) {
                    foreach ($menus as $menu) {
                        $controller->widget('zii.widgets.CMenu', ['items' => $menu->getItemsForMenu(), 'htmlOptions'   => ['class' => 'links']]);
                    }
                } else { ?>
                    <ul class="links">
                        <?php if ($page = Page::findBySlug('terms-and-conditions')) { ?>
                            <li><a href="<?php echo html_encode((string)$page->getPermalink()); ?>" title="<?php echo html_encode((string)$page->title); ?>"><?php echo html_encode((string)$page->title); ?></a></li>
                        <?php } ?>
                        <?php if ($page = Page::findBySlug('privacy-policy')) { ?>
                            <li><a href="<?php echo html_encode((string)$page->getPermalink()); ?>" title="<?php echo html_encode((string)$page->title); ?>"><?php echo html_encode((string)$page->title); ?></a></li>
                        <?php } ?>
                        <li><a href="<?php echo createUrl('articles/index'); ?>" title="<?php echo t('app', 'Articles'); ?>"><?php echo t('app', 'Articles'); ?></a></li>
                        <li><a href="<?php echo createUrl('lists/block_address'); ?>" title="<?php echo t('app', 'Block my email'); ?>"><?php echo t('app', 'Block my email'); ?></a></li>
                    </ul>
                <?php } ?>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <ul class="social">
					<?php foreach (['facebook', 'twitter', 'linkedin', 'instagram', 'youtube'] as $item) {
                    if (!($url = options()->get('system.social_links.' . $item, ''))) {
                        continue;
                    } ?>
                        <li>
                            <a href="<?php echo html_encode((string)$url); ?>" title="<?php echo ucfirst(html_encode((string)$item)); ?>" target="_blank">
                                <i class="fa fa-<?php echo html_encode((string)$item); ?>"></i>
                            </a>
                        </li>
						<?php
                } ?>
                </ul>
            </div>
        </div>
    </div>
	<?php hooks()->doAction('layout_footer_html', $controller); ?>
</footer>
