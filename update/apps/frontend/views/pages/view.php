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
 * @since 1.5.5
 */

/** @var Controller $controller */
$controller = controller();

/** @var Page $page */
$page = $controller->getData('page');

?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-heading">
            <?php echo html_encode((string)$page->title); ?>
        </h1>
        <hr />
        <?php echo html_purify((string)$page->content); ?>
        <hr />
    </div>
</div>
