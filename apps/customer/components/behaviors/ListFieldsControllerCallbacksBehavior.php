<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListFieldsControllerCallbacksBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property mixed $onSubscriberSaveError
 * @property mixed $onSubscriberFieldsSorting
 * @property mixed $onListFieldsSorting
 * @property mixed $onListFieldsSave
 * @property mixed $onListFieldsDisplay
 * @property mixed $onSubscriberSave
 * @property mixed $onSubscriberFieldsDisplay
 */
class ListFieldsControllerCallbacksBehavior extends CBehavior
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
    public function onListFieldsSave(CEvent $event)
    {
        $this->raiseEvent('onListFieldsSave', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onListFieldsDisplay(CEvent $event)
    {
        $this->raiseEvent('onListFieldsDisplay', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onListFieldsSorting(CEvent $event)
    {
        $this->raiseEvent('onListFieldsSorting', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onListFieldsSaveSuccess(CEvent $event)
    {
        $this->raiseEvent('onListFieldsSaveSuccess', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onListFieldsSaveError(CEvent $event)
    {
        $this->raiseEvent('onListFieldsSaveError', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onSubscriberFieldsSorting(CEvent $event)
    {
        $this->raiseEvent('onSubscriberFieldsSorting', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onSubscriberSave(CEvent $event)
    {
        $this->raiseEvent('onSubscriberSave', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onSubscriberFieldsDisplay(CEvent $event)
    {
        $this->raiseEvent('onSubscriberFieldsDisplay', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onSubscriberSaveSuccess(CEvent $event)
    {
        $this->raiseEvent('onSubscriberSaveSuccess', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onSubscriberSaveError(CEvent $event)
    {
        $this->raiseEvent('onSubscriberSaveError', $event);
    }
}
