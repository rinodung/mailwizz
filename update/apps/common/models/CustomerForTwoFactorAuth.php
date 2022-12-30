<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Class CustomerForTwoFactorAuth
 */
class CustomerForTwoFactorAuth extends Customer
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['twofa_enabled', 'required'],
            ['twofa_enabled', 'in', 'range' => array_keys($this->getYesNoOptions())],
        ];
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerForTwoFactorAuth the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerForTwoFactorAuth $parent */
        $parent = parent::model($className);

        return $parent;
    }
}
