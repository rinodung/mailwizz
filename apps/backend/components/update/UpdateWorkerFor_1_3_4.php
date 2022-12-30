<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UpdateWorkerFor_1_3_4
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4
 */

class UpdateWorkerFor_1_3_4 extends UpdateWorkerAbstract
{
    public function run()
    {
        // run the sql from file
        // $this->runQueriesFromSqlFile('1.3.4');

        // update old layouts
        $models = ListPageType::model()->findAll();
        $searchReplace = [
            'panel panel-default'   => 'box box-primary borderless',
            'panel-heading'         => 'box-header',
            'panel-title'           => 'box-title',
            'panel-body'            => 'box-body',
            'callout'               => 'callout callout-info',
            'panel-footer'          => 'box-footer',
            'panel-'                => 'box-',
            '@import url(\'http://fonts.googleapis.com/css?family=Open+Sans\');'            => '',
            '@import url(\'http://fonts.googleapis.com/css?family=Noto+Sans:700italic\');'  => '',
            '#b94a48'               => '#367fa9',
            '\'Open Sans\','        => '',
            '\'Noto Sans\','        => '',
        ];
        foreach ($models as $model) {
            $model->content = str_replace(array_keys($searchReplace), array_values($searchReplace), $model->content);
            $model->save(false);
        }

        /** @var OptionEmailTemplate $optionEmailTemplate */
        $optionEmailTemplate = container()->get(OptionEmailTemplate::class);

        $common = str_replace('#b94a48', '#367fa9', $optionEmailTemplate->common);
        $optionEmailTemplate->saveAttributes(['common' => $common]);
        // end update
    }
}
