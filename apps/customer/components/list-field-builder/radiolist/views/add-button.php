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
 * @since 1.4.4
 */

?>


<a href="javascript:;" class="btn btn-primary btn-flat btn-add-radiolist-field">
    <?php echo IconHelper::make('create') . '&nbsp;' . t('list_fields', 'Add radiolist field'); ?>
</a>