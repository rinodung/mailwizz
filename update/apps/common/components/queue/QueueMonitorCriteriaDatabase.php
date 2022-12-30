<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class QueueMonitorCriteriaDatabase extends QueueMonitorCriteria
{
    /**
     * @var CDbCriteria
     */
    protected $criteria;

    /**
     * QueueMonitorCriteriaDatabase constructor.
     *
     * @param CDbCriteria $criteria
     */
    public function __construct(CDbCriteria $criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * @return CDbCriteria
     */
    public function getCriteria()
    {
        return $this->criteria;
    }
}
