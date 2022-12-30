<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class QueueName implements QueueNameInterface
{
    /**
     * @var array
     */
    protected $names = [];

    /**
     * QueueName constructor.
     *
     * @param mixed ...$name
     */
    public function __construct(...$name)
    {
        foreach ($name as $n) {
            if (!is_string($n)) {
                throw new InvalidArgumentException('Please provide only strings for queue names');
            }
        }
        $this->names = $name;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->names;
    }
}
