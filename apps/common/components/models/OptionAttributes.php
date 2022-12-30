<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionAttributes
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

/**
 * @param array $attributes
 */
abstract class OptionAttributes extends FormModel
{
    /**
     * @return string
     */
    abstract public function getCategoryName(): string;

    /**
     * @param string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getOption(string $key, $defaultValue = null)
    {
        return options()->get($this->getCategoryName() . '.' . $key, $defaultValue);
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function setOption(string $key, $value)
    {
        options()->set($this->getCategoryName() . '.' . $key, $value);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function removeOption(string $key): bool
    {
        return options()->remove($this->getCategoryName() . '.' . $key);
    }

    /**
     * Refresh the model properties
     *
     * @return void
     */
    public function refresh(): void
    {
        foreach ($this->getAttributes() as $attributeName => $attributeValue) {
            $this->$attributeName = $this->getOption($attributeName, $this->$attributeName);
        }
    }

    /**
     * @return bool
     */
    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        return $this->saveAttributes($this->getAttributes());
    }

    /**
     * @param array $attributes
     *
     * @return bool
     */
    public function saveAttributes(array $attributes): bool
    {
        foreach ($attributes as $attributeName => $attributeValue) {
            $this->setAttribute($attributeName, $attributeValue);
        }

        return true;
    }

    /**
     * @param string $attributeName
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getAttribute(string $attributeName, $defaultValue = null)
    {
        if (property_exists($this, $attributeName)) {
            return $this->$attributeName ?? $defaultValue;
        }

        return $this->getOption($attributeName, $defaultValue);
    }

    /**
     * @param string $attributeName
     * @param mixed $attributeValue
     *
     * @return void
     */
    public function setAttribute(string $attributeName, $attributeValue)
    {
        if (property_exists($this, $attributeName)) {
            $this->$attributeName = $attributeValue;
        }

        $this->setOption($attributeName, $attributeValue);
    }

    /**
     * @param mixed $names
     *
     * @return void
     */
    public function unsetAttributes($names = null)
    {
        if ($names === null) {
            $names = $this->attributeNames();
        }

        if (is_array($names)) {
            /** @var string $name */
            foreach ($names as $name) {
                $this->unsetAttribute($name);
            }
        }
    }

    /**
     * @param string $attributeName
     *
     * @return bool
     */
    public function unsetAttribute(string $attributeName): bool
    {
        if (property_exists($this, $attributeName)) {
            $this->$attributeName = null;
        }

        // TODO - decide what to do with this. Issue is that we are removing from the db, prior to have everything ok
        //return $this->removeOption($attributeName);

        return true;
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->refresh();
    }
}
