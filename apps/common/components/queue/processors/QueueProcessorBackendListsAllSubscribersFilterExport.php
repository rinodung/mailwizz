<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use League\Csv\CannotInsertRecord;
use League\Flysystem\FileExistsException;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class QueueProcessorBackendListsAllSubscribersFilterExport implements Processor
{
    /**
     * @param Message $message
     * @param Context $context
     *
     * @return string
     * @throws CException
     * @throws CannotInsertRecord
     * @throws FileExistsException
     */
    public function process(Message $message, Context $context)
    {
        // do not retry this message
        if ($message->isRedelivered()) {
            return self::ACK;
        }

        $filter = new AllCustomersListsSubscribersFilters();

        $attributes = CMap::mergeArray($filter->getAttributes(), $message->getProperties());
        foreach ($attributes as $key => $value) {
            if ($filter->hasAttribute($key) || property_exists($filter, $key)) {
                $filter->$key = $value;
            }
        }

        $user = User::model()->findByPk((int)$filter->user_id);
        if (empty($user)) {
            return self::ACK;
        }

        $storage = (string)Yii::getPathOfAlias('common.runtime.list-export');
        if ((!file_exists($storage) || !is_dir($storage)) && !mkdir($storage)) {
            throw new Exception(sprintf('Please make sure the folder "%s" exists and is writable!', $storage));
        }
        $fileName = StringHelper::random(40) . '.csv';
        $file = $storage . '/' . $fileName;
        $csvWriter = League\Csv\Writer::createFromPath($file, 'w');
        $csvWriter->insertOne(['Email', 'Source', 'Ip address', 'Status']);

        /** @var ListSubscriber $subscriber */
        foreach ($filter->getSubscribers() as $subscriber) {
            try {
                $csvWriter->insertOne([
                    (string)$subscriber->getDisplayEmail(),
                    (string)$subscriber->source,
                    (string)$subscriber->ip_address,
                    (string)$subscriber->status,
                ]);
            } catch (Exception $e) {
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        }

        if (!($fileHandle = fopen($file, 'r'))) {
            throw new CException(sprintf('Unable to open the "%s" file for processing!', $file));
        }

        queue()->getStorage()->getFilesystem()->writeStream($fileName, $fileHandle);
        if (FileSystemHelper::isStreamResource($fileHandle)) {
            fclose($fileHandle);
        }
        unlink($file);

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $message = new UserMessage();
        $message->user_id = (int)$filter->user_id;
        $message->title   = 'Export done';
        $message->message = 'Your requested export is done, you can click {url} to download it! The download link is valid for 24 hours only!';
        $message->message_translation_params = [
            '{url}' => CHtml::link(t('app', 'here'), $optionUrl->getBackendUrl('download-queued/' . $fileName)),
        ];
        $message->save();

        $delay = (60 * 60 * 24);
        queue_send('backend.lists.allsubscribers.filter.export.delete', ['fileName' => $fileName], [], $delay * 1000);

        return self::ACK;
    }
}
