<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignAbtest
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.29
 */

/**
 * This is the model class for table "{{campaign_abtest}}".
 *
 * The followings are the available columns in table '{{campaign_abtest}}':
 * @property integer $test_id
 * @property integer $campaign_id
 * @property integer $winner_criteria_opens_count
 * @property integer $winner_criteria_days_count
 * @property null|string|CDbExpression $winner_criteria_days_start_date
 * @property string $winner_criteria_operator
 * @property null|string|CDbExpression $winner_opens_count_reached_at
 * @property null|string|CDbExpression $winner_days_count_reached_at
 * @property string $winner_decided_by_opens_count
 * @property string $winner_decided_by_days_count
 * @property string $enabled
 * @property string $status
 * @property null|string|CDbExpression $completed_at
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property CampaignAbtestSubject[] $subjects
 * @property CampaignAbtestSubject[] $activeSubjects
 */
class CampaignAbtest extends ActiveRecord
{
    /**
     * Statuses
     */
    const STATUS_COMPLETE   = 'complete';

    /**
     * Operators
     */
    const OPERATOR_OR = 'or';
    const OPERATOR_AND = 'and';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_abtest}}';
    }

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['winner_criteria_opens_count', 'numerical', 'integerOnly' => true, 'min' => 1, 'max' => 1000000],
            ['winner_criteria_days_count', 'numerical', 'integerOnly' => true, 'min' => 1, 'max' => 3650],

            ['winner_criteria_operator', 'length', 'max' => 3],
            ['winner_criteria_operator', 'in', 'range' => array_keys($this->getOperatorsList())],

            ['enabled', 'in', 'range' => array_keys($this->getYesNoOptions())],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array relational rules.
     * @throws CException
     */
    public function relations()
    {
        $relations = [
            'campaign'          => [self::BELONGS_TO, 'Campaign', 'campaign_id'],
            'subjects'          => [self::HAS_MANY, 'CampaignAbtestSubject', 'test_id'],
            'activeSubjects'    => [self::HAS_MANY, 'CampaignAbtestSubject', 'test_id', 'condition' => 'status = :s', 'params' => [':s' => self::STATUS_ACTIVE]],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = [
            'test_id'                           => t('campaigns', 'Test'),
            'campaign_id'                       => t('campaigns', 'Campaign'),
            'winner_criteria_opens_count'       => t('campaigns', 'Winner criteria - Opens count'),
            'winner_criteria_days_count'        => t('campaigns', 'Winner criteria - Days count'),
            'winner_criteria_days_start_date'   => t('campaigns', 'Winner criteria - Days count start date'),
            'winner_criteria_operator'          => t('campaigns', 'Winner criteria - Operator'),
            'enabled'                           => t('app', 'Enabled'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'winner_criteria_opens_count'   => t('campaigns', 'i.e: {n}', ['{n}' => 1000]),
            'winner_criteria_days_count'    => t('campaigns', 'i.e: {n}', ['{n}' => 30]),
        ];
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $placeholders = [
            'winner_criteria_opens_count'       => t('campaigns', 'Determine the winner based on the number of opens in a campaign.'),
            'winner_criteria_days_count'        => t('campaigns', 'Determine the winner based on the number of days since the campaign has started.'),
            'winner_criteria_operator'          => t('campaigns', 'Decide if all conditions must be met or either of them.'),
            'enabled'                           => t('campaigns', 'Whether this A/B Test is enabled for this campaign'),
        ];
        return CMap::mergeArray($placeholders, parent::attributeHelpTexts());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function afterValidate()
    {
        parent::afterValidate();

        if (!$this->getIsEnabled()) {
            return;
        }

        if ($this->winner_criteria_operator === self::OPERATOR_OR && (empty($this->winner_criteria_days_count) && empty($this->winner_criteria_opens_count))) {
            $this->addError('winner_criteria_days_count', t('campaigns', 'Please provide either the "{attribute1}" or the "{attribute2}"', [
                '{attribute1}' => $this->getAttributeLabel('winner_criteria_days_count'),
                '{attribute2}' => $this->getAttributeLabel('winner_criteria_opens_count'),
            ]));

            $this->addError('winner_criteria_opens_count', t('campaigns', 'Please provide either the "{attribute1}" or the "{attribute2}"', [
                '{attribute1}' => $this->getAttributeLabel('winner_criteria_days_count'),
                '{attribute2}' => $this->getAttributeLabel('winner_criteria_opens_count'),
            ]));
        }

        if ($this->winner_criteria_operator === self::OPERATOR_AND && (empty($this->winner_criteria_days_count) || empty($this->winner_criteria_opens_count))) {
            if (empty($this->winner_criteria_days_count)) {
                $this->addError('winner_criteria_days_count', t('campaigns', 'When using the AND operator, the "{attribute}" is required', [
                    '{attribute}' => $this->getAttributeLabel('winner_criteria_days_count'),
                ]));
            }

            if (empty($this->winner_criteria_opens_count)) {
                $this->addError('winner_criteria_opens_count', t('campaigns', 'When using the AND operator, the "{attribute}" is required', [
                    '{attribute}' => $this->getAttributeLabel('winner_criteria_opens_count'),
                ]));
            }
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function beforeSave()
    {
        if (empty($this->winner_criteria_days_start_date)) {
            $this->winner_criteria_days_start_date = new CDbExpression('NOW()');
        }

        if (empty($this->winner_criteria_operator)) {
            $this->winner_criteria_operator = self::OPERATOR_OR;
        }

        return parent::beforeSave();
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignAbtest the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return array
     */
    public function getOperatorsList(): array
    {
        return [
            self::OPERATOR_OR   => t('campaigns', 'OR'),
            self::OPERATOR_AND  => t('campaigns', 'AND'),
        ];
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return (string)$this->enabled === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getIsComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETE;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getDescription(): string
    {
        $params      = [];
        $description = [];

        if ($this->getIsComplete()) {
            $description[] = 'The winner has been decided on {completed_at}';
            $params['{completed_at}'] = $this->dateTimeFormatter->formatLocalizedDateTime($this->completed_at);
        } else {
            $description[] = 'Decide the winner';
        }

        if (!empty($this->winner_criteria_opens_count)) {
            $description[] = 'after {opens_count} opens';
            $params['{opens_count}'] = $this->winner_criteria_opens_count;
        }

        if (!empty($this->winner_criteria_opens_count) && !empty($this->winner_criteria_days_count)) {
            $description[] = '{operator}';
            $params['{operator}'] = $this->winner_criteria_operator === self::OPERATOR_OR ? t('campaigns', 'OR') : t('campaigns', 'AND');
        }

        if (!empty($this->winner_criteria_days_count)) {
            $description[] = 'after {days_count} days (starting from {days_date_start})';
            $params['{days_count}']      = $this->winner_criteria_days_count;
            $params['{days_date_start}'] = $this->dateTimeFormatter->formatLocalizedDateTime($this->winner_criteria_days_start_date);
        }

        if (
            !empty($this->winner_criteria_opens_count) &&
            !empty($this->winner_criteria_days_count) &&
            $this->winner_criteria_operator === self::OPERATOR_OR
        ) {
            if ($this->getIsComplete()) {
                $description[] = ', whichever came first';
            } else {
                $description[] = ', whichever comes first';
            }
        }

        $description[] = '.';

        $description = implode(' ', $description);
        $description = (string)str_replace([' ,', ' .'], [',', '.'], $description);

        return t('campaigns', $description, $params);
    }
}
