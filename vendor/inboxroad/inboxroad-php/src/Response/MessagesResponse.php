<?php declare(strict_types=1);

namespace Inboxroad\Response;

/**
 * Class MessagesResponse
 * @package Inboxroad\Response
 */
class MessagesResponse extends Response
{
    /**
     * @return string
     */
    public function getMessageId(): string
    {
        $responseData = $this->getResponseData();
        if (empty($responseData['message_id'])) {
            return '';
        }
        return (string)str_replace(['<', '>'], '', (string)$responseData['message_id']);
    }
}
