<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListFieldBuilderTypeTextEmailValidator
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class ListFieldBuilderTypeTextEmailValidator extends CValidator
{
    /**
     * @var ListField
     */
    public $field;

    /**
     * @var ListSubscriber
     */
    public $subscriber;

    /**
     * @param CModel $object
     * @param string $attribute
     *
     * @return void
     * @throws CException
     */
    protected function validateAttribute($object, $attribute)
    {
        // extract the attribute value from it's model object
        $value = $object->$attribute;
        $field = $this->field;

        // since 2.0.18
        if ($object->hasErrors($attribute)) {
            return;
        }

        $blacklisted = EmailBlacklist::isBlacklisted($value, $this->subscriber, $this->subscriber->list->customer, ['checkZone' => EmailBlacklist::CHECK_ZONE_LIST_SUBSCRIBE]);
        if (!empty($blacklisted)) {
            // temp flag since 1.3.5.9
            app_param_set('validationSubscriberAlreadyExists', true);
            $this->addError($object, $attribute, t('list_fields', 'This email address is blacklisted!'));
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('email', $value);
        $criteria->compare('list_id', (int)$this->subscriber->list_id);
        $criteria->addCondition('subscriber_id != :sid');
        $criteria->params[':sid'] = (int)$this->subscriber->subscriber_id;

        /** @var ListSubscriber|null $subscriberExists */
        $subscriberExists = ListSubscriber::model()->find($criteria);

        if (!empty($subscriberExists)) {

            // temp flag since 1.3.5.9
            app_param_set('validationSubscriberAlreadyExists', true);

            // 1.3.9.8
            app_param_set('validationSubscriberAlreadyExistsSubscriber', $subscriberExists);

            $this->addError($object, $attribute, t('list_fields', 'This email address is already registered in this list!'));
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->select = 't.field_id';
        $criteria->compare('t.list_id', (int)$field->list_id);
        $criteria->compare('t.type_id', (int)$field->type_id);
        $criteria->compare('t.field_id', (int)$field->field_id);
        $criteria->compare('t.tag', 'EMAIL');

        $criteria->with = [
            'value' => [
                'select'    => false,
                'joinType'  => 'INNER JOIN',
                'together'  => true,
                'condition' => '`value`.`subscriber_id` != :sid AND `value`.`value` = :val',
                'params'    => [
                    ':sid'  => (int)$this->subscriber->subscriber_id,
                    ':val'  => $value,
                ],
        ], ];

        $model = ListField::model()->find($criteria);

        if (empty($model)) {
            return;
        }

        // temp flag since 1.3.5.9
        app_param_set('validationSubscriberAlreadyExists', true);
        $this->addError($object, $attribute, t('list_fields', 'This email address is already registered in this list!'));
    }
}
