<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * HooksManager
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class HooksManager extends CApplicationComponent
{
    /**
     * @var CMap
     */
    private $_actionsMap;

    /**
     * @var CMap
     */
    private $_filtersMap;

    /**
     * @param string $tag
     * @param callable $callback
     * @param int $priority
     *
     * @return HooksManager
     */
    public function addAction(string $tag, callable $callback, int $priority = 10): self
    {
        if (!$this->getActionsMap()->contains($tag)) {
            try {
                $this->getActionsMap()->add($tag, new CList());
            } catch (Exception $e) {
            }
        }

        if ($this->hasAction($tag, $callback)) {
            return $this;
        }

        $this->getActionsMap()->itemAt($tag)->add([
            'callback'    => $callback,
            'priority'    => (int)$priority,
        ]);

        return $this;
    }

    /**
     * @param string $tag
     * @param mixed $arg
     *
     * @return HooksManager
     */
    public function doAction(string $tag, $arg = null): self
    {
        if (!$this->getActionsMap()->contains($tag)) {
            return $this;
        }

        $actions    = $this->getActionsMap()->itemAt($tag)->toArray();
        $sort       = [];
        $callbacks  = [];
        $start      = 0;

        // array_multisort will trigger: Fatal error: Nesting level too deep - recursive dependency?
        // if there are too many callbacks with big objects, so we need to work around that!

        foreach ($actions as $index => $action) {
            $sort[] = (int)$action['priority'];

            // generate a reference key, keep it a string
            $key = 'key' . ($start++);

            // store the callback
            $callbacks[$key] = $action['callback'];

            // also store a key reference in the action
            $actions[$index]['key'] = $key;

            // unset the callback from the action
            unset($actions[$index]['callback']);
        }

        // now multisort will loop through an easy array.
        array_multisort($sort, $actions);

        // restore all the callbacks to their action.
        foreach ($callbacks as $key => $callback) {
            foreach ($actions as $actionIndex => $action) {
                if ($action['key'] === $key) {
                    $actions[$actionIndex]['callback'] = $callback;
                    break;
                }
            }
        }

        // destroy all the old refrences to the callbacks.
        unset($callbacks);

        $args = func_get_args();
        $args = array_slice($args, 2);
        $args = array_values($args);
        array_unshift($args, $arg);

        foreach ($actions as $action) {
            call_user_func_array($action['callback'], $args);
        }

        Yii::trace('Did ' . $tag . ' action, ' . (is_countable($actions) ? count($actions) : 0) . ' action hooks were triggered!');

        return $this;
    }

    /**
     * @param string $tag
     * @param callable $callback
     *
     * @return bool
     */
    public function hasAction(string $tag, callable $callback): bool
    {
        if (!$this->getActionsMap()->contains($tag)) {
            return false;
        }

        $actions = $this->getActionsMap()->itemAt($tag)->toArray();
        foreach ($actions as $action) {
            if ($action['callback'] === $callback) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $tag
     *
     * @return bool
     */
    public function hasActions(string $tag): bool
    {
        return $this->getActionsCount($tag) > 0;
    }

    /**
     * @param string $tag
     *
     * @return int
     */
    public function getActionsCount(string $tag): int
    {
        if (!$this->getActionsMap()->contains($tag)) {
            return 0;
        }
        return $this->getActionsMap()->itemAt($tag)->getCount();
    }

    /**
     * @param string $tag
     * @param callable $callback
     *
     * @return bool
     */
    public function removeAction(string $tag, callable $callback): bool
    {
        if (!$this->getActionsMap()->contains($tag)) {
            return false;
        }

        $actions = $this->getActionsMap()->itemAt($tag)->toArray();
        foreach ($actions as $index => $action) {
            if ($action['callback'] === $callback) {
                $this->getActionsMap()->itemAt($tag)->removeAt($index);
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $tag
     *
     * @return bool
     */
    public function removeAllActions(string $tag): bool
    {
        if (!$this->getActionsMap()->contains($tag)) {
            return false;
        }

        $this->getActionsMap()->itemAt($tag)->clear();

        return true;
    }

    /**
     * @param string $tag
     * @param callable $callback
     * @param int $priority
     *
     * @return HooksManager
     */
    public function addFilter(string $tag, callable $callback, int $priority = 10): self
    {
        if (!$this->getFiltersMap()->contains($tag)) {
            try {
                $this->getFiltersMap()->add($tag, new CList());
            } catch (Exception $e) {
            }
        }

        if ($this->hasFilter($tag, $callback)) {
            return $this;
        }

        $this->getFiltersMap()->itemAt($tag)->add([
            'callback'    => $callback,
            'priority'    => (int)$priority,
        ]);

        return $this;
    }

    /**
     * @param string $tag
     * @param mixed $arg
     *
     * @return mixed
     */
    public function applyFilters(string $tag, $arg)
    {
        if (!$this->getFiltersMap()->contains($tag)) {
            return $arg;
        }

        $filters    = $this->getFiltersMap()->itemAt($tag)->toArray();
        $sort       = [];
        $callbacks  = [];
        $start      = 0;

        // array_multisort will trigger: Fatal error: Nesting level too deep - recursive dependency?
        // if there are too many callbacks with big objects, so we need to work around that!

        foreach ($filters as $index => $filter) {
            $sort[] = (int)$filter['priority'];

            // generate a reference key, keep it a string
            $key = 'key' . ($start++);

            // store the callback
            $callbacks[$key] = $filter['callback'];

            // also store a key reference in the filter
            $filters[$index]['key'] = $key;

            // unset the callback from the filter
            unset($filters[$index]['callback']);
        }

        // now multisort will loop through an easy array.
        array_multisort($sort, $filters);

        // restore all the callbacks to their filters.
        foreach ($callbacks as $key => $callback) {
            foreach ($filters as $filterIndex => $filter) {
                if ($filter['key'] === $key) {
                    $filters[$filterIndex]['callback'] = $callback;
                    break;
                }
            }
        }

        // destroy all the old refrences to the callbacks.
        unset($callbacks);

        $args = func_get_args();
        $args = array_slice($args, 2);
        $args = array_values($args);
        array_unshift($args, $arg);

        foreach ($filters as $filter) {
            $arg = call_user_func_array($filter['callback'], $args);

            // remove old arg value
            array_shift($args);

            // add the new one
            array_unshift($args, $arg);
        }

        Yii::trace('Did ' . $tag . ' filter, ' . (is_countable($filters) ? count($filters) : 0) . ' filter hooks were triggered!');

        return $arg;
    }

    /**
     * @param string $tag
     * @param callable $callback
     *
     * @return bool
     */
    public function hasFilter(string $tag, callable $callback): bool
    {
        if (!$this->getFiltersMap()->contains($tag)) {
            return false;
        }

        $filters = $this->getFiltersMap()->itemAt($tag)->toArray();
        foreach ($filters as $filter) {
            if ($filter['callback'] === $callback) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $tag
     *
     * @return bool
     */
    public function hasFilters(string $tag): bool
    {
        return $this->getFiltersCount($tag) > 0;
    }

    /**
     * @param mixed $tag
     *
     * @return int
     */
    public function getFiltersCount($tag)
    {
        if (!$this->getFiltersMap()->contains($tag)) {
            return 0;
        }
        return $this->getFiltersMap()->itemAt($tag)->getCount();
    }

    /**
     * @param string $tag
     * @param callable $callback
     *
     * @return bool
     */
    public function removeFilter(string $tag, callable $callback): bool
    {
        if (!$this->getFiltersMap()->contains($tag)) {
            return false;
        }

        $filters = $this->getFiltersMap()->itemAt($tag)->toArray();
        foreach ($filters as $index => $filter) {
            if ($filter['callback'] === $callback) {
                $this->getFiltersMap()->itemAt($tag)->removeAt($index);
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $tag
     *
     * @return bool
     */
    public function removeAllFilters(string $tag): bool
    {
        if (!$this->getFiltersMap()->contains($tag)) {
            return false;
        }

        $this->getFiltersMap()->itemAt($tag)->clear();

        return true;
    }

    /**
     * @return CMap
     */
    protected function getActionsMap()
    {
        if (!($this->_actionsMap instanceof CMap)) {
            try {
                $this->_actionsMap = new CMap();
            } catch (Exception $e) {
            }
        }

        return $this->_actionsMap;
    }

    /**
     * @return CMap
     */
    protected function getFiltersMap(): CMap
    {
        if (!($this->_filtersMap instanceof CMap)) {
            try {
                $this->_filtersMap = new CMap();
            } catch (Exception $e) {
            }
        }

        return $this->_filtersMap;
    }
}
