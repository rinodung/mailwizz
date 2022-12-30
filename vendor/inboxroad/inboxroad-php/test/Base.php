<?php declare(strict_types=1);

namespace Inboxroad\Test;

use ErrorException;
use PHPUnit\Framework\TestCase;

/**
 * Class Base
 * @package Inboxroad\Test
 */
class Base extends TestCase
{
    /**
     * @throws ErrorException
     */
    public function checkEnvironmentVariables(): void
    {
        $keys = [
            'INBOXROAD_API_KEY',
            'INBOXROAD_SEND_EMAIL_ENABLED',
            'INBOXROAD_SEND_EMAIL_FROM_EMAIL',
            'INBOXROAD_SEND_EMAIL_TO_EMAIL'
        ];

        foreach ($keys as $key) {
            if (!getenv($key)) {
                throw new ErrorException('Following environment variable is missing: ' . $key);
            }
        }
    }
}
