<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class QueueProcessorBackendEmailBlacklistDeleteAll implements Processor
{
    /**
     * @param Message $message
     * @param Context $context
     *
     * @return string
     * @throws CDbException
     */
    public function process(Message $message, Context $context)
    {
        // do not retry this message
        if ($message->isRedelivered()) {
            return self::ACK;
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'email_id, subscriber_id, email';
        $criteria->limit  = 1000;

        $models = EmailBlacklist::model()->findAll($criteria);
        while (!empty($models)) {
            foreach ($models as $model) {
                $model->delete();
            }
            $models = EmailBlacklist::model()->findAll($criteria);
        }

        $msg = new UserMessage();
        $msg->user_id = $message->getProperty('user_id');
        $msg->title   = 'Global email blacklist delete';
        $msg->message = 'Your request to delete all the records from the global email blacklist is now complete';
        $msg->save();

        return self::ACK;
    }
}
