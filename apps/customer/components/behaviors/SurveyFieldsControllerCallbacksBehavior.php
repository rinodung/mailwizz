<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyFieldsControllerCallbacksBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/**
 * @property mixed $onResponderSaveError
 * @property mixed $onResponderFieldsSorting
 * @property mixed $onSurveyFieldsSorting
 * @property mixed $onSurveyFieldsSave
 * @property mixed $onSurveyFieldsDisplay
 * @property mixed $onResponderSave
 * @property mixed $onResponderFieldsDisplay
 */
class SurveyFieldsControllerCallbacksBehavior extends CBehavior
{
    /**
     * @param CEvent $event
     *
     * @return array
     */
    public function _orderFields(CEvent $event)
    {
        $fields = [];
        $sort   = [];

        foreach ($event->params['fields'] as $type => $_fields) {
            foreach ($_fields as $index => $field) {
                if (!isset($field['sort_order'], $field['field_html'])) {
                    unset($event->params['fields'][$type][$index]);
                    continue;
                }
                $fields[] = $field;
                $sort[] = (int)$field['sort_order'];
            }
        }

        array_multisort($sort, $fields);

        return $event->params['fields'] = $fields;
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _collectAndShowErrorMessages(CEvent $event)
    {
        $instances = isset($event->params['instances']) ? (array)$event->params['instances'] : [];

        // collect and show visible errors.
        foreach ($instances as $instance) {
            if (empty($instance->errors)) {
                continue;
            }
            foreach ($instance->errors as $error) {
                if (empty($error['show']) || empty($error['message'])) {
                    continue;
                }
                notify()->addError($error['message']);
            }
        }
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onSurveyFieldsSave(CEvent $event)
    {
        $this->raiseEvent('onSurveyFieldsSave', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onSurveyFieldsDisplay(CEvent $event)
    {
        $this->raiseEvent('onSurveyFieldsDisplay', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onSurveyFieldsSorting(CEvent $event)
    {
        $this->raiseEvent('onSurveyFieldsSorting', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onSurveyFieldsSaveSuccess(CEvent $event)
    {
        $this->raiseEvent('onSurveyFieldsSaveSuccess', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onSurveyFieldsSaveError(CEvent $event)
    {
        $this->raiseEvent('onSurveyFieldsSaveError', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onResponderFieldsSorting(CEvent $event)
    {
        $this->raiseEvent('onResponderFieldsSorting', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onResponderSave(CEvent $event)
    {
        $this->raiseEvent('onResponderSave', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onResponderFieldsDisplay(CEvent $event)
    {
        $this->raiseEvent('onResponderFieldsDisplay', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onResponderSaveSuccess(CEvent $event)
    {
        $this->raiseEvent('onResponderSaveSuccess', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onResponderSaveError(CEvent $event)
    {
        $this->raiseEvent('onResponderSaveError', $event);
    }
}
