<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Class UserForTwoFactorAuth
 */
class UserForTwoFactorAuth extends User
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{user}}';
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
     * @return UserForTwoFactorAuth the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var UserForTwoFactorAuth $parent */
        $parent = parent::model($className);

        return $parent;
    }
}
