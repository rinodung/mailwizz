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
 * @since 2.1.11
 */

/** @var string $dataConfirmText */
/** @var string $favoritePageColorClass */
/** @var string $route */
/** @var string $serializedRouteParams */
/** @var string $label */
/** @var string $title */

?>

<div id="favorite-page">
    <div class="favorite-page-wrapper">
        <?php
            echo CHtml::tag('span', [
                'class'                    => 'favorite-page-toggle glyphicon glyphicon-bookmark ' . $favoritePageColorClass,
                'data-url'                 => createUrl('favorite_pages/add_remove'),
                'data-route'               => $route,
                'data-route_params'        => $serializedRouteParams,
                'data-label'               => $label,
                'data-current_color_class' => $favoritePageColorClass,
                'data-confirm'             => $dataConfirmText,
                'data-placement'           => 'right',
                'title'                    => $title,
            ]);
        ?>
    </div>
</div>
