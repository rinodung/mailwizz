<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * MailerSwiftMailer
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.2
 */

class MailerSwiftMailer extends MailerAbstract
{
    /**
     * @var Swift_Transport|null
     */
    private $_transport;

    /**
     * @var Swift_Message|null
     */
    private $_message;

    /**
     * @var Swift_Mailer|null
     */
    private $_mailer;

    /**
     * @var Swift_Plugins_LoggerPlugin|null
     */
    private $_loggerPlugin;

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

        try {

            /** @var Swift_Mailer $mailer */
            $mailer = $this->getMailer();

            /** @var Swift_Message $message */
            $message = $this->getMessage();

            if ($sent = (bool)$mailer->send($message)) {
                $this->addLog('OK');
            } else {
                /** @var Swift_Plugins_LoggerPlugin $logger */
                $logger = $this->getLoggerPlugin();

                $this->addLog($logger->dump());
            }
        } catch (Exception $e) {
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
        /** @var Swift_Message $message */
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
        $this->resetTransport()->resetMessage()->resetMailer()->resetPlugins();

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
        return 'SwiftMailer';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return t('mailer', 'A fully compliant mailer.');
    }

    /**
     * @inheritDoc
     */
    public function clearLogs(): MailerAbstract
    {
        /** @var Swift_Plugins_LoggerPlugin $logger */
        $logger = $this->getLoggerPlugin();
        $logger->clear();

        return parent::clearLogs();
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

        /** @var Swift_Transport|null $transport */
        $transport = $this->buildTransport($params);
        if (empty($transport)) {
            return $this;
        }

        $this->_transport = $transport;
        $this->_mailer    = new Swift_Mailer($transport);
        $this->_mailer->registerPlugin($this->getLoggerPlugin());

        /** @var Swift_Transport $transport */
        $transport = hooks()->applyFilters('mailer_after_create_transport_instance', $this->_transport, $params->toArray(), $this);

        /** @var Swift_Mailer $mailer */
        $mailer = hooks()->applyFilters('mailer_after_create_mailer_instance', $this->_mailer, $params->toArray(), $this);

        // since 1.3.5.3
        $this->_transport = $transport;
        $this->_mailer    = $mailer;

        return $this;
    }

    /**
     * @param CMap $params
     *
     * @return $this
     * @throws Swift_SwiftException
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

        // since 1.3.4.7
        $message  = null;
        $signer   = null;
        $dkimSign = $params->itemAt('signingEnabled') && $params->itemAt('dkimPrivateKey') && $params->itemAt('dkimDomain') && $params->itemAt('dkimSelector');
        if ($dkimSign && version_compare(PHP_VERSION, '5.3', '>=')) {
            $message = new Swift_Message();
            $signer  = new Swift_Signers_DKIMSigner($params->itemAt('dkimPrivateKey'), $params->itemAt('dkimDomain'), $params->itemAt('dkimSelector'));
            $signer->ignoreHeader('Return-Path')->ignoreHeader('Sender');
            $signer->setHeaderCanon('relaxed');
            $signer->setBodyCanon('relaxed');

            // since 1.5.2
            try {
                $signer->setHashAlgorithm('rsa-sha256');
            } catch (Exception $e) {
                $signer->setHashAlgorithm('rsa-sha1');
            }

            $message->attachSigner($signer);
        }

        if (empty($message)) {
            $message = new Swift_Message();
        }

        $message->setCharset(app()->charset);
        $message->setMaxLineLength(990);
        $message->setId(sha1(StringHelper::uniqid() . StringHelper::uniqid() . StringHelper::uniqid()) . '@' . $returnDomain);

        $this->_message   = $message;
        $this->_messageId = (string)str_replace(['<', '>'], '', $message->getId());

        if ($params->itemAt('headers') && is_array($params->itemAt('headers'))) {
            foreach ($params->itemAt('headers') as $header) {
                if (!is_array($header) || !isset($header['name'], $header['value'])) {
                    continue;
                }
                $message->getHeaders()->addTextHeader($header['name'], $header['value']);
            }
        }

        $message->setSubject($params->itemAt('subject'));
        $message->setFrom($fromEmail, $fromName);
        $message->setTo($toEmail, $toName);
        $message->setReplyTo($replyToEmail, $replyToName);

        // since 1.7.3
        if (app_param('email.custom.returnPath.enabled', true)) {
            $message->setReturnPath($returnEmail);
        }
        //

        $body           = $params->itemAt('body');
        $plainText      = $params->itemAt('plainText');
        $onlyPlainText  = $params->itemAt('onlyPlainText') === true;

        if (empty($plainText) && !empty($body)) {
            $plainText = CampaignHelper::htmlToText($body);
        }

        if (!empty($plainText) && empty($body)) {
            $body = $plainText;
        }

        $embedImages = $params->itemAt('embedImages');
        if (!$onlyPlainText && !empty($embedImages) && is_array($embedImages)) {
            $cids = [];
            foreach ($embedImages as $imageData) {
                if (!isset($imageData['path'], $imageData['cid'])) {
                    continue;
                }
                if (is_file($imageData['path'])) {
                    $cids['cid:' . $imageData['cid']] = $message->embed(Swift_Image::fromPath($imageData['path']));
                }
            }
            if (!empty($cids)) {
                $body = (string)str_replace(array_keys($cids), array_values($cids), $body);
            }
            unset($embedImages, $cids);
        }
        //

        if ($onlyPlainText) {
            $message->setBody($plainText, 'text/plain', app()->charset);
        } else {
            $message->setBody($body, 'text/html', app()->charset);
            $message->addPart($plainText, 'text/plain', app()->charset);
        }

        $attachments = $params->itemAt('attachments');
        if (!$onlyPlainText && !empty($attachments) && is_array($attachments)) {
            $attachments = array_unique($attachments);
            foreach ($attachments as $attachment) {
                if (is_file($attachment)) {
                    $message->attach(Swift_Attachment::fromPath($attachment));
                }
            }
            unset($attachments);
        }

        // since 1.3.6.3
        if ($signer) {
            $listHeaders = $message->getHeaders()->listAll();
            foreach ($listHeaders as $hName) {
                if (stripos($hName, 'x-') === 0) {
                    $signer->ignoreHeader($hName);
                }
            }
        }

        /** @var Swift_Message $message */
        $message = hooks()->applyFilters('mailer_after_create_message_instance', $message, $params->toArray(), $this);

        // since 1.3.5.3
        $this->_message = $message;

        return $this;
    }

    /**
     * @return Swift_Transport|null
     */
    protected function getTransport(): ?Swift_Transport
    {
        return $this->_transport;
    }

    /**
     * @return Swift_Message|null
     */
    protected function getMessage(): ?Swift_Message
    {
        return $this->_message;
    }

    /**
     * @return Swift_Mailer|null
     */
    protected function getMailer(): ?Swift_Mailer
    {
        return $this->_mailer;
    }

    /**
     * @param Swift_Plugins_LoggerPlugin $loggerPlugin
     *
     * @return $this
     */
    protected function setLoggerPlugin(Swift_Plugins_LoggerPlugin $loggerPlugin)
    {
        $this->_loggerPlugin = $loggerPlugin;
        return $this;
    }

    /**
     * @return Swift_Plugins_LoggerPlugin
     */
    protected function getLoggerPlugin(): Swift_Plugins_LoggerPlugin
    {
        if ($this->_loggerPlugin === null) {
            $this->_loggerPlugin = new Swift_Plugins_LoggerPlugin(new Swift_Plugins_Loggers_ArrayLogger());
        }
        return $this->_loggerPlugin;
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
     * @return $this
     */
    protected function resetPlugins(): self
    {
        $this->_loggerPlugin = null;
        return $this;
    }

    /**
     * @param CMap $params
     *
     * @return Swift_Transport|null
     * @throws CException
     */
    protected function buildTransport(CMap $params): ?Swift_Transport
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
     * @return Swift_Transport|null
     * @throws CException
     */
    protected function buildSmtpTransport(CMap $params): ?Swift_Transport
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

        try {
            $transport = new Swift_SmtpTransport($params->itemAt('hostname'), (int)$params->itemAt('port'), $params->itemAt('protocol'));
            if ($params->itemAt('username')) {
                $transport->setUsername($params->itemAt('username'));
            }
            if ($params->itemAt('password')) {
                $transport->setPassword($params->itemAt('password'));
            }
            $transport->setTimeout((int)$params->itemAt('timeout'));

            // because the old swift version does not have this option
            if (method_exists($transport, 'setStreamOptions')) {
                $transport->setStreamOptions([
                    'ssl' => [
                        'allow_self_signed' => true,
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                    ],
                ]);
            }
        } catch (Exception $e) {
            $this->addLog($e->getMessage());
            return null;
        }

        return $transport;
    }

    /**
     * @param CMap $params
     *
     * @return Swift_Transport|null
     */
    protected function buildSendmailTransport(CMap $params): ?Swift_Transport
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
        $transport = null;

        try {
            $transport = new Swift_SendmailTransport($command);
        } catch (Exception $e) {
            $this->addLog($e->getMessage());
            $transport = null;
        }

        return $transport;
    }
}
