<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyFieldBuilderType
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/**
 * @property SurveyFieldBuilderTypeCrud $crudHandler
 * @property SurveyFieldBuilderTypeResponder $responderHandler
 */
class SurveyFieldBuilderType extends CWidget
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
     * @var Survey
     */
    private $_survey;

    /**
     * @var SurveyFieldType
     */
    private $_fieldType;

    /**
     * @var SurveyResponder
     */
    private $_responder;

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
            'responderHandler' => [
                'class' => $this->getBehaviorsBaseAlias() . 'Responder',
            ],
        ]);

        if (apps()->isAppName('customer')) {
            if (in_array($controller->getId(), ['survey_fields'])) {

                /** @var SurveyFieldsControllerCallbacksBehavior $callbacks */
                $callbacks = $controller->asa('callbacks');

                /** @var mixed onSurveyFieldsSave */
                $callbacks->onSurveyFieldsSave = [$this->crudHandler, '_saveFields'];

                /** @var mixed onSurveyFieldsDisplay */
                $callbacks->onSurveyFieldsDisplay = [$this->crudHandler, '_displayFields'];
            } elseif (in_array($controller->getId(), ['survey_responders'])) {

                /** @var SurveyFieldsControllerCallbacksBehavior $callbacks */
                $callbacks = $controller->asa('callbacks');

                /** @var mixed onResponderSave */
                $callbacks->onResponderSave = [$this->responderHandler, '_saveFields'];

                /** @var mixed onResponderFieldsDisplay */
                $callbacks->onResponderFieldsDisplay = [$this->responderHandler, '_displayFields'];
            }
        } elseif (apps()->isAppName('frontend')) {
            if (in_array($controller->getId(), ['surveys'])) {

                /** @var SurveyControllerCallbacksBehavior $callbacks */
                $callbacks = $controller->asa('callbacks');

                /** @var mixed onResponderSave */
                $callbacks->onResponderSave = [$this->responderHandler, '_saveFields'];

                /** @var mixed onResponderFieldsDisplay */
                $callbacks->onResponderFieldsDisplay = [$this->responderHandler, '_displayFields'];
            }
        }
    }

    /**
     * @param Survey $survey
     *
     * @return void
     */
    final public function setSurvey(Survey $survey)
    {
        $this->_survey = $survey;
    }

    /**
     * @return Survey
     * @throws Exception
     */
    final public function getSurvey(): Survey
    {
        if (!($this->_survey instanceof Survey)) {
            throw new Exception('SurveyFieldBuilderType::$survey must be an instance of Survey');
        }
        return $this->_survey;
    }

    /**
     * @param SurveyFieldType $fieldType
     *
     * @return void
     */
    final public function setFieldType(SurveyFieldType $fieldType)
    {
        $this->_fieldType = $fieldType;
    }

    /**
     * @return SurveyFieldType
     * @throws Exception
     */
    final public function getFieldType(): SurveyFieldType
    {
        if (!($this->_fieldType instanceof SurveyFieldType)) {
            throw new Exception('SurveyFieldBuilderType::$fieldType must be an instance of SurveyFieldType');
        }
        return $this->_fieldType;
    }

    /**
     * @param SurveyResponder $responder
     *
     * @return void
     */
    final public function setResponder(SurveyResponder $responder)
    {
        $this->_responder = $responder;
    }

    /**
     * @return SurveyResponder
     * @throws Exception
     */
    final public function getResponder(): SurveyResponder
    {
        if (!($this->_responder instanceof SurveyResponder)) {
            throw new Exception('SurveyFieldBuilderType::$responder must be an instance of SurveyResponder');
        }
        return $this->_responder;
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
        return sprintf('customer.components.survey-field-builder.%s.behaviors.%s', $folderName, $className);
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
