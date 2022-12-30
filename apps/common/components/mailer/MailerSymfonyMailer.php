<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Symfony\Component\Mailer\Exception\TransportExceptionInterface as SymfonyMailerTransportExceptionInterface;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport\Dsn as SymfonyMailerDsn;
use Symfony\Component\Mailer\Transport\SendmailTransportFactory as SymfonyMailerSendmailTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory as SymfonyMailerEsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\TransportInterface as SymfonyMailerTransportInterface;
use Symfony\Component\Mime\Address as SymfonyMimeAddress;
use Symfony\Component\Mime\Crypto\DkimSigner as SymfonyMimeDkimSigner;
use Symfony\Component\Mime\Email as SymfonyMimeEmailMessage;
use Symfony\Component\Mime\Header\HeaderInterface as SymfonyMimeHeaderInterface;
use Symfony\Component\Mime\Message as SymfonyMimeMessage;

/**
 * MailerSymfonyMailer
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.2
 */

class MailerSymfonyMailer extends MailerAbstract
{
    /**
     * @var SymfonyMailerTransportInterface|null
     */
    private $_transport;

    /**
     * @var SymfonyMimeMessage|null
     */
    private $_message;

    /**
     * @var SymfonyMailer|null
     */
    private $_mailer;

    /**
     * @inheritDoc
     */
    public function send(array $params = []): bool
    {
        // params
        $params = new CMap($params);

        // since 1.3.6.7
        if ((int)$params->itemAt('maxConnectionMessages') > 1) {
            $serverId = (int)$params->itemAt('server_id');

            if ($serverId == 0 || $this->_deliveryServerId != $serverId) {
                $this->reset();
            } else {
                $this->resetMessage()->clearLogs();
            }

            $this->_deliveryServerId = $serverId;
        } else {
            $this->reset();
        }

        $this->clearLogs()->setTransport($params)->setMessage($params);

        if (!$this->getTransport() || !$this->getMessage()) {
            return false;
        }

        // since 1.3.5.3
        hooks()->doAction('mailer_before_send_email', $this, $params->toArray());
        if ($this->denySending === true) {
            return false;
        }

        /** @var SymfonyMailer $mailer */
        $mailer = $this->getMailer();

        /** @var SymfonyMimeEmailMessage $message */
        $message = $this->getMessage();

        try {
            $mailer->send($message);
            $this->addLog('OK');
            $sent = true;

            /** @var SymfonyMimeHeaderInterface $messageId */
            $messageId = $message->getHeaders()->get('Message-ID');
            $this->_messageId = (string)str_replace(['<', '>'], '', $messageId->getBodyAsString());
        } catch (SymfonyMailerTransportExceptionInterface $e) {
            $sent = false;
            $this->addLog($e->getMessage());
        }

        // since 1.3.5.3
        hooks()->doAction('mailer_after_send_email', $this, $params->toArray(), $sent);

        $this->_sentCounter++;

        // reset
        if ($this->_sentCounter >= (int)$params->itemAt('maxConnectionMessages')) {
            $this->reset(false);
        } else {
            $this->resetMessage();
        }

        return $sent;
    }

    /**
     * @inheritDoc
     */
    public function getEmailMessage(array $params = []): string
    {
        /** @var SymfonyMimeEmailMessage $message */
        $message = $this->reset()->setMessage(new CMap($params))->getMessage();

        return $message->toString();
    }

    /**
     * @param bool $resetLogs
     *
     * @return $this
     */
    public function reset(bool $resetLogs = true)
    {
        $this->resetTransport()->resetMessage()->resetMailer();

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
        return 'SymfonyMailer';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return t('mailer', 'Next generation mailer.');
    }

    /**
     * @param CMap $params
     *
     * @return $this
     * @throws CException
     */
    protected function setTransport(CMap $params)
    {
        if ($this->_transport !== null) {
            return $this;
        }

        $this->resetTransport()->resetMailer();

        /** @var SymfonyMailerTransportInterface|null $transport */
        $transport = $this->buildTransport($params);
        if (empty($transport)) {
            return $this;
        }

        $this->_transport = $transport;
        $this->_mailer    = new SymfonyMailer($transport);

        /** @var SymfonyMailerTransportInterface $transport */
        $transport = hooks()->applyFilters('mailer_after_create_transport_instance', $this->_transport, $params->toArray(), $this);

        /** @var SymfonyMailer $mailer */
        $mailer = hooks()->applyFilters('mailer_after_create_mailer_instance', $this->_mailer, $params->toArray(), $this);

        $this->_transport = $transport;
        $this->_mailer    = $mailer;

        return $this;
    }

    /**
     * @param CMap $params
     *
     * @return $this
     */
    protected function setMessage(CMap $params)
    {
        $this->resetMessage();

        $requiredKeys = ['to', 'from', 'subject'];
        foreach ($requiredKeys as $key) {
            if (!$params->itemAt($key)) {
                return $this;
            }
        }

        if (!$params->itemAt('body') && !$params->itemAt('plainText')) {
            return $this;
        }

        [$fromEmail, $fromName]         = $this->findEmailAndName($params->itemAt('from'));
        [$toEmail, $toName]             = $this->findEmailAndName($params->itemAt('to'));
        [$replyToEmail, $replyToName]   = $this->findEmailAndName($params->itemAt('replyTo'));

        if ($params->itemAt('fromName') && is_string($params->itemAt('fromName'))) {
            $fromName = $params->itemAt('fromName');
        }

        if ($params->itemAt('toName') && is_string($params->itemAt('toName'))) {
            $toName = $params->itemAt('toName');
        }

        if ($params->itemAt('replyToName') && is_string($params->itemAt('replyToName'))) {
            $replyToName = $params->itemAt('replyToName');
        }

        // dmarc policy...
        if (!$this->isCustomFromDomainAllowed($this->getDomainFromEmail($fromEmail))) {
            $fromEmail = $params->itemAt('username');
        }

        if (!FilterVarHelper::email($fromEmail)) {
            $fromEmail = $params->itemAt('from_email');
        }

        $replyToName  = empty($replyToName) ? $fromName : $replyToName;
        $replyToEmail = empty($replyToEmail) ? $fromEmail : $replyToEmail;
        $returnEmail  = FilterVarHelper::email($params->itemAt('returnPath')) ? $params->itemAt('returnPath') : $params->itemAt('from_email');
        $returnEmail  = FilterVarHelper::email($returnEmail) ? $returnEmail : $fromEmail;
        $returnDomain = $this->getDomainFromEmail($returnEmail, 'local.host');

        $message = (new SymfonyMimeEmailMessage())
            ->subject((string)$params->itemAt('subject'))
            ->from(new SymfonyMimeAddress((string)$fromEmail, (string)$fromName))
            ->to(new SymfonyMimeAddress((string)$toEmail, (string)$toName))
            ->replyTo(new SymfonyMimeAddress((string)$replyToEmail, (string)$replyToName));

        if (app_param('email.custom.returnPath.enabled', true)) {
            $message->returnPath(new SymfonyMimeAddress((string)$returnEmail));
        }

        $body           = (string)$params->itemAt('body');
        $plainText      = (string)$params->itemAt('plainText');
        $onlyPlainText  = $params->itemAt('onlyPlainText') === true;

        if (empty($plainText) && !empty($body)) {
            $plainText = CampaignHelper::htmlToText($body);
        }

        if (!empty($plainText) && empty($body)) {
            $body = $plainText;
        }

        $embedImages = $params->itemAt('embedImages');
        if (!$onlyPlainText && !empty($embedImages) && is_array($embedImages)) {
            foreach ($embedImages as $imageData) {
                if (!isset($imageData['path'], $imageData['cid'], $imageData['mime'])) {
                    continue;
                }
                if (is_file($imageData['path'])) {
                    $message->embedFromPath($imageData['path'], $imageData['cid'], $imageData['mime']);
                }
            }
            unset($embedImages);
        }

        // see https://github.com/symfony/symfony/issues/40131
        // dkim signature fails when text/plain added
        $message->text($plainText, app()->charset);

        if (!$onlyPlainText) {
            $message->html($body, app()->charset);
        }

        $attachments = $params->itemAt('attachments');
        if (!$onlyPlainText && !empty($attachments) && is_array($attachments)) {
            $attachments = array_unique($attachments);
            foreach ($attachments as $attachment) {
                if (is_file($attachment)) {
                    $message->attachFromPath($attachment);
                }
            }
            unset($attachments);
        }

        if ($params->itemAt('headers') && is_array($params->itemAt('headers'))) {
            foreach ($params->itemAt('headers') as $header) {
                if (!is_array($header) || !isset($header['name'], $header['value'])) {
                    continue;
                }
                $message->getHeaders()->addTextHeader($header['name'], $header['value']);
            }
        }

        $messageId = sha1(StringHelper::uniqid() . StringHelper::uniqid() . StringHelper::uniqid()) . '@' . $returnDomain;
        $message->getHeaders()->addIdHeader('Message-ID', $messageId);
        $this->_messageId = $messageId;

        $dkimSign = $params->itemAt('signingEnabled') &&
                    $params->itemAt('dkimPrivateKey') &&
                    $params->itemAt('dkimDomain') &&
                    $params->itemAt('dkimSelector');

        if ($dkimSign) {
            $ignoreHeaders = ['Message-ID', 'Return-Path', 'Sender'];
            foreach ($message->getHeaders()->all() as $header) {
                if (stripos($header->getName(), 'x-') === 0) {
                    $ignoreHeaders[] = $header->getName();
                }
            }

            $signer = new SymfonyMimeDkimSigner(
                (string)$params->itemAt('dkimPrivateKey'),
                (string)$params->itemAt('dkimDomain'),
                (string)$params->itemAt('dkimSelector'),
                [
                    'algorithm'         => SymfonyMimeDkimSigner::ALGO_SHA256,
                    'header_canon'      => SymfonyMimeDkimSigner::CANON_RELAXED,
                    'body_canon'        => SymfonyMimeDkimSigner::CANON_RELAXED,
                    'headers_to_ignore' => $ignoreHeaders,
                ]
            );

            $message = $signer->sign($message);
        }

        /** @var SymfonyMimeEmailMessage $message */
        $message = hooks()->applyFilters('mailer_after_create_message_instance', $message, $params->toArray(), $this);

        $this->_message = $message;

        return $this;
    }

    /**
     * @return SymfonyMailerTransportInterface|null
     */
    protected function getTransport(): ?SymfonyMailerTransportInterface
    {
        return $this->_transport;
    }

    /**
     * @return SymfonyMimeMessage|null
     */
    protected function getMessage(): ?SymfonyMimeMessage
    {
        return $this->_message;
    }

    /**
     * @return SymfonyMailer|null
     */
    protected function getMailer(): ?SymfonyMailer
    {
        return $this->_mailer;
    }

    /**
     * @return $this
     */
    protected function resetTransport(): self
    {
        $this->_sentCounter = 0;
        $this->_transport   = null;
        return $this;
    }

    /**
     * @return $this
     */
    protected function resetMessage(): self
    {
        $this->_message = null;
        return $this;
    }

    /**
     * @return $this
     */
    protected function resetMailer(): self
    {
        $this->_mailer = null;
        return $this;
    }

    /**
     * @param CMap $params
     *
     * @return SymfonyMailerTransportInterface|null
     * @throws CException
     */
    protected function buildTransport(CMap $params): ?SymfonyMailerTransportInterface
    {
        if (!$params->itemAt('transport')) {
            $params->add('transport', 'smtp');
        }

        if ($params->itemAt('transport') == 'smtp') {
            return $this->buildSmtpTransport($params);
        }

        if ($params->itemAt('transport') == 'sendmail') {
            return $this->buildSendmailTransport($params);
        }

        return null;
    }

    /**
     * @param CMap $params
     *
     * @return SymfonyMailerTransportInterface|null
     * @throws CException
     */
    protected function buildSmtpTransport(CMap $params): ?SymfonyMailerTransportInterface
    {
        $requiredKeys = ['hostname'];
        $hasRequiredKeys = true;

        foreach ($requiredKeys as $key) {
            if (!$params->itemAt($key)) {
                $hasRequiredKeys = false;
                break;
            }
        }

        if (!$hasRequiredKeys) {
            return null;
        }

        if (!$params->itemAt('port')) {
            $params->add('port', 25);
        }

        if (!$params->itemAt('timeout')) {
            $params->add('timeout', 30);
        }

        $dsn = sprintf(
            '%s://%s:%s@%s:%d?verify_peer=0',
            in_array((string)$params->itemAt('protocol'), ['ssl', 'tls']) ? 'smtps' : 'smtp',
            urlencode($params->contains('username') ? (string)$params->itemAt('username') : ''),
            urlencode($params->contains('password') ? (string)$params->itemAt('password') : ''),
            urlencode((string)$params->itemAt('hostname')),
            (int)$params->itemAt('port')
        );

        return (new SymfonyMailerEsmtpTransportFactory())->create(SymfonyMailerDsn::fromString($dsn));
    }

    /**
     * @param CMap $params
     *
     * @return SymfonyMailerTransportInterface
     */
    protected function buildSendmailTransport(CMap $params): ?SymfonyMailerTransportInterface
    {
        if (!$params->itemAt('sendmailPath') || !CommonHelper::functionExists('proc_open')) {
            if (!$params->itemAt('sendmailPath')) {
                $this->addLog(t('servers', 'The sendmail path is missing from the transport configuration params!'));
            }
            if (!CommonHelper::functionExists('proc_open')) {
                $this->addLog(t('servers', 'The server type {type} requires following functions to be active on your host: {functions}!', [
                    '{type}'      => 'sendmail',
                    '{functions}' => 'proc_open',
                ]));
            }
            return null;
        }

        $command = $params->itemAt('sendmailPath');
        $command = trim(preg_replace('/\s\-.*/', '', $command));
        $command .= ' -bs';

        $dsn = sprintf('sendmail://default?command=%s', urlencode($command));

        return (new SymfonyMailerSendmailTransportFactory())->create(SymfonyMailerDsn::fromString($dsn));
    }
}
