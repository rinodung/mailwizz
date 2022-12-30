<?php declare(strict_types=1);

namespace Inboxroad\Test;

use Inboxroad\Api\Messages;
use Inboxroad\HttpClient\HttpClient;
use Inboxroad\Inboxroad;

/**
 * Class InboxroadTest
 * @package Inboxroad\Test
 */
class InboxroadTest extends Base
{
    /**
     * @var Inboxroad
     */
    private $inboxroad;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        
        $this->inboxroad = new Inboxroad(new HttpClient('DUMMY'));
    }

    /**
     * @return void
     */
    public function testMessages(): void
    {
        $this->assertInstanceOf(Messages::class, $this->inboxroad->messages());
    }
}
