<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListFieldBuilderTypeYearsRangeModelSettersGetters
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.0
 */

/**
 * @property ListField $owner
 */
class ListFieldBuilderTypeYearsRangeModelSettersGetters extends CBehavior
{
    /**
     * @param mixed $value
     *
     * @return void
     * @throws CException
     */
    public function setYearStart($value)
    {
        $this->owner->modelMetaData->getModelMetaData()->add('year_start', $value);
    }

    /**
     * @return mixed
     * @throws CException
     */
    public function getYearStart()
    {
        return $this->owner->modelMetaData->getModelMetaData()->itemAt('year_start');
    }

    /**
     * @param mixed $value
     *
     * @return void
     * @throws CException
     */
    public function setYearEnd($value)
    {
        $this->owner->modelMetaData->getModelMetaData()->add('year_end', $value);
    }

    /**
     * @return mixed
     * @throws CException
     */
    public function getYearEnd()
    {
        return $this->owner->modelMetaData->getModelMetaData()->itemAt('year_end');
    }

    /**
     * @param mixed $value
     *
     * @return void
     * @throws CException
     */
    public function setYearStep($value)
    {
        $this->owner->modelMetaData->getModelMetaData()->add('year_step', $value);
    }

    /**
     * @return int
     * @throws CException
     */
    public function getYearStep()
    {
        $step = (int)$this->owner->modelMetaData->getModelMetaData()->itemAt('year_step');
        return $step <= 1 ? 1 : $step;
    }

    /**
     * @return int
     */
    public function getYearMin()
    {
        return (int)date('Y') - 300;
    }

    /**
     * @return int
     */
    public function getYearMax()
    {
        return (int)date('Y') + 300;
    }
}
