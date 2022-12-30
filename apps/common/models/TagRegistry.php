<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UserAutoLoginToken
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "tag_registry".
 *
 * The followings are the available columns in table 'tag_registry':
 * @property integer $tag_id
 * @property string $tag
 * @property string $description
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 */
class TagRegistry extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{tag_registry}}';
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return TagRegistry the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var TagRegistry $model */
        $model = parent::model($className);

        return $model;
    }
}
