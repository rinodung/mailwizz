<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Enqueue\Dbal\DbalConnectionFactory;
use Interop\Queue\Context;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class QueueDatabase extends QueueBase
{
    /**
     * @return Context
     * @throws CException
     */
    public function getContext(): Context
    {
        if ($this->_context === null) {
            db()->setActive(true);
            $pdo = db()->getPdoInstance();
            $connectionFactory = new DbalConnectionFactory([
                'connection'    => [
                    'driver'    => 'pdo_' . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
                    'pdo'       => $pdo,
                ],
                'table_name'    => db()->tablePrefix . 'queue',
                // by default it's 20 minutes, which causes issues with longer running operations, so we give it 24 hours
                'redelivery_delay'  => 24 * 3600 * 1000,
            ]);

            $this->_context = $connectionFactory->createContext();
            try {
                $tableExists = db()->getSchema()->getTable('{{queue}}');
            } catch (Exception $e) {
                $tableExists = false;
            }
            if (!$tableExists) {
                $this->_context->createDataBaseTable();
            }
        }

        return $this->_context;
    }
}
