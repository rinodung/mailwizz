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

/** @var string $content */
$content = (string)$controller->getData('content');

/** @var array $attributes */
$attributes = (array)$controller->getData('attributes');

// since 1.3.5.6
$htmlOptions = [];
if (!empty($attributes) && !empty($attributes['target']) && in_array($attributes['target'], ['_blank'])) {
    $htmlOptions['target'] = $attributes['target'];
}
?>

<div class="row">
    <div class="<?php echo (string)$controller->layout != 'embed' ? 'col-lg-6 col-lg-push-3 col-md-6 col-md-push-3 col-sm-12' : ''; ?>">
        <?php echo CHtml::form('', 'post', $htmlOptions); ?>
        <?php echo (string)$content; ?>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>
