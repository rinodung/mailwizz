<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListFieldBuilderType
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property ListFieldBuilderTypeCrud $crudHandler
 * @property ListFieldBuilderTypeSubscriber $subscriberHandler
 */
class ListFieldBuilderType extends CWidget
{
    /**
     * Add the needed traits
     */
    use DetectLanguageFromJsFilePathTrait;

    /**
     * @var array
     */
    public $errors = [];

    /**
     * @var Lists
     */
    private $_list;

    /**
     * @var ListFieldType
     */
    private $_fieldType;

    /**
     * @var ListSubscriber
     */
    private $_subscriber;

    /**
     * @var int
     */
    private static $_index = -1;

    /**
     * @return void
     */
    public function run()
    {
        /** @var Controller|null $controller */
        $controller = app()->getController();

        if (empty($controller)) {
            return;
        }

        $this->attachBehaviors([
            'crudHandler' => [
                'class' => $this->getBehaviorsBaseAlias() . 'Crud',
            ],
            'subscriberHandler' => [
                'class' => $this->getBehaviorsBaseAlias() . 'Subscriber',
            ],
        ]);

        if (apps()->isAppName('customer')) {
            if (in_array($controller->getId(), ['list_fields'])) {

                /** @var ListFieldsControllerCallbacksBehavior $callbacks */
                $callbacks = $controller->asa('callbacks');

                /** @var mixed onListFieldsSave */
                $callbacks->onListFieldsSave = [$this->crudHandler, '_saveFields'];

                /** @var mixed onListFieldsDisplay */
                $callbacks->onListFieldsDisplay = [$this->crudHandler, '_displayFields'];
            } elseif (in_array($controller->getId(), ['list_subscribers'])) {

                /** @var ListFieldsControllerCallbacksBehavior $callbacks */
                $callbacks = $controller->asa('callbacks');

                /** @var mixed onSubscriberSave */
                $callbacks->onSubscriberSave = [$this->subscriberHandler, '_saveFields'];

                /** @var mixed onSubscriberFieldsDisplay */
                $callbacks->onSubscriberFieldsDisplay = [$this->subscriberHandler, '_displayFields'];
            }
        } elseif (apps()->isAppName('frontend')) {
            if (in_array($controller->getId(), ['lists'])) {

                /** @var ListControllerCallbacksBehavior $callbacks */
                $callbacks = $controller->asa('callbacks');

                /** @var mixed onSubscriberSave */
                $callbacks->onSubscriberSave = [$this->subscriberHandler, '_saveFields'];

                /** @var mixed onSubscriberFieldsDisplay */
                $callbacks->onSubscriberFieldsDisplay = [$this->subscriberHandler, '_displayFields'];
            }
        }
    }

    /**
     * @param Lists $list
     *
     * @return void
     */
    final public function setList(Lists $list)
    {
        $this->_list = $list;
    }

    /**
     * @return Lists
     * @throws Exception
     */
    final public function getList(): Lists
    {
        if (!($this->_list instanceof Lists)) {
            throw new Exception('ListFieldBuilderType::$list must be an instance of Lists');
        }
        return $this->_list;
    }

    /**
     * @param ListFieldType $fieldType
     *
     * @return void
     */
    final public function setFieldType(ListFieldType $fieldType)
    {
        $this->_fieldType = $fieldType;
    }

    /**
     * @return ListFieldType
     * @throws Exception
     */
    final public function getFieldType(): ListFieldType
    {
        if (!($this->_fieldType instanceof ListFieldType)) {
            throw new Exception('ListFieldBuilderType::$fieldType must be an instance of ListFieldType');
        }
        return $this->_fieldType;
    }

    /**
     * @param ListSubscriber $subscriber
     *
     * @return void
     */
    final public function setSubscriber(ListSubscriber $subscriber)
    {
        $this->_subscriber = $subscriber;
    }

    /**
     * @return ListSubscriber
     * @throws Exception
     */
    final public function getSubscriber(): ListSubscriber
    {
        if (!($this->_subscriber instanceof ListSubscriber)) {
            throw new Exception('ListFieldBuilderType::$subscriber must be an instance of ListSubscriber');
        }
        return $this->_subscriber;
    }

    /**
     * @return int
     */
    final public function getIndex()
    {
        return self::$_index++;
    }

    /**
     * @return string
     */
    public function getBehaviorsBaseAlias(): string
    {
        try {
            $folderName = basename(trim(dirname((string)(new ReflectionClass($this))->getFileName()), DIRECTORY_SEPARATOR));
        } catch (Exception $e) {
            $folderName = '';
        }

        $className = get_class($this);
        return sprintf('customer.components.list-field-builder.%s.behaviors.%s', $folderName, $className);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _addInputErrorClass(CEvent $event)
    {
        if ($event->sender->owner->hasErrors($event->params['attribute'])) {
            if (!isset($event->params['htmlOptions']['class'])) {
                $event->params['htmlOptions']['class'] = '';
            }
            $event->params['htmlOptions']['class'] .= ' error';
        }
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _addFieldNameClass(CEvent $event)
    {
        if (!isset($event->params['htmlOptions']['class'])) {
            $event->params['htmlOptions']['class'] = '';
        }
        $event->params['htmlOptions']['class'] .= ' field-' . strtolower((string)$event->sender->owner->field->tag) . ' field-type-' . strtolower((string)$event->sender->owner->field->type->identifier);
    }
}
