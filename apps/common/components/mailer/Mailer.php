<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Mailer
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.2
 */

class Mailer extends CApplicationComponent
{
    /**
     * Default mailer
     */
    const DEFAULT_MAILER = 'SwiftMailer';

    /**
     * Holds the active mailer name
     *
     * @var string
     */
    protected $_activeMailer;

    /**
     * Holds CMap of available mailers and their configuration/instances
     *
     * @var CMap
     */
    protected $_mailers;

    /**
     * @param array $params
     *
     * @return bool
     * @throws CException
     */
    public function send(array $params = []): bool
    {
        return $this->getMailer()->send($params);
    }

    /**
     * @param array $params
     *
     * @return string
     * @throws CException
     */
    public function getEmailMessage(array $params = []): string
    {
        return $this->getMailer()->getEmailMessage($params);
    }

    /**
     * @param bool $clearLogs
     *
     * @return MailerAbstract
     * @throws CException
     */
    public function reset(bool $clearLogs = true): MailerAbstract
    {
        return $this->getMailer()->reset($clearLogs);
    }

    /**
     * @return string
     * @throws CException
     */
    public function getName(): string
    {
        return $this->getMailer()->getName();
    }

    /**
     * @return string
     * @throws CException
     */
    public function getDescription(): string
    {
        return $this->getMailer()->getDescription();
    }

    /**
     * @return string
     * @throws CException
     */
    public function getEmailMessageId(): string
    {
        return $this->getMailer()->getEmailMessageId();
    }

    /**
     * @param mixed $log
     *
     * @return $this
     * @throws CException
     */
    public function addLog($log): self
    {
        $this->getMailer()->addLog($log);
        return $this;
    }

    /**
     * @param bool $clear
     *
     * @return array
     * @throws CException
     */
    public function getLogs(bool $clear = true): array
    {
        return $this->getMailer()->getLogs($clear);
    }

    /**
     * @param string $glue
     * @param bool $clear
     *
     * @return string
     * @throws CException
     */
    public function getLog(string $glue = "\n", bool $clear = true): string
    {
        return $this->getMailer()->getLog($glue, $clear);
    }

    /**
     * @return MailerAbstract
     * @throws CException
     */
    public function clearLogs(): MailerAbstract
    {
        return $this->getMailer()->clearLogs();
    }

    /**
     * @param string $mailer
     *
     * @return Mailer
     */
    public function setActiveMailer(string $mailer): self
    {
        $this->_activeMailer = $mailer;
        return $this;
    }

    /**
     * @return string
     */
    public function getActiveMailer(): string
    {
        if (empty($this->_activeMailer) || !$this->getMailers()->contains($this->_activeMailer)) {
            $this->_activeMailer = $this->getDefaultMailer();
        }
        return $this->_activeMailer;
    }

    /**
     * @return CMap
     * @throws CException
     */
    public function getAllInstances(): CMap
    {
        /**
         * @var string $key
         * @var array $value
         */
        foreach ($this->getMailers() as $key => $value) {
            $this->instance($key);
        }
        return $this->getMailers();
    }

    /**
     * @param mixed $data
     *
     * @return array
     * @throws CException
     */
    public function findEmailAndName($data): array
    {
        return $this->getMailer()->findEmailAndName($data);
    }

    /**
     * @return MailerAbstract
     * @throws CException
     */
    protected function getMailer(): MailerAbstract
    {
        return $this->instance($this->getActiveMailer());
    }

    /**
     * @param string $mailerName
     *
     * @return MailerAbstract
     * @throws CException
     */
    protected function instance(string $mailerName): MailerAbstract
    {
        $mailer = $this->getMailers()->itemAt($mailerName);
        if (is_array($mailer)) {
            /** @var MailerAbstract $mailer */
            $mailer = Yii::createComponent($mailer);
            $mailer->init();
            $this->getMailers()->add($mailerName, $mailer);
        }
        if (!$mailer) {
            $this->getMailers()->remove($mailerName);
        }
        return $mailer;
    }

    /**
     * @return string
     */
    protected function getDefaultMailer(): string
    {
        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        $defaultMailer = $common->getDefaultMailer();
        $defaultMailer = $defaultMailer ?: self::DEFAULT_MAILER;

        if (!$this->getMailers()->contains($defaultMailer)) {
            if ($this->getMailers()->getCount() > 0) {
                foreach ($this->getMailers()->toArray() as $name => $value) {
                    $defaultMailer = $name;
                    break;
                }
            } else {
                $defaultMailer = self::DEFAULT_MAILER;
            }
        }
        return $defaultMailer;
    }

    /**
     * @return CMap
     */
    protected function getMailers(): CMap
    {
        if ($this->_mailers !== null && $this->_mailers instanceof CMap) {
            return $this->_mailers;
        }

        $mailers = [
            'SwiftMailer' => [
                'class'   => 'common.components.mailer.MailerSwiftMailer',
            ],
            'SymfonyMailer' => [
                'class'   => 'common.components.mailer.MailerSymfonyMailer',
            ],
            'DummyMailer' => [
                'class'   => 'common.components.mailer.MailerDummyMailer',
            ],
        ];

        $mailers = (array)hooks()->applyFilters('mailer_get_mailers_list', $mailers);
        try {
            $this->_mailers = new CMap($mailers);
        } catch (Exception $e) {
        }

        return $this->_mailers;
    }
}
