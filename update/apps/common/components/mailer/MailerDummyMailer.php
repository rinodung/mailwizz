<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * MailerDummyMailer
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.2
 */

class MailerDummyMailer extends MailerAbstract
{
    /**
     * @inheritDoc
     */
    public function send(array $params = []): bool
    {
        $this->reset();
        $this->addLog('OK');

        $this->_messageId = StringHelper::randomSha1();
        $this->_sentCounter++;

        $this->reset(false);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getEmailMessage(array $params = []): string
    {
        return StringHelper::random(rand(0, 1000));
    }

    /**
     * @inheritDoc
     */
    public function reset(bool $resetLogs = true)
    {
        if ($resetLogs) {
            $this->clearLogs();
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'DummyMailer';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return t('mailer', 'System testing mailer, only simulate sending.');
    }
}
