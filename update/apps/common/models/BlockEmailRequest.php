<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * BlockEmailRequest
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.7.3
 */

/**
 * This is the model class for table "block_email_request".
 *
 * The followings are the available columns in table 'block_email_request':
 * @property integer $email_id
 * @property string $email
 * @property string $ip_address
 * @property string $user_agent
 * @property string $confirmation_key
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 */
class BlockEmailRequest extends ActiveRecord
{
    /**
     * Flag
     */
    const STATUS_CONFIRMED = 'confirmed';

    /**
     * Flag
     */
    const STATUS_UNCONFIRMED = 'unconfirmed';

    /**
     * Flag
     */
    const BULK_ACTION_CONFIRM = 'confirm-block';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{block_email_request}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['email', 'required'],
            ['email', 'length', 'max' => 150],
            ['email', 'email', 'validateIDN' => true],

            ['email, ip_address, user_agent', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'email_id'      => $this->t('Email'),
            'email'         => $this->t('Email'),
            'ip_address'    => $this->t('Ip address'),
            'user_agent'    => $this->t('User agent'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return CActiveDataProvider
     * @throws CException
     */
    public function search()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('email', $this->email, true);
        $criteria->compare('ip_address', $this->ip_address, true);
        $criteria->compare('user_agent', $this->user_agent, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    'email_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return BlockEmailRequest the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var BlockEmailRequest $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function getTranslationCategory(): string
    {
        return 'email_blacklist';
    }

    /**
     * @throws CDbException
     * @throws CException
     */
    public function block(): void
    {
        if (empty($this->email)) {
            return;
        }

        $blacklist = EmailBlacklist::model()->findByAttributes(['email' => $this->email]);
        if (empty($blacklist)) {
            EmailBlacklist::addToBlacklist($this->email, 'Block email request!');
        }

        $this->saveStatus(self::STATUS_CONFIRMED);

        db()->createCommand('UPDATE {{list_subscriber}} SET `status` = :st1 WHERE email = :em AND `status` = :st2')
            ->execute([
                ':st1' => ListSubscriber::STATUS_BLACKLISTED,
                ':st2' => ListSubscriber::STATUS_CONFIRMED,
                ':em'  => $this->email,
            ]);
    }

    /**
     * @inheritDoc
     */
    public function getStatusesList(): array
    {
        return [
            self::STATUS_UNCONFIRMED => $this->t('Unconfirmed'),
            self::STATUS_CONFIRMED   => $this->t('Confirmed'),
        ];
    }

    /**
     * @return bool
     */
    public function getIsConfirmed(): bool
    {
        return $this->getStatusIs(self::STATUS_CONFIRMED);
    }

    /**
     * @return array
     */
    public function getBulkActionsList(): array
    {
        return [
            self::BULK_ACTION_CONFIRM => $this->t('Confirm'),
        ];
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (empty($this->confirmation_key)) {
            $this->confirmation_key = StringHelper::randomSha1();
        }
        return parent::beforeSave();
    }
}
