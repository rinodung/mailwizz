<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use League\Flysystem\FileNotFoundException;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class QueueProcessorCustomerListsAllSubscribersFilterExportDelete implements Processor
{
    /**
     * @param Message $message
     * @param Context $context
     *
     * @return string
     * @throws FileNotFoundException
     */
    public function process(Message $message, Context $context)
    {
        // do not retry this message
        if ($message->isRedelivered()) {
            return self::ACK;
        }

        $fileName = $message->getProperty('fileName');
        if (!empty($fileName) && queue()->getStorage()->getFilesystem()->has($fileName)) {
            queue()->getStorage()->getFilesystem()->delete($fileName);
        }
        return self::ACK;
    }
}
