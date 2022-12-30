<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListDefaultFieldsBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property Lists $owner
 */
class ListDefaultFieldsBehavior extends CActiveRecordBehavior
{
    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function afterSave($event)
    {
        $type = ListFieldType::model()->findByAttributes([
            'identifier' => 'text',
        ]);

        if (empty($type)) {
            return;
        }

        // create the default fields
        $attributesList = [
            [
                'label'    => 'Email',
                'tag'      => 'EMAIL',
                'required' => ListField::TEXT_YES,
            ],
            [
                'label'     => 'First name',
                'tag'       => 'FNAME',
                'required'  => ListField::TEXT_NO,
            ],
            [
                'label'     => 'Last name',
                'tag'       => 'LNAME',
                'required'  => ListField::TEXT_NO,
            ],
        ];

        $sortOrder = 0;

        foreach ($attributesList as $attributes) {
            $model = new ListField();
            $model->attributes  = $attributes;
            $model->list_id     = (int)$this->owner->list_id;
            $model->type_id     = (int)$type->type_id;
            $model->sort_order  = $sortOrder;
            $model->visibility  = ListField::VISIBILITY_VISIBLE;

            $model->save();

            $sortOrder++;
        }

        // now raise an action so any other custom field can be attached
        try {
            $params = new CAttributeCollection([
                'list'          => $this->owner,
                'lastSortOrder' => $sortOrder,
            ]);
            hooks()->doAction('after_list_created_list_default_fields', $params);
        } catch (Exception $e) {
        }
    }
}
