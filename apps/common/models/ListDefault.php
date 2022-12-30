<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListDefault
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "list_default".
 *
 * The followings are the available columns in table 'list_default':
 * @property integer $list_id
 * @property string $from_name
 * @property string $from_email
 * @property string $reply_to
 * @property string $subject
 *
 * The followings are the available model relations:
 * @property Lists $list
 */
class ListDefault extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_default}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['from_name, reply_to, from_email', 'required'],

            ['from_name', 'length', 'min' => 2, 'max' => 255],
            ['reply_to, from_email', 'length', 'min' => 5, 'max' => 100],
            ['reply_to, from_email', 'email', 'validateIDN' => true],
            ['subject', 'length', 'max'=>255],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'list' => [self::BELONGS_TO, Lists::class, 'list_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'list_id'   => t('lists', 'List'),
            'from_name' => t('lists', 'From name'),
            'from_email'=> t('lists', 'From email'),
            'reply_to'  => t('lists', 'Reply to'),
            'subject'   => t('lists', 'Subject'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListDefault the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListDefault $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'from_name' => t('lists', 'This is the name of the "From" header used in campaigns, use a name that your subscribers will easily recognize, like your website name or company name.'),
            'from_email'=> t('lists', 'This is the email of the "From" header used in campaigns, use a name that your subscribers will easily recognize, containing your website name or company name.'),
            'reply_to'  => t('lists', 'If a user replies to one of your campaigns, the reply will go to this email address. Make sure you check it often.'),
            'subject'   => t('lists', 'Default subject for campaigns, this can be changed for any particular campaign.'),
        ];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'from_name' => t('lists', 'My Super Company INC'),
            'from_email'=> t('lists', 'newsletter@my-super-company.com'),
            'reply_to'  => t('lists', 'reply@my-super-company.com'),
            'subject'   => t('lists', 'Weekly newsletter'),
        ];
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @param Customer $customer
     *
     * @return $this
     */
    public function mergeWithCustomerInfo(Customer $customer): self
    {
        $this->from_name     = $customer->getFullName();
        $this->from_email    = $customer->email;
        $this->reply_to      = $customer->email;

        return $this;
    }
}
