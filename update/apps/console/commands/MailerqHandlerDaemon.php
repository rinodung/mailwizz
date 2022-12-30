<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * MailerqHandlerDaemon
 *
 * This is a daemon NOT a regular cron job command.
 * Please make sure it is not added in cron jobs nor it runs in more instances.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.9
 */

class MailerqHandlerDaemon extends ConsoleCommand
{
    /**
     * @var int
     */
    public $verbose = 0;

    /**
     * @var string
     */
    public $queue = 'results';

    /**
     * @return int
     * @throws CException
     */
    public function actionIndex()
    {
        $funcs = ['pcntl_fork', 'pcntl_waitpid'];
        foreach ($funcs as $func) {
            if (!CommonHelper::functionExists($func)) {
                $this->stdout(sprintf('You need to have the "%s" function (part of pcntl extension) enabled!', $func));
                return 1;
            }
        }

        $loadedServers = [];
        // @phpstan-ignore-next-line
        while (true) {
            $this->stdout('In while loop...');

            /** @var array $servers */
            $servers = (array)DeliveryServer::model()->findAll([
                'select'    => 'server_id',
                'condition' => 'type = "mailerq-web-api"',
            ]);

            $this->stdout('Looking for servers and found ' . count($servers) . ' servers.');

            foreach ($servers as $index => $server) {
                if (isset($loadedServers[$server->server_id])) {
                    unset($servers[$index]);
                } else {
                    $loadedServers[$server->server_id] = true;
                }
            }

            if (empty($servers)) {
                $this->stdout('Seems there is no valid server...');
                sleep(60);
                continue;
            }

            // close the external connections
            $this->setExternalConnectionsActive(false);

            $children = [];
            foreach ($servers as $server) {
                $this->stdout(sprintf('Forking a new process for server id %d!', $server->server_id));

                $pid = pcntl_fork();
                if ($pid == -1) {
                    continue;
                }

                // Parent
                if ($pid) {
                    $children[] = $pid;
                }

                // Child
                if (!$pid) {
                    $this->_handleServer($server->server_id);
                    app()->end();
                }
            }

            while (count($children) > 0) {
                foreach ($children as $key => $pid) {
                    $res = pcntl_waitpid($pid, $status, WNOHANG);
                    if ($res == -1 || $res > 0) {
                        unset($children[$key]);
                    }
                }
                sleep(1);
            }

            sleep(10);
        }
    }

    /**
     * @param int $serverId
     *
     * @return void
     * @throws CException
     */
    public function _handleServer(int $serverId)
    {
        // open the external connections
        $this->setExternalConnectionsActive();

        $server = DeliveryServerMailerqWebApi::model()->findByPk($serverId);
        if (empty($server)) {
            $this->stdout('Could not find the server anymore: ' . $serverId);
            return;
        }

        $this->stdout(sprintf('Processing server id %d!', $server->server_id));

        try {
            $channel = $server->getConnection()->channel();
            $channel->queue_declare($this->queue, false, true, false, false);
            $channel->basic_consume($this->queue, '', false, true, false, false, [$this, '_process']);
            while ((is_countable($channel->callbacks) ? count($channel->callbacks) : 0)) {
                $channel->wait();
            }
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            $this->stdout($e->getMessage());
        }
    }

    /**
     * @param mixed $msg
     *
     * @return void
     * @throws CException
     */
    public function _process($msg)
    {
        // @phpstan-ignore-next-line
        $results = json_decode($msg->body);
        if (empty($results)) {
            $this->stdout('$msg->body is empty.');
            return;
        }

        $this->stdout('$msg->body contains ' . (is_countable($results) ? count($results) : 0) . ' results.');

        $campaigns = $subscribers = [];

        if (!is_array($results) && is_object($results)) {
            $results = [$results];
        }

        foreach ((array)$results as $result) {
            if (empty($result->results) || !isset($result->campaign_uid, $result->subscriber_uid)) {
                $this->stdout('Result is not valid: ' . json_encode($result));
                continue;
            }

            $res = end($result->results);

            if ($this->verbose) {
                $this->stdout(json_encode($res));
            }

            if (!in_array($res->type, ['success', 'error'])) {
                $this->stdout('The result does not need processing.');
                continue;
            }

            if (!array_key_exists($result->campaign_uid, $campaigns)) {
                $campaigns[$result->campaign_uid] = Campaign::model()->findByAttributes([
                    'campaign_uid' => $result->campaign_uid,
                ]);
            }
            if (empty($campaigns[$result->campaign_uid])) {
                $this->stdout('Cannot find campaign: ' . $result->campaign_uid);
                continue;
            }

            if (!array_key_exists($result->subscriber_uid, $subscribers)) {
                $subscribers[$result->subscriber_uid] = ListSubscriber::model()->findByAttributes([
                    'subscriber_uid' => $result->subscriber_uid,
                ]);
            }
            if (empty($subscribers[$result->subscriber_uid])) {
                $this->stdout('Cannot find subscriber: ' . $result->subscriber_uid);
                continue;
            }

            $campaign   = $campaigns[$result->campaign_uid];
            $subscriber = $subscribers[$result->subscriber_uid];

            if ($res->type == 'error' && $res->fatal) {
                $bounceLog = CampaignBounceLog::model()->findByAttributes([
                    'campaign_id'   => $campaign->campaign_id,
                    'subscriber_id' => $subscriber->subscriber_id,
                ]);

                if (!empty($bounceLog)) {
                    $this->stdout('Result processed successfully, bounce log has been found already!');
                    continue;
                }

                $bounceLog = new CampaignBounceLog();
                $bounceLog->campaign_id   = (int)$campaign->campaign_id;
                $bounceLog->subscriber_id = (int)$subscriber->subscriber_id;
                $bounceLog->message       = $res->description;
                $bounceLog->bounce_type   = CampaignBounceLog::BOUNCE_HARD;
                $bounceLog->save();

                $subscriber->addToBlacklist($bounceLog->message);
                continue;
            }

            if ($res->type == 'success') {
                $deliveryLog = db()->createCommand()
                    ->select('log_id, delivery_confirmed')
                    ->from('{{campaign_delivery_log}}')
                    ->where('campaign_id = :cid AND subscriber_id = :sid', [
                        ':cid' => (int)$campaign->campaign_id,
                        ':sid' => (int)$subscriber->subscriber_id,
                    ])
                    ->queryRow();

                if (!empty($deliveryLog) && $deliveryLog['delivery_confirmed'] == DeliveryServer::TEXT_NO) {
                    db()->createCommand()->update(
                        '{{campaign_delivery_log}}',
                        [
                                'delivery_confirmed' => CampaignDeliveryLog::TEXT_YES,
                            ],
                        'log_id = :lid',
                        [
                                ':lid' => $deliveryLog['log_id'],
                            ]
                    );
                }
            }


            $this->stdout('Result processed successfully.');
        }

        unset($campaigns, $subscribers, $results);
    }
}
