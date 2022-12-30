<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class SearchExtBehaviorSurveysController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'title'     => t('surveys', 'Surveys'),
                'keywords'  => [
                    'survey', 'responders', 'segments', 'respond', 'custom fields', 'custom field', 'survey fields',
                ],
                'skip'              => [$this, '_indexSkip'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
            'create' => [
                'keywords'  => ['survey', 'create survey', 'survey create', 'responder'],
                'skip'      => [$this, '_createSkip'],
            ],
        ];
    }

    /**
     * @param SearchExtSearchItem $item
     *
     * @return bool
     */
    public function _indexSkip(SearchExtSearchItem $item)
    {
        if (apps()->isAppName('customer')) {
            if (is_subaccount() && !subaccount()->canManageSurveys()) {
                return true;
            }

            return false;
        }

        /** @var User $user */
        $user = user()->getModel();
        return !$user->hasRouteAccess($item->route);
    }

    /**
     * @param string $term
     * @param SearchExtSearchItem|null $parent
     *
     * @return array
     */
    public function _indexChildrenGenerator(string $term, ?SearchExtSearchItem $parent = null): array
    {
        $criteria = new CDbCriteria();

        if (apps()->isAppName('customer')) {
            $criteria->addCondition('customer_id = :cid');
            $criteria->params[':cid'] = (int)customer()->getId();
        }

        $criteria->addCondition('(name LIKE :term OR display_name LIKE :term OR description LIKE :term)');
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->order = 'survey_id DESC';
        $criteria->limit = 5;

        return SurveyCollection::findAll($criteria)->map(function (Survey $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->name;
            $item->url   = createUrl('surveys/overview', ['survey_uid' => $model->survey_uid]);
            $item->score++;

            if (apps()->isAppName('customer')) {
                $item->buttons = [
                    CHtml::link(IconHelper::make('update'), ['surveys/update', 'survey_uid' => $model->survey_uid], ['title' => t('surveys', 'Update'), 'class' => 'btn btn-xs btn-primary btn-flat']),
                    CHtml::link(IconHelper::make('fa-users'), ['survey_responders/index', 'survey_uid' => $model->survey_uid], ['title' => t('surveys', 'Responders'), 'class' => 'btn btn-xs btn-primary btn-flat']),
                    CHtml::link(IconHelper::make('fa-list'), ['survey_fields/index', 'survey_uid' => $model->survey_uid], ['title' => t('surveys', 'Custom fields'), 'class' => 'btn btn-xs btn-primary btn-flat']),
                ];
            }

            return $item->getFields();
        })->all();
    }

    /**
     * @return bool
     */
    public function _createSkip(): bool
    {
        if (apps()->isAppName('customer')) {

            /** @var Customer $customer */
            $customer = customer()->getModel();
            return (int)$customer->getGroupOption('surveys.max_surveys', -1) == 0;
        }

        return true;
    }
}
