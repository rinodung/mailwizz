<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * NotifyManager
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property bool $hasSuccess
 * @property bool $hasError
 * @property bool $hasWarning
 * @property bool $hasInfo
 */
class NotifyManager extends CApplicationComponent
{
    /**
     * Notification types
     */
    const ERROR      = 'error';
    const WARNING    = 'warning';
    const INFO       = 'info';
    const SUCCESS    = 'success';

    /**
     * @var string
     */
    public $errorClass = 'alert alert-block alert-danger';

    /**
     * @var string
     */
    public $warningClass = 'alert alert-block alert-warning';

    /**
     * @var string
     */
    public $infoClass = 'alert alert-block alert-info';

    /**
     * @var string
     */
    public $successClass = 'alert alert-block alert-success';

    /**
     * @var string
     */
    public $htmlWrapper = '<div class="%s">%s</div>';

    /**
     * @var string
     */
    public $htmlCloseButton = '<button type="button" class="close" data-dismiss="alert">&times;</button>';

    /**
     * @var string
     */
    public $htmlHeading = '<p>%s</p>';

    /**
     * @var array
     */
    protected $cliMessages = [];

    /**
     * @return string
     */
    public function show(): string
    {
        $output = '';

        if (is_cli()) {
            foreach ([self::ERROR, self::WARNING, self::INFO, self::SUCCESS] as $type) {
                if (!empty($this->cliMessages[$type])) {
                    foreach ($this->cliMessages[$type] as $message) {
                        $output .= t('app', ucfirst($type)) . ': ' . strip_tags($message) . "\n";
                    }
                    $this->cliMessages[$type] = [];
                }
            }
            return $output;
        }

        $error      = user()->getFlash('__notify_error', []);
        $warning    = user()->getFlash('__notify_warning', []);
        $info       = user()->getFlash('__notify_info', []);
        $success    = user()->getFlash('__notify_success', []);

        $error      = is_array($error) ? array_unique($error) : [];
        $warning    = is_array($warning) ? array_unique($warning) : [];
        $info       = is_array($info) ? array_unique($info) : [];
        $success    = is_array($success) ? array_unique($success) : [];

        if (count($error) > 0) {
            $liItems = [];
            foreach ($error as $message) {
                $liItems[] = CHtml::tag('li', [], $message);
            }
            $ul = CHtml::tag('ul', [], implode("\n", $liItems));

            $content = '';
            if ($this->htmlCloseButton) {
                $content.= $this->htmlCloseButton;
            }
            if (($heading = $this->getErrorHeading()) && $this->htmlHeading) {
                $content.= sprintf($this->htmlHeading, $heading);
            }
            $content.= $ul;
            $output .= sprintf($this->htmlWrapper, $this->errorClass, $content);
        }

        if (count($warning) > 0) {
            $liItems = [];
            foreach ($warning as $message) {
                $liItems[] = CHtml::tag('li', [], $message);
            }
            $ul = CHtml::tag('ul', [], implode("\n", $liItems));

            $content = '';
            if ($this->htmlCloseButton) {
                $content.= $this->htmlCloseButton;
            }
            if (($heading = $this->getWarningHeading()) && $this->htmlHeading) {
                $content.= sprintf($this->htmlHeading, $heading);
            }
            $content.= $ul;
            $output .= sprintf($this->htmlWrapper, $this->warningClass, $content);
        }

        if (count($info) > 0) {
            $liItems = [];
            foreach ($info as $message) {
                $liItems[] = CHtml::tag('li', [], $message);
            }
            $ul = CHtml::tag('ul', [], implode("\n", $liItems));

            $content = '';
            if ($this->htmlCloseButton) {
                $content.= $this->htmlCloseButton;
            }
            if (($heading = $this->getInfoHeading()) && $this->htmlHeading) {
                $content.= sprintf($this->htmlHeading, $heading);
            }
            $content.= $ul;
            $output .= sprintf($this->htmlWrapper, $this->infoClass, $content);
        }

        if (count($success) > 0) {
            $liItems = [];
            foreach ($success as $message) {
                $liItems[] = CHtml::tag('li', [], $message);
            }
            $ul = CHtml::tag('ul', [], implode("\n", $liItems));

            $content = '';
            if ($this->htmlCloseButton) {
                $content.= $this->htmlCloseButton;
            }
            if (($heading = $this->getSuccessHeading()) && $this->htmlHeading) {
                $content.= sprintf($this->htmlHeading, $heading);
            }
            $content.= $ul;
            $output .= sprintf($this->htmlWrapper, $this->successClass, $content);
        }

        return $output;
    }

    /**
     * @param mixed $message
     * @param string $type
     *
     * @return NotifyManager
     */
    public function add($message, string $type = self::WARNING): self
    {
        $map = [
            self::ERROR     => 'addError',
            self::WARNING   => 'addWarning',
            self::INFO      => 'addInfo',
            self::SUCCESS   => 'addSuccess',
        ];

        if (!in_array($type, array_keys($map))) {
            $type = self::WARNING;
        }

        $method = $map[$type];
        return $this->$method($message);
    }

    /**
     * @param mixed $message
     *
     * @return NotifyManager
     */
    public function addError($message): self
    {
        if (!is_array($message)) {
            $message = [$message];
        }

        if (is_cli()) {
            if (!isset($this->cliMessages[self::ERROR])) {
                $this->cliMessages[self::ERROR] = [];
            }
            $this->cliMessages[self::ERROR] = CMap::mergeArray($this->cliMessages[self::ERROR], $message);
            return $this;
        }

        $flash = user()->getFlash('__notify_error', [], false);
        $flash = CMap::mergeArray($flash, $message);
        user()->setFlash('__notify_error', $flash);

        return $this;
    }

    /**
     * @param mixed $message
     *
     * @return NotifyManager
     */
    public function addWarning($message): self
    {
        if (!is_array($message)) {
            $message = [$message];
        }

        if (is_cli()) {
            if (!isset($this->cliMessages[self::WARNING])) {
                $this->cliMessages[self::WARNING] = [];
            }
            $this->cliMessages[self::WARNING] = CMap::mergeArray($this->cliMessages[self::WARNING], $message);
            return $this;
        }

        $flash = user()->getFlash('__notify_warning', [], false);
        $flash = CMap::mergeArray($flash, $message);
        user()->setFlash('__notify_warning', $flash);

        return $this;
    }

    /**
     * @param mixed $message
     *
     * @return NotifyManager
     */
    public function addInfo($message): self
    {
        if (!is_array($message)) {
            $message = [$message];
        }

        if (is_cli()) {
            if (!isset($this->cliMessages[self::INFO])) {
                $this->cliMessages[self::INFO] = [];
            }
            $this->cliMessages[self::INFO] = CMap::mergeArray($this->cliMessages[self::INFO], $message);
            return $this;
        }

        $flash = user()->getFlash('__notify_info', [], false);
        $flash = CMap::mergeArray($flash, $message);
        user()->setFlash('__notify_info', $flash);

        return $this;
    }

    /**
     * @param mixed $message
     *
     * @return NotifyManager
     */
    public function addSuccess($message): self
    {
        if (!is_array($message)) {
            $message = [$message];
        }

        if (is_cli()) {
            if (!isset($this->cliMessages[self::SUCCESS])) {
                $this->cliMessages[self::SUCCESS] = [];
            }
            $this->cliMessages[self::SUCCESS] = CMap::mergeArray($this->cliMessages[self::SUCCESS], $message);
            return $this;
        }

        $flash = user()->getFlash('__notify_success', [], false);
        $flash = CMap::mergeArray($flash, $message);
        user()->setFlash('__notify_success', $flash);

        return $this;
    }

    /**
     * @return NotifyManager
     */
    public function clearError(): self
    {
        if (is_cli()) {
            $this->cliMessages[self::ERROR] = [];
            return $this;
        }
        user()->setFlash('__notify_error', []);
        return $this;
    }

    /**
     * @return NotifyManager
     */
    public function clearWarning(): self
    {
        if (is_cli()) {
            $this->cliMessages[self::WARNING] = [];
            return $this;
        }
        user()->setFlash('__notify_warning', []);
        return $this;
    }

    /**
     * @return NotifyManager
     */
    public function clearInfo(): self
    {
        if (is_cli()) {
            $this->cliMessages[self::INFO] = [];
            return $this;
        }
        user()->setFlash('__notify_info', []);
        return $this;
    }

    /**
     * @return NotifyManager
     */
    public function clearSuccess(): self
    {
        if (is_cli()) {
            $this->cliMessages[self::SUCCESS] = [];
            return $this;
        }
        user()->setFlash('__notify_success', []);
        return $this;
    }

    /**
     * @return NotifyManager
     */
    public function clearAll(): self
    {
        return $this->clearError()->clearWarning()->clearInfo()->clearSuccess();
    }

    /**
     * @param string $text
     *
     * @return NotifyManager
     */
    public function setErrorHeading(string $text): self
    {
        if (is_cli()) {
            return $this;
        }
        user()->setFlash('__notify_error_heading', $text);
        return $this;
    }

    /**
     * @return string
     */
    public function getErrorHeading(): string
    {
        if (is_cli()) {
            return '';
        }
        return (string)user()->getFlash('__notify_error_heading', '');
    }

    /**
     * @param string $text
     *
     * @return NotifyManager
     */
    public function setWarningHeading(string $text): self
    {
        if (is_cli()) {
            return $this;
        }
        user()->setFlash('__notify_warning_heading', $text);
        return $this;
    }

    /**
     * @return string
     */
    public function getWarningHeading(): string
    {
        if (is_cli()) {
            return '';
        }
        return (string)user()->getFlash('__notify_warning_heading', '');
    }

    /**
     * @param string $text
     *
     * @return NotifyManager
     */
    public function setInfoHeading(string $text): self
    {
        if (is_cli()) {
            return $this;
        }
        user()->setFlash('__notify_info_heading', $text);
        return $this;
    }

    /**
     * @return string
     */
    public function getInfoHeading(): string
    {
        if (is_cli()) {
            return '';
        }
        return (string)user()->getFlash('__notify_info_heading', '');
    }

    /**
     * @param string $text
     *
     * @return NotifyManager
     */
    public function setSuccessHeading(string $text): self
    {
        if (is_cli()) {
            return $this;
        }
        user()->setFlash('__notify_success_heading', $text);
        return $this;
    }

    /**
     * @return string
     */
    public function getSuccessHeading(): string
    {
        if (is_cli()) {
            return '';
        }
        return (string)user()->getFlash('__notify_success_heading', '');
    }

    /**
     * @return bool
     */
    public function getHasSuccess(): bool
    {
        if (is_cli()) {
            return !empty($this->cliMessages[self::SUCCESS]);
        }
        $messages = user()->getFlash('__notify_success', [], false);
        return !empty($messages);
    }

    /**
     * @return bool
     */
    public function getHasInfo(): bool
    {
        if (is_cli()) {
            return !empty($this->cliMessages[self::INFO]);
        }
        $messages = user()->getFlash('__notify_info', [], false);
        return !empty($messages);
    }

    /**
     * @return bool
     */
    public function getHasWarning(): bool
    {
        if (is_cli()) {
            return !empty($this->cliMessages[self::WARNING]);
        }
        $messages = user()->getFlash('__notify_warning', [], false);
        return !empty($messages);
    }

    /**
     * @return bool
     */
    public function getHasError(): bool
    {
        if (is_cli()) {
            return !empty($this->cliMessages[self::ERROR]);
        }
        $messages = user()->getFlash('__notify_error', [], false);
        return !empty($messages);
    }

    /**
     * @return bool
     */
    public function getIsEmpty(): bool
    {
        return !$this->getHasSuccess() && !$this->getHasInfo() && !$this->getHasWarning() && !$this->getHasError();
    }
}
