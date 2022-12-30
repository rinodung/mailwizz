<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerWarmupPlanGraphWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.10
 */

class DeliveryServerWarmupPlanGraphWidget extends CWidget
{
    /**
     * @var DeliveryServerWarmupPlan|null
     */
    public $plan;

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        if (empty($this->plan) || empty($this->plan->schedules)) {
            return;
        }

        $chartData = [];
        $data      = [];
        foreach ($this->plan->schedules as $index => $schedule) {
            $data[] = [$index, (int)$schedule->getPlanQuota()];
        }

        $chartData[] = [
            'label' => '&nbsp;' . t('warmup_plans', 'Quota'),
            'data'  => $data,
        ];

        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.categories.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/delivery-server-warmup-plan-graph.js'));

        $this->render('delivery-server-warmup-plan-graph', compact('chartData'));
    }
}
