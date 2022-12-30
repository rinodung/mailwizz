<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * MailerAbstract
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.2
 */

abstract class MailerAbstract extends CApplicationComponent
{

    /**
     * @var bool
     */
    public $denySending = false;

    /**
     * @var string
     */
    protected $_messageId = '';

    /**
     * @var array
     */
    protected $_logs = [];

    /**
     * @var int
     */
    protected $_sentCounter = 0;

    /**
     * @var int
     */
    protected $_deliveryServerId = 0;

    /**
     * @return void
     */
    public function init()
    {
        $this->setLocalServerNameIfMissing();
        parent::init();
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    abstract public function send(array $params = []): bool;

    /**
     * @param array $params
     *
     * @return string
     */
    abstract public function getEmailMessage(array $params = []): string;

    /**
     * @param bool $resetLogs
     *
     * @return MailerAbstract
     */
    abstract public function reset(bool $resetLogs = true);

    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @return string
     */
    abstract public function getDescription(): string;

    /**
     * @return string
     */
    public function getEmailMessageId(): string
    {
        return (string)$this->_messageId;
    }

    /**
     * @param mixed $log
     *
     * @return $this
     */
    public function addLog($log): self
    {
        if (is_array($log)) {
            foreach ($log as $l) {
                $this->addLog($l);
            }
            return $this;
        }
        $this->_logs[] = $log;
        return $this;
    }

    /**
     * @param bool $clear
     *
     * @return array
     */
    public function getLogs(bool $clear = true): array
    {
        $logs = $this->_logs = array_unique($this->_logs);
        if ($clear) {
            $this->clearLogs();
        }
        return $logs;
    }

    /**
     * @param string $glue
     * @param bool $clear
     *
     * @return string
     */
    public function getLog(string $glue = "\n", bool $clear = true): string
    {
        return implode($glue, $this->getLogs($clear));
    }

    /**
     * @return $this
     */
    public function clearLogs(): self
    {
        $this->_logs = [];
        return $this;
    }

    /**
     * @param mixed $data
     *
     * @return array
     */
    public function findEmailAndName($data): array
    {
        if (empty($data)) {
            return [null, null];
        }

        if (is_string($data)) {
            return [$data, null];
        }

        if (is_array($data)) {
            foreach ($data as $email => $name) {
                return [$email, $name];
            }
        }

        return [null, null];
    }

    /**
     * @return $this
     */
    protected function setLocalServerNameIfMissing(): self
    {
        if (!empty($_SERVER) && !empty($_SERVER['SERVER_NAME'])) {
            return $this;
        }

        if (empty($_SERVER)) {
            $_SERVER = [];
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $hostname = $optionUrl->getFrontendUrl();

        if (!empty($hostname)) {
            $hostname = @parse_url($hostname, PHP_URL_HOST);
            if (!empty($hostname)) {
                $_SERVER['SERVER_NAME'] = $hostname;
            }
        }

        if (empty($_SERVER['SERVER_NAME']) && php_uname('n') !== false) {
            $_SERVER['SERVER_NAME'] = php_uname('n');
        }

        if (empty($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = 'localhost.localdomain';
        }

        return $this;
    }

    /**
     * @param string $email
     * @param string $default
     *
     * @return string
     */
    protected function getDomainFromEmail(string $email, string $default = ''): string
    {
        if (strpos($email, '@') === false) {
            return $default;
        }
        $parts = explode('@', $email);
        return $parts[1] ?? $default;
    }

    /**
     * @param string $domain
     *
     * @return bool
     */
    protected function isCustomFromDomainAllowed(string $domain): bool
    {
        static $patterns = [];
        static $domains  = [];

        if (isset($domains[$domain])) {
            return $domains[$domain];
        }

        if (empty($patterns)) {
            $patterns = ['/^yahoo/i', '/^aol/i'];
            $patterns = (array)hooks()->applyFilters('mailer_not_allowed_custom_from_domain_patterns', $patterns);
            $patterns = array_unique($patterns);
        }

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $domain)) {
                return $domains[$domain] = false;
            }
        }

        return $domains[$domain] = true;
    }

    /**
     * @param string $email
     *
     * @return string
     */
    protected function appendDomainNameIfMissing(string $email): string
    {
        if (strpos($email, '@') !== false) {
            return $email;
        }

        if (empty($_SERVER['SERVER_NAME'])) {
            $this->setLocalServerNameIfMissing();
        }
        $searchReplace = [
            '/^(www\.)/i' => '',
        ];
        $thisDomainName = preg_replace(array_keys($searchReplace), array_values($searchReplace), $_SERVER['SERVER_NAME']);
        return $email . '@' . $thisDomainName;
    }
}
