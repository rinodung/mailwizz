<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * BaseFormModel
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property array $attributes
 *
 * @property AttributesShortErrorsBehavior $shortErrors
 * @property AttributeFieldDecoratorBehavior $fieldDecorator
 * @property PaginationOptionsBehavior $paginationOptions
 */
class BaseFormModel extends CFormModel
{
    /**
     * Add the needed traits
     */
    use AddTranslationFunctionalityByCategoryTrait;

    /**
     * Flags
     */
    const TEXT_YES = 'yes';
    const TEXT_NO = 'no';

    /**
     * @var string
     */
    private $_modelName = '';

    /**
     * BaseFormModel::rules()
     *
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $filter = apps()->getCurrentAppName() . '_model_' . strtolower(get_class($this)) . '_' . strtolower(__FUNCTION__);

        /** @var CList $rules */
        $rules = hooks()->applyFilters($filter, new CList());

        $this->onRules(new CModelEvent($this, [
            'rules' => $rules,
        ]));

        return $rules->toArray();
    }

    /**
     * BaseFormModel::onRules()
     *
     * @param CModelEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onRules(CModelEvent $event)
    {
        $this->raiseEvent('onRules', $event);
    }

    /**
     * BaseFormModel::behaviors()
     *
     * @return array
     * @throws CException
     */
    public function behaviors()
    {
        $behaviors = CMap::mergeArray(parent::behaviors(), [
            'shortErrors' => [
                'class' => 'common.components.behaviors.AttributesShortErrorsBehavior',
            ],
            'fieldDecorator' => [
                'class' => 'common.components.behaviors.AttributeFieldDecoratorBehavior',
            ],
            'paginationOptions' => [
                'class' => 'common.components.behaviors.PaginationOptionsBehavior',
            ],
        ]);

        $behaviors = new CMap($behaviors);

        $filter = apps()->getCurrentAppName() . '_model_' . strtolower(get_class($this)) . '_' . strtolower(__FUNCTION__);

        /** @var CMap $behaviors */
        $behaviors = hooks()->applyFilters($filter, $behaviors);

        $this->onBehaviors(new CModelEvent($this, [
            'behaviors' => $behaviors,
        ]));

        return $behaviors->toArray();
    }

    /**
     * BaseFormModel::onBehaviors()
     *
     * @param CModelEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onBehaviors(CModelEvent $event)
    {
        $this->raiseEvent('onBehaviors', $event);
    }

    /**
     * BaseFormModel::attributeLabels()
     *
     * @return array
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = new CMap([
            'status'        => t('app', 'Status'),
            'date_added'    => t('app', 'Date added'),
            'last_updated'  => t('app', 'Last updated'),
        ]);

        $filter = apps()->getCurrentAppName() . '_model_' . strtolower(get_class($this)) . '_' . strtolower(__FUNCTION__);

        /** @var CMap $labels */
        $labels = hooks()->applyFilters($filter, $labels);

        $this->onAttributeLabels(new CModelEvent($this, [
            'labels' => $labels,
        ]));

        return $labels->toArray();
    }

    /**
     * BaseFormModel::onAttributeLabels()
     *
     * @param CModelEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onAttributeLabels(CModelEvent $event)
    {
        $this->raiseEvent('onAttributeLabels', $event);
    }

    /**
     * BaseFormModel::attributeHelpTexts()
     *
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $filter = apps()->getCurrentAppName() . '_model_' . strtolower(get_class($this)) . '_' . strtolower(__FUNCTION__);

        /** @var CMap $texts */
        $texts = hooks()->applyFilters($filter, new CMap());

        $this->onAttributeHelpTexts(new CModelEvent($this, [
            'texts' => $texts,
        ]));

        return $texts->toArray();
    }

    /**
     * BaseFormModel::onAttributeHelpTexts()
     *
     * @param CModelEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onAttributeHelpTexts(CModelEvent $event)
    {
        $this->raiseEvent('onAttributeHelpTexts', $event);
    }

    /**
     * BaseFormModel::attributePlaceholders()
     *
     * @return array
     * @throws CException
     */
    public function attributePlaceholders()
    {
        $filter = apps()->getCurrentAppName() . '_model_' . strtolower(get_class($this)) . '_' . strtolower(__FUNCTION__);

        /** @var CMap $placeholders */
        $placeholders = hooks()->applyFilters($filter, new CMap());

        $this->onAttributePlaceholders(new CModelEvent($this, [
            'placeholders' => $placeholders,
        ]));

        return $placeholders->toArray();
    }

    /**
     * BaseFormModel::onAttributePlaceholders()
     *
     * @param CModelEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onAttributePlaceholders(CModelEvent $event)
    {
        $this->raiseEvent('onAttributePlaceholders', $event);
    }

    /**
     * BaseFormModel::getModelName()
     *
     * @return string
     */
    public function getModelName(): string
    {
        if ($this->_modelName === '') {
            $this->_modelName = get_class($this);
        }
        return $this->_modelName;
    }

    /**
     * @return array
     */
    public function getYesNoOptions(): array
    {
        return [
            self::TEXT_YES  => ucfirst(t('app', self::TEXT_YES)),
            self::TEXT_NO   => ucfirst(t('app', self::TEXT_NO)),
        ];
    }
}
