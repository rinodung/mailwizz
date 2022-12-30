<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * BoxHeaderContent
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.8.8
 */

class BoxHeaderContent
{
    /**
     * left side
     */
    const LEFT = 'left';

    /**
     * right side
     */
    const RIGHT = 'right';

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var string
     */
    protected $side = self::RIGHT;

    /**
     * BoxHeaderContent constructor.
     *
     * @param string $side
     */
    public function __construct(string $side = self::RIGHT)
    {
        $this->side = $side;
    }

    /**
     * @param string $side
     *
     * @return BoxHeaderContent
     */
    public static function make(string $side = self::RIGHT): self
    {
        return new self($side);
    }

    /**
     * @param mixed $item
     * @return $this
     */
    public function add($item)
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * @param mixed $item
     * @param mixed $condition
     * @return $this
     */
    public function addIf($item, $condition)
    {
        if ($condition) {
            $this->items[] = $item;
        }
        return $this;
    }

    /**
     * @param bool $return
     *
     * @return array|void
     */
    public function render(bool $return = false)
    {
        $controller = app()->getController();
        $filterName = sprintf('box_header_%s_content', $this->side);

        /** @var array $items */
        $items = (array)hooks()->applyFilters($filterName, $this->items, $controller);
        $items = (array)array_filter(array_map('trim', $items));

        if ($return) {
            return $items;
        }

        echo implode(PHP_EOL, $items);
    }
}
