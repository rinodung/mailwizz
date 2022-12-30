<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ConsoleCommandList
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.9.1
 */

/**
 * This is the model class for table "{{console_command}}".
 *
 * The followings are the available columns in table '{{console_command}}':
 * @property integer $command_id
 * @property string $command
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property ConsoleCommandListHistory[] $history
 */
class ConsoleCommandList extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{console_command}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'history' => [self::HAS_MANY, ConsoleCommandListHistory::class, 'command_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'command_id' => t('console', 'ID'),
            'command'    => t('console', 'Command'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ConsoleCommandList the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ConsoleCommandList $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $commandName
     * @param int $seconds
     *
     * @return bool
     */
    public static function isCommandActive(string $commandName, int $seconds = 86400): bool
    {
        $criteria = new CDbCriteria();
        $criteria->with = [];
        $criteria->addCondition(sprintf('t.end_time > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d SECOND))', $seconds));
        $criteria->compare('command.command', $commandName);
        $criteria->with['command'] = [
            'joinType' => 'INNER JOIN',
            'together' => true,
        ];
        $commandHistory = ConsoleCommandListHistory::model()->find($criteria);
        return !empty($commandHistory);
    }

    /**
     * @return array
     */
    public static function getCommandMapCheckInterval(): array
    {
        $commandMap = [
            'send-campaigns'                    => 3600 * 12,
            'queue'                             => 3600 * 12,
            'send-transactional-emails'         => 3600 * 12,
            'bounce-handler'                    => 3600 * 24,
            'feedback-loop-handler'             => 3600 * 24,
            'process-delivery-and-bounce-log'   => 3600 * 24,
            'hourly'                            => 3600 * 24,
            'daily'                             => 3600 * 24 * 2,
        ];

        return (array)hooks()->applyFilters('console_command_list_model_get_command_map_check_interval', $commandMap);
    }
}
