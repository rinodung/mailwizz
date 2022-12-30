<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyFieldBuilderTypeResponder
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

abstract class SurveyFieldBuilderTypeResponder extends CBehavior
{
    /**
     * @param CEvent $event
     *
     * @return void
     */
    abstract public function _saveFields(CEvent $event);

    /**
     * @param CEvent $event
     *
     * @return void
     */
    abstract public function _displayFields(CEvent $event);

    /**
     * @param CModelEvent $event
     *
     * @return void
     */
    abstract public function _setCorrectLabel(CModelEvent $event);

    /**
     * @param CModelEvent $event
     *
     * @return void
     */
    abstract public function _setCorrectValidationRules(CModelEvent $event);

    /**
     * @param CModelEvent $event
     *
     * @return void
     */
    abstract public function _setCorrectHelpText(CModelEvent $event);

    /**
     * @return SurveyFieldValue[]
     */
    abstract protected function getValueModels(): array;

    /**
     * @param SurveyFieldValue $model
     *
     * @return array
     */
    abstract protected function buildFieldArray(SurveyFieldValue $model): array;
}
