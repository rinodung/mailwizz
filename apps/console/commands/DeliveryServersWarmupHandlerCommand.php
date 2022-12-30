<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServersWarmupHandlerCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.10
 */

class DeliveryServersWarmupHandlerCommand extends ConsoleCommand
{
    /**
     * @return int
     */
    public function actionIndex()
    {
        // set the lock name
        $lockName = sha1(__METHOD__);

        if (!mutex()->acquire($lockName, 5)) {
            return 0;
        }

        $result = 0;

        $this->stdout('Started processing warmup plans for delivery servers');

        try {
            hooks()->doAction('console_command_delivery_servers_warmup_handler_before_process', $this);

            $result = $this->process();

            hooks()->doAction('console_command_delivery_servers_warmup_handler_after_process', $this);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        $this->stdout('Done processing warmup plans for delivery servers');

        mutex()->release($lockName);

        return $result;
    }

    /**
     * @return int
     * @throws CDbException
     * @throws Exception
     */
    public function process()
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('warmup_plan_id IS NOT NULL');
        $criteria->addNotInCondition('status', [
            DeliveryServer::STATUS_PENDING_DELETE, DeliveryServer::STATUS_INACTIVE, DeliveryServer::STATUS_DISABLED,
        ]);
        $criteria->order = 'server_id ASC';

        /** @var DeliveryServer[] $servers */
        $servers = DeliveryServer::model()->findAll($criteria);
        foreach ($servers as $server) {
            DeliveryServerWarmupPlanHelper::handleServerWarmupPlanScheduleLogs($server, [$this, 'stdout']);
        }

        return 0;
    }
}
