<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ConsoleCommandListHistory
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.9.1
 */

/**
 * This is the model class for table "{{console_command_history}}".
 *
 * The followings are the available columns in table '{{console_command_history}}':
 * @property integer $id
 * @property integer|string $command_id
 * @property string $action
 * @property string $params
 * @property string $start_time
 * @property string $end_time
 * @property integer $start_memory
 * @property integer $end_memory
 * @property string $status
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property ConsoleCommandList $command
 */
class ConsoleCommandListHistory extends ActiveRecord
{
    /**
     * Various statuses
     */
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{console_command_history}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['command_id, action, params, status', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'command' => [self::BELONGS_TO, ConsoleCommandList::class, 'command_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'id'            => t('console', 'ID'),
            'command_id'    => t('console', 'Command'),
            'action'        => t('console', 'Action'),
            'params'        => t('console', 'Params'),
            'start_time'    => t('console', 'Start time'),
            'end_time'      => t('console', 'End time'),
            'start_memory'  => t('console', 'Start memory'),
            'end_memory'    => t('console', 'End memory'),

            //
            'duration'      => t('console', 'Duration'),
            'memoryUsage'   => t('console', 'Memory usage'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     * @throws CException
     */
    public function search()
    {
        $criteria = new CDbCriteria();
        $criteria->with = [];

        $criteria->with['command'] = [
            'together' => true,
            'joinType' => 'INNER JOIN',
        ];

        if (!empty($this->command_id)) {
            $commandId = (string)$this->command_id;
            if (is_numeric($commandId)) {
                $criteria->compare('t.command_id', $commandId);
            } else {
                $criteria->compare('command.command', $commandId, true);
            }
        }

        $criteria->compare('t.action', $this->action, true);
        $criteria->compare('t.params', $this->params, true);
        $criteria->compare('t.status', $this->status);

        $criteria->order = 't.id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    't.id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ConsoleCommandListHistory the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ConsoleCommandListHistory $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function getStatusesList(): array
    {
        return [
            self::STATUS_SUCCESS => t('app', 'Success'),
            self::STATUS_ERROR   => t('app', 'Error'),
        ];
    }

    /**
     * @param int $keep
     * @return bool
     */
    public function deleteOlderRecords(int $keep = 10): bool
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'id';
        $criteria->compare('command_id', (int)$this->command_id);
        $criteria->order = 'id DESC';
        $criteria->limit = (int)$keep;

        $models = self::model()->findAll($criteria);
        $ids    = [];

        foreach ($models as $model) {
            $ids[] = $model->id;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('command_id', (int)$this->command_id);

        if (!empty($ids)) {
            $criteria->addNotInCondition('id', $ids);
        }

        return (bool)$this->deleteAll($criteria);
    }

    /**
     * @return array
     */
    public static function getCommandsListAsOptions(): array
    {
        $options = [];
        $models  = ConsoleCommandList::model()->findAll();
        foreach ($models as $model) {
            $options[$model->command_id] = $model->command;
        }
        return $options;
    }

    /**
     * @return array
     */
    public function getActionAsOptions(): array
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'DISTINCT(action) as action';
        $criteria->group = 'action';

        $options = [];
        $models  = self::model()->findAll($criteria);
        foreach ($models as $model) {
            $options[$model->action] = $model->action;
        }
        return $options;
    }

    /**
     * @return string
     */
    public function getDuration(): string
    {
        return round((float)$this->end_time - (float)$this->start_time, 2) . ' ' . t('console', 'seconds');
    }

    /**
     * @return string
     */
    public function getMemoryUsage(): string
    {
        return CommonHelper::formatBytes((int)$this->end_memory - (int)$this->start_memory);
    }

    /**
     * @return void
     */
    protected function afterSave()
    {
        parent::afterSave();
        $this->deleteOlderRecords();
    }
}
