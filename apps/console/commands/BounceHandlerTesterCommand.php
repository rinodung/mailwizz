<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * BounceHandlerTesterCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.6.6
 */

class BounceHandlerTesterCommand extends ConsoleCommand
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        parent::init();

        Yii::import('common.vendors.BounceHandler.*');
    }

    /**
     * @return int
     * @throws ReflectionException
     */
    public function actionIndex()
    {
        $this->stdout('Starting...');

        $bounceHandler = new BounceHandlerTester('__TEST__', '__TEST__', '__TEST__', [
            'deleteMessages'                => false,
            'deleteAllMessages'             => false,
            'processLimit'                  => 1000,
            'searchCharset'                 => app()->charset,
            'imapOpenParams'                => [],
            'processDaysBack'               => 10,
            'processOnlyFeedbackReports'    => false,
            'logger'                        => [$this, 'stdout'],
            'isValidResultCallback'         => [$this, 'isValidBounceHandlerResultCallback'],
        ]);

        $this->stdout('Fetching the results...');

        // fetch the results
        $results = $bounceHandler->getResults();

        $this->stdout(sprintf('Found %d results.', count($results)));

        // done
        if (empty($results)) {
            $this->stdout('No results!');
            return 0;
        }

        $hard = $soft = $internal = $fbl = 0;

        foreach ($results as $result) {
            $log = $this->getDeliveryLogFromBounceHandlerResult($result);
            if (empty($log)) {
                continue;
            }

            $this->stdout(sprintf('Processing campaign uid: %s and subscriber uid %s.', $log->campaign->campaign_uid, $log->subscriber->subscriber_uid));

            if (in_array($result['bounceType'], [BounceHandler::BOUNCE_SOFT, BounceHandler::BOUNCE_HARD])) {
                if ($result['bounceType'] == BounceHandler::BOUNCE_SOFT) {
                    $soft++;
                } else {
                    $hard++;
                }

                $this->stdout(sprintf('Subscriber uid: %s is %s bounced with the message: %s.', $log->subscriber->subscriber_uid, $result['bounceType'], $result['email']));
            } elseif ($result['bounceType'] == BounceHandler::FEEDBACK_LOOP_REPORT) {
                $fbl++;
                $_message = 'DELETED / UNSUB';

                $this->stdout(sprintf('Subscriber uid: %s is %s bounced with the message: %s.', $log->subscriber->subscriber_uid, (string)$result['bounceType'], (string)$_message));
            } elseif ($result['bounceType'] == BounceHandler::BOUNCE_INTERNAL) {
                $internal++;
                $this->stdout(sprintf('Subscriber uid: %s is %s bounced with the message: %s.', $log->subscriber->subscriber_uid, $result['bounceType'], $result['email']));
            }
        }

        $this->stdout(sprintf('Overall: %d hard / %d soft / %d internal / %d fbl', $hard, $soft, $internal, $fbl));


        return 0;
    }

    /**
     * @param array $result
     *
     * @return bool
     */
    public function isValidBounceHandlerResultCallback(array $result): bool
    {
        return $this->getDeliveryLogFromBounceHandlerResult($result) !== null;
    }

    /**
     * @param array $result
     *
     * @return CampaignDeliveryLog|null
     */
    public function getDeliveryLogFromBounceHandlerResult(array $result): ?CampaignDeliveryLog
    {
        if (!isset($result['originalEmailHeadersArray']) || !is_array($result['originalEmailHeadersArray'])) {
            return null;
        }

        $headers = array_reverse((array)$result['originalEmailHeadersArray']);
        foreach ($headers as $key => $value) {
            if (strtolower($key) !== 'message-id') {
                continue;
            }

            /** @var CampaignDeliveryLog|null $log */
            $log = CampaignDeliveryLog::model()->findByEmailMessageId($value);
            if (!empty($log)) {
                return $log;
            }
        }

        return null;
    }
}
