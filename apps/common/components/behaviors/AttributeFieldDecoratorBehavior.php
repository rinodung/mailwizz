<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * AttributeFieldDecoratorBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property ActiveRecord|BaseFormModel $owner
 * @property mixed $onHtmlOptionsSetup
 */
class AttributeFieldDecoratorBehavior extends CBehavior
{

    /**
     * @param string $attribute
     * @param bool $useLabels
     *
     * @return string
     * @throws CException
     */
    public function getAttributePlaceholder(string $attribute, bool $useLabels = true): string
    {
        if (!$this->isOwnerAllowed()) {
            return '';
        }

        $placeholders = (array)$this->owner->attributePlaceholders();

        if (isset($placeholders[$attribute])) {
            return (string)$placeholders[$attribute];
        }

        if ($useLabels && $label = $this->owner->getAttributeLabel($attribute)) {
            return $label;
        }

        return '';
    }

    /**
     * @param string $attribute
     *
     * @return string
     * @throws CException
     */
    public function getAttributeHelpText(string $attribute): string
    {
        if (!$this->isOwnerAllowed()) {
            return '';
        }

        $helpTexts = (array)$this->owner->attributeHelpTexts();

        return (string)($helpTexts[$attribute] ?? '');
    }

    /**
     * @param string $attribute
     * @param array $htmlOptions
     *
     * @return array
     */
    public function getHtmlOptions(string $attribute, array $htmlOptions = []): array
    {
        if (!$this->isOwnerAllowed()) {
            return $htmlOptions;
        }

        try {
            $htmlOptions = new CMap(CMap::mergeArray($this->_getDefaultHtmlOptions($attribute), $htmlOptions));

            // raise the event for being able to change the html options.
            $this->onHtmlOptionsSetup(new CModelEvent($this, [
                'attribute'     => $attribute,
                'htmlOptions'   => $htmlOptions,
            ]));

            // place for editor instantiation
            if ($htmlOptions->contains('wysiwyg_editor_options')) {
                $wysiwygOptions = (array)$htmlOptions->itemAt('wysiwyg_editor_options');
                $htmlOptions->remove('wysiwyg_editor_options');
                // do the action to register the editor instance
                hooks()->doAction('wysiwyg_editor_instance', $wysiwygOptions);
            }

            return $htmlOptions->toArray();
        } catch (Exception $e) {
        }

        return [];
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onHtmlOptionsSetup(CEvent $event)
    {
        $this->raiseEvent('onHtmlOptionsSetup', $event);
    }

    /**
     * @return bool
     */
    protected function isOwnerAllowed(): bool
    {
        return collect([BaseActiveRecord::class, BaseFormModel::class])->filter(function ($name) {
            return $this->owner instanceof $name;
        })->count() > 0;
    }

    /**
     * @param string $attribute
     *
     * @return array
     * @throws CException
     */
    protected function _getDefaultHtmlOptions(string $attribute): array
    {
        $options = [
            'class'         => 'form-control',
            'placeholder'   => $this->owner->fieldDecorator->getAttributePlaceholder($attribute),
        ];

        if ($helpText = $this->owner->fieldDecorator->getAttributeHelpText($attribute)) {
            $options = array_merge([
                'data-title'        => $this->owner->getAttributeLabel($attribute),
                'data-container'    => 'body',
                'data-toggle'       => 'popover',
                'data-content'      => $helpText,
                'data-placement'    => 'top',
            ], $options);

            $options['class'] .= ' has-help-text';
        }

        return $options;
    }
}
