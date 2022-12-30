<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DatabaseHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class DatabaseHelper
{
    /**
     * @param callable $callback
     * @param bool $doTransaction
     * @param string $transactionLockName
     *
     * @return bool
     */
    public static function runIsolated(callable $callback, bool $doTransaction = true, string $transactionLockName = ''): bool
    {
        if (empty($transactionLockName)) {
            $transactionLockName = sha1(__METHOD__);
        }

        /** @var CDbTransaction $transaction */
        $transaction = null;
        if ($doTransaction) {
            if (!mutex()->acquire($transactionLockName, 30)) {
                return false;
            }
            $transaction = db()->beginTransaction();
        }

        try {
            $result = (bool)call_user_func($callback, $transaction);

            if (!empty($transaction) && $transaction->getActive()) {
                if ($result) {
                    $transaction->commit();
                } else {
                    $transaction->rollback();
                }
            }
        } catch (Exception $e) {
            if (!empty($transaction) && $transaction->getActive()) {
                try {
                    $transaction->rollback();
                } catch (Exception $e) {
                }
            }

            $result = false;
        }

        if ($doTransaction) {
            mutex()->release($transactionLockName);
        }

        return $result;
    }
}
