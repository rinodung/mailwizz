<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

$packages = require YII_PATH . '/web/js/packages.php';
unset($packages['jquery']);

$packages = CMap::mergeArray([
    'jquery' => [
        'basePath'  => 'common.components.web.js.source',
        'js'        => [
            // @phpstan-ignore-next-line
            YII_DEBUG ? 'jquery.js' : 'jquery.min.js',
        ],
    ],
    'jquery-migrate' => [
        'basePath'  => 'common.components.web.js.source',
        'js'        => [
            // @phpstan-ignore-next-line
            YII_DEBUG ? 'jquery-migrate.js' : 'jquery-migrate.min.js',
        ],
        'depends'   => ['jquery'],
    ],
], $packages);

return $packages;
