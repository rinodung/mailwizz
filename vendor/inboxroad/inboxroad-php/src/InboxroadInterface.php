<?php declare(strict_types=1);

namespace Inboxroad;

use Inboxroad\Api\MessagesInterface;

/**
 * Interface InboxroadInterface
 * @package Inboxroad
 */
interface InboxroadInterface
{
    /**
     * @return MessagesInterface
     */
    public function messages(): MessagesInterface;
}
