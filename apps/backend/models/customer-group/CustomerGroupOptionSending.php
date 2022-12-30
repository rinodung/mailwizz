<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerGroupOptionSending
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

/**
 * @param array $attributes
 * @method CustomerGroupModelHandlerBehavior setGroup(CustomerGroup $group)
 */
class CustomerGroupOptionSending extends OptionCustomerSending
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

    /**
     * @return array
     */
    public function getGroupsList(): array
    {
        $groups = parent::getGroupsList();
        if ($group = $this->asa('handler')->getGroup()) {
            foreach ($groups as $groupId => $name) {
                if ($groupId == $group->group_id) {
                    unset($groups[$groupId]);
                    break;
                }
            }
        }
        return $groups;
    }
}
