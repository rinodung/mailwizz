<?php declare(strict_types=1);

namespace Inboxroad\Test\Api;

use ErrorException;
use Inboxroad\Api\Messages;
use Inboxroad\HttpClient\HttpClient;
use Inboxroad\Models\Message;
use Inboxroad\Models\MessageHeader;
use Inboxroad\Models\MessageHeaderCollection;
use Inboxroad\Response\MessagesResponse;
use Inboxroad\Test\Base;

/**
 * Class MessagesTest
 * @package Inboxroad\Test\Api
 */
class MessagesTest extends Base
{
    /**
     * @throws ErrorException
     */
    public function setUp(): void
    {
        parent::setUp();
        
        if (!getenv('INBOXROAD_SEND_EMAIL_ENABLED')) {
            $this->markTestSkipped('Test skipped because INBOXROAD_SEND_EMAIL_ENABLED = 0');
        }

        $this->checkEnvironmentVariables();
    }

    /**
     * @return void
     * @throws ErrorException
     * @throws \Inboxroad\Exception\RequestException
     */
    public function testSend(): void
    {
        $message = (new Message())
            ->setFromEmail((string)getenv('INBOXROAD_SEND_EMAIL_FROM_EMAIL'))
            ->setToEmail((string)getenv('INBOXROAD_SEND_EMAIL_TO_EMAIL'))
            ->setToName('Inboxroad API Test')
            ->setReplyToEmail((string)getenv('INBOXROAD_SEND_EMAIL_FROM_EMAIL'))
            ->setSubject('Testing')
            ->setText('Testing...')
            ->setHtml('<strong>Testing...</strong>')
            ->setHeaders((new MessageHeaderCollection())->add(new MessageHeader('X-Mailer', 'Inboxroad-PHP')));
        
        $messages = new Messages(new HttpClient((string)getenv('INBOXROAD_API_KEY')));
        
        $result = $messages->send($message);
        $this->assertInstanceOf(MessagesResponse::class, $result);
        $this->assertIsString($result->getMessageId());
        $this->assertNotEmpty($result->getMessageId());
    }
}
