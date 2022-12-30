<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyResponders7DaysActivityWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

class SurveyResponders7DaysActivityWidget extends CWidget
{
    /**
     * @var Survey
     */
    public $survey;

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        $survey = $this->survey;

        if ($survey->customer->getGroupOption('surveys.show_7days_responders_activity_graph', 'yes') != 'yes') {
            return;
        }

        $cacheKey = sha1(__METHOD__ . $survey->survey_id . date('H') . 'v2');
        if (($chartData = cache()->get($cacheKey)) === false) {
            $chartData = [
                'responders' => [
                    'label' => '&nbsp;' . t('survey_responders', 'Responders'),
                    'data'  => [],
                ],
            ];

            for ($i = 0; $i < 7; $i++) {
                $timestamp = (int)strtotime(sprintf('-%d days', $i));

                // responders
                $count = SurveyResponder::model()->count([
                    'condition' => 'survey_id = :lid AND status = :st AND DATE(date_added) = :date',
                    'params'    => [
                        ':lid'  => $survey->survey_id,
                        ':st'   => SurveyResponder::STATUS_ACTIVE,
                        ':date' => date('Y-m-d', $timestamp),
                    ],
                ]);
                $chartData['responders']['data'][] = [$timestamp * 1000, (int)$count];
            }

            $chartData = array_values($chartData);
            cache()->set($cacheKey, $chartData, 3600);
        }

        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.resize.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.crosshair.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.time.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/strftime/strftime-min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/survey-responders-7days-activity.js'));

        $this->render('7days-activity', compact('chartData'));
    }
}
