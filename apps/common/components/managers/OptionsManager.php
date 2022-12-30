<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionsManager
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class OptionsManager extends CApplicationComponent
{
    const DEFAULT_CATEGORY = 'misc';

    /**
     * @var int
     */
    public $cacheTtl = 60;

    /**
     * @var array
     */
    protected $_options = [];

    /**
     * @var array
     */
    protected $_categories = [];

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     * @return array
     */
    public function getCategories(): array
    {
        return $this->_categories;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return OptionsManager
     */
    public function set(string $key, $value): self
    {
        if (($existingValue = $this->get($key)) === $value || $value === null) {
            return $this;
        }

        $_key = $key;
        [$category, $key] = $this->getCategoryAndKey($key);
        $command = db()->createCommand();

        if ($this->get($_key) !== null) {
            $command->update('{{option}}', [
                'value'         => is_string($value) ? $value : serialize($value),
                'is_serialized' => (int)(!is_string($value)),
                'last_updated'  => MW_DATETIME_NOW,
            ], '`category` = :c AND `key`=:k', [':c' => $category, ':k' => $key]);
        } else {
            $command->insert('{{option}}', [
                'category'      => $category,
                'key'           => $key,
                'value'         => is_string($value) ? $value : serialize($value),
                'is_serialized' => (int)(!is_string($value)),
                'date_added'    => MW_DATETIME_NOW,
                'last_updated'  => MW_DATETIME_NOW,
            ]);
        }
        $this->_options[$_key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function get(string $key, $defaultValue = null)
    {
        // simple keys are set with default category, we need to retrieve them the same.
        $key = implode('.', $this->getCategoryAndKey($key));

        $this->loadCategory($key);
        return $this->_options[$key] ?? $defaultValue;
    }

    /**
     * @param string $key
     * @param mixed $defaultValue
     *
     * @return bool
     */
    public function isTrue(string $key, $defaultValue = null): bool
    {
        $value = $this->get($key, $defaultValue);
        if (is_null($value)) {
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value > 0;
        }

        if (is_array($value)) {
            return $value != [];
        }

        if (is_object($value)) {
            return $value != (object)[];
        }

        if (!is_string($value)) {
            return false;
        }

        $value = strtolower((string)$value);
        return $value === 'yes' || $value === 'on';
    }

    /**
     * @param string $key
     * @param null $defaultValue
     *
     * @return bool
     */
    public function isFalse(string $key, $defaultValue = null): bool
    {
        return !$this->isTrue($key, $defaultValue);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function remove(string $key): bool
    {
        if (isset($this->_options[$key])) {
            unset($this->_options[$key]);
        }

        [$category, $key] = $this->getCategoryAndKey($key);

        db()->createCommand()->delete('{{option}}', '`category` = :c AND `key` = :k', [':c' => $category, ':k' => $key]);
        return true;
    }

    /**
     * @param string $category
     *
     * @return bool
     */
    public function removeCategory(string $category): bool
    {
        if (isset($this->_categories[$category])) {
            unset($this->_categories[$category]);
        }

        db()->createCommand()->delete('{{option}}', '`category` = :c', [':c' => $category]);
        // added in 1.3.5.4
        db()->createCommand()->delete('{{option}}', '`category` LIKE :c', [':c' => $category . '%']);

        foreach ($this->_options as $key => $value) {
            if (strpos($key, $category) === 0) {
                unset($this->_options[$key]);
            }
        }

        return true;
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function getCategoryAndKey(string $key): array
    {
        $category = self::DEFAULT_CATEGORY;

        if (strpos($key, '.') !== false) {
            $parts = explode('.', $key);
            $key = array_pop($parts);
            $category = implode('.', $parts);
        }

        return [$category, $key];
    }

    /**
     * @return $this
     */
    public function resetLoaded(): self
    {
        $this->_options    = [];
        $this->_categories = [];
        return $this;
    }

    /**
     * @param string $key
     *
     * @return OptionsManager
     */
    protected function loadCategory(string $key): self
    {
        [$category, $key] = $this->getCategoryAndKey($key);

        if (isset($this->_categories[$category])) {
            return $this;
        }

        // NOTE: add caching but be aware of the CLI problems when the cache does not invalidate!
        try {
            $command = db()->createCommand('SELECT `category`, `key`, `value`, `is_serialized` FROM `{{option}}` WHERE `category` = :c');
            $rows = $command->queryAll(true, [':c' => $category]);
        } catch (Exception $e) {
            $rows = [];
        }

        foreach ($rows as $row) {
            $this->_options[$row['category'] . '.' . $row['key']] = !$row['is_serialized'] ? $row['value'] : unserialize($row['value']);
        }

        $this->_categories[$category] = true;

        return $this;
    }
}
