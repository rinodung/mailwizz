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

/** @var ArticleCategory $category */
$category = $controller->getData('category');

/** @var Article[] $articles */
$articles = $controller->getData('articles');

/** @var CPagination $pages */
$pages = $controller->getData('pages');

?>

<div class="row">
    <div class="col-lg-12 list-articles">
        <h1 class="page-heading">
            <?php echo html_encode((string)$category->name); ?> <small><?php echo t('articles', 'All articles filled in under this category'); ?></small>
        </h1>
        <hr />

        <?php if (!empty($articles)) {
    foreach ($articles as $article) {?>
        <div class="article">
                <div class="title"><?php echo CHtml::link(html_encode((string)$article->title), createUrl('articles/view', ['slug' => html_encode((string)$article->slug)]), ['title' => html_encode((string)$article->title)]); ?></div>
                <div class="excerpt"><?php echo html_encode((string)$article->getExcerpt(500)); ?></div>
                <div class="categories pull-right">
                    <?php
                    $controller->widget('frontend.components.web.widgets.article.ArticleCategoriesWidget', [
                        'article'   => $article,
                        'except'    => [$category->category_id],
                    ]);
                    ?>
                </div>
                <div class="clearfix"><!-- --></div>
            </div>
        <?php } ?>
            <hr />
            <div class="pull-right">
                <?php $controller->widget('CLinkPager', [
                    'pages'         => $pages,
                    'htmlOptions'   => ['class' => 'pagination'],
                    'header'        => false,
                    'cssFile'       => false,
                ]); ?>
            </div>
            <div class="clearfix"><!-- --></div>

        <?php
} else { ?>
            <h4><?php echo t('articles', 'We\'re sorry, but this category doesn\'t have any published article yet!'); ?></h4>
        <?php } ?>

    </div>
</div>
