<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailBlacklistFilters
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.7.1
 */

class EmailBlacklistFilters extends EmailBlacklist
{
    /**
     * flag for view list
     */
    const ACTION_VIEW = 'view';

    /**
     * flag for export
     */
    const ACTION_EXPORT = 'export';

    /**
     * flag for delete
     */
    const ACTION_DELETE = 'delete';

    /**
     * @var string $email
     */
    public $email;

    /**
     * @var string
     */
    public $reason;

    /**
     * @var string
     */
    public $date_start;

    /**
     * @var string
     */
    public $date_end;

    /**
     * @var string $action
     */
    public $action;

    /**
     * @var bool
     */
    public $hasSetFilters = false;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['action', 'in', 'range' => array_keys($this->getActionsList())],
            ['email, reason, date_start, date_end', 'safe'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return CMap::mergeArray(parent::attributeLabels(), [
            'action'     => t('email_blacklist', 'Action'),
            'email'      => t('email_blacklist', 'Email'),
            'reason'     => t('email_blacklist', 'Reason'),
            'date_start' => t('email_blacklist', 'Date start'),
            'date_end'   => t('email_blacklist', 'Date end'),
        ]);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        return [
            'email'  => 'name@domain.com',
            'reason' => 'unknown recipient',
            'date_start' => date('Y-m-d', (int)strtotime('-1 week')),
            'date_end'   => date('Y-m-d', (int)strtotime('+1 week')),
        ];
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return EmailBlacklistFilters the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var EmailBlacklistFilters $parent */
        $parent = parent::model($className);

        return $parent;
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        return true;
    }

    /**
     * @return bool|void
     */
    public function afterValidate()
    {
        return true;
    }

    /**
     * @return array
     */
    public function getActionsList(): array
    {
        return [
            self::ACTION_VIEW    => t('list_subscribers', ucfirst(self::ACTION_VIEW)),
            self::ACTION_EXPORT  => t('list_subscribers', ucfirst(self::ACTION_EXPORT)),
            self::ACTION_DELETE  => t('list_subscribers', ucfirst(self::ACTION_DELETE)),
        ];
    }

    /**
     * @return bool
     */
    public function getIsExportAction(): bool
    {
        return (string)$this->action === self::ACTION_EXPORT;
    }

    /**
     * @return bool
     */
    public function getIsDeleteAction(): bool
    {
        return (string)$this->action === self::ACTION_DELETE;
    }

    /**
     * @return Generator
     */
    public function getEmails(): Generator
    {
        $criteria = $this->buildEmailsCriteria();
        $criteria->limit  = 1000;
        $criteria->offset = 0;

        while (true) {
            $models = self::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield $model;
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }

    /**
     * @return CDbCriteria
     */
    public function buildEmailsCriteria(): CDbCriteria
    {
        $criteria = new CDbCriteria();
        if ($this->reason == '[EMPTY]') {
            $criteria->addCondition('t.reason = ""');
        } else {
            $criteria->compare('t.reason', $this->reason, true);
        }

        if (!empty($this->date_start)) {
            $criteria->addCondition('DATE(t.date_added) >= :ds');
            $criteria->params[':ds'] = $this->date_start;
        }

        if (!empty($this->date_end)) {
            $criteria->addCondition('DATE(t.date_added) <= :de');
            $criteria->params[':de'] = $this->date_end;
        }

        if (!empty($this->email)) {
            if (strpos($this->email, ',') !== false) {
                $emails = CommonHelper::getArrayFromString($this->email, ',');
                foreach ($emails as $index => $email) {
                    if (!FilterVarHelper::email($email)) {
                        unset($emails[$index]);
                    }
                }
                if (!empty($emails)) {
                    $criteria->addInCondition('t.email', $emails);
                }
            } else {
                $criteria->compare('t.email', $this->email, true);
            }
        }

        $criteria->order  = 't.email_id DESC';

        return $criteria;
    }

    /**
     * @return CActiveDataProvider
     * @throws CException
     */
    public function getActiveDataProvider(): CActiveDataProvider
    {
        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $this->buildEmailsCriteria(),
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    't.email_id'   => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * @param array $emailIds
     *
     * @return int
     * @throws CException
     */
    public function deleteEmailsByIds(array $emailIds = []): int
    {
        $emailIds = array_filter(array_unique(array_map('intval', $emailIds)));

        $command = db()->createCommand();
        $emails  = $command->select('email')->from('{{email_blacklist}}')->where(['and',
            ['in', 'email_id', $emailIds],
        ])->queryAll();

        if (empty($emails)) {
            return 0;
        }

        $count   = is_countable($emails) ? count($emails) : 0;
        $_emails = [];
        foreach ($emails as $email) {
            $_emails[] = $email['email'];
        }

        $emails = array_chunk($_emails, 100);
        foreach ($emails as $emailsChunk) {

            // delete rom global BL
            $command = db()->createCommand();
            $command->delete('{{email_blacklist}}', ['and',
                ['in', 'email', $emailsChunk],
            ]);

            // delete from customer BL
            $command->delete('{{customer_email_blacklist}}', ['and',
                ['in', 'email', $emailsChunk],
            ]);

            // 1.4.4
            /** @var OptionEmailBlacklist $emailBlacklistOptions */
            $emailBlacklistOptions = container()->get(OptionEmailBlacklist::class);

            if ($emailBlacklistOptions->getReconfirmBlacklistedSubscribersOnBlacklistDelete()) {
                // update list subscribers
                $command = db()->createCommand();
                $command->update('{{list_subscriber}}', ['status' => ListSubscriber::STATUS_CONFIRMED], ['and',
                    ['in', 'email', $emailsChunk],
                    ['in', 'status', [ListSubscriber::STATUS_BLACKLISTED]],
                ]);
            }
        }

        return $count;
    }

    /**
     * @return string
     */
    public function getDatePickerFormat(): string
    {
        return 'yy-mm-dd';
    }

    /**
     * @return string
     */
    public function getDatePickerLanguage(): string
    {
        $language = app()->getLanguage();
        if (strpos($language, '_') === false) {
            return $language;
        }
        $language = explode('_', $language);

        return $language[0];
    }
}
