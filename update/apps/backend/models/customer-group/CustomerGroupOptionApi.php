<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerGroupOptionApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.3
 */

/**
 * @param array $attributes
 * @method CustomerGroupModelHandlerBehavior setGroup(CustomerGroup $group)
 */
class CustomerGroupOptionApi extends OptionCustomerApi
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        $behaviors = [
            'handler' => [
                'class'         => 'backend.components.behaviors.CustomerGroupModelHandlerBehavior',
                'categoryName'  => $this->_categoryName,
            ],
        ];
        return CMap::mergeArray($behaviors, parent::behaviors());
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        return $this->asa('handler')->save();
    }
}
