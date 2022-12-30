<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ConsoleCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.6
 *
 */

class ConsoleCommand extends CConsoleCommand
{
    /**
     * Whether this should be verbose and output to console
     *
     * @var int
     */
    public $verbose = 0;

    /**
     * @var int|float
     */
    protected $__startTime = 0;

    /**
     * @var int
     */
    protected $__startMemory = 0;

    /**
     * @var array
     */
    protected $stdoutLogs = [];

    /**
     * @param mixed $message
     * @param bool $timer
     * @param string $separator
     * @param bool $store
     * @return int
     */
    public function stdout($message, bool $timer = true, string $separator = "\n", bool $store = false)
    {
        if ($store) {
            $this->stdoutLogs = array_slice($this->stdoutLogs, -500);
            $this->stdoutLogs[] = ($timer ? '[' . date('Y-m-d H:i:s') . '] - ' : '') . $message . ($separator ? $separator : '');
        }

        if (!$this->verbose) {
            return 0;
        }

        if (!is_array($message)) {
            $message = [$message];
        }

        $out = '';

        foreach ($message as $msg) {
            if ($timer) {
                $out .= '[' . date('Y-m-d H:i:s') . '] - ';
            }

            $out .= $msg;

            if ($separator) {
                $out .= $separator;
            }
        }

        echo $out;
        return 0;
    }

    /**
     * @inheritDoc
     */
    protected function beforeAction($action, $params)
    {
        $this->__startTime   = microtime(true);
        $this->__startMemory = memory_get_peak_usage(true);

        return parent::beforeAction($action, $params);
    }

    /**
     * @param string $action
     * @param array $params
     * @param int $exitCode
     *
     * @return int
     */
    protected function afterAction($action, $params, $exitCode = 0)
    {
        $result = parent::afterAction($action, $params, $exitCode);
        $this->saveCommandHistory($action, $params, $exitCode);

        return $result;
    }

    /**
     * @param array $params
     * @return string
     */
    protected function stringifyParams(array $params = []): string
    {
        if (empty($params)) {
            return '';
        }

        $out = [];
        foreach ($params as $key => $value) {
            $out[] = '--' . $key . '=' . $value;
        }

        return implode(' ', $out);
    }

    /**
     * @param string $action
     * @param array $params
     * @param int $exitCode
     */
    protected function saveCommandHistory(string $action, array $params = [], int $exitCode = 0): void
    {
        if (!app_param('console.save_command_history', true)) {
            return;
        }

        try {
            $command = ConsoleCommandList::model()->findByAttributes([
                'command' => $this->getName(),
            ]);

            if (empty($command)) {
                $command = new ConsoleCommandList();
                $command->command = $this->getName();
                $command->save();
            }

            $commandHistory = new ConsoleCommandListHistory();
            $commandHistory->command_id   = (int)$command->command_id;
            $commandHistory->action       = $action;
            $commandHistory->params       = $this->stringifyParams($params);
            $commandHistory->start_time   = (string)$this->__startTime;
            $commandHistory->end_time     = (string)microtime(true);
            $commandHistory->start_memory = (int)$this->__startMemory;
            $commandHistory->end_memory   = memory_get_peak_usage(true);
            $commandHistory->status       = $exitCode !== 0 ? ConsoleCommandListHistory::STATUS_ERROR : ConsoleCommandListHistory::STATUS_SUCCESS;
            $commandHistory->save();
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
    }

    /**
     * @param bool $active
     *
     * @return $this
     * @throws CException
     */
    protected function setExternalConnectionsActive(bool $active = true): self
    {
        db()->setActive($active);
        mutex()->setConnectionActive($active);

        if (method_exists(cache(), 'setConnectionActive')) {
            cache()->setConnectionActive($active);
        }

        // since 1.9.17
        hooks()->doAction('console_command_set_external_connections_active', $active, $this);

        return $this;
    }

    /**
     * @return CConsoleCommandRunner
     */
    protected function getCommandRunnerClone(): CConsoleCommandRunner
    {
        /** @var CConsoleApplication $app */
        $app = app();

        /** @var CConsoleCommandRunner $runner */
        $runner = clone $app->getCommandRunner();

        return $runner;
    }
}
