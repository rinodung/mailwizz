<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PageTypeTagsBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property ListPageType|ListPage $owner
 */
class PageTypeTagsBehavior extends CActiveRecordBehavior
{
    /**
     * @param CComponent $owner
     *
     * @return void
     * @throws CException
     */
    public function attach($owner)
    {
        if (!($owner instanceof ListPageType) && !($owner instanceof ListPage)) {
            throw new CException('Invalid behavior owner!');
        }
        parent::attach($owner);
    }

    /**
     * @param CModelEvent $event
     *
     * @return void
     */
    public function beforeSave($event)
    {
        $tags = $this->getAvailableTags();
        $content = html_decode($this->owner->content);

        if (empty($content)) {
            return;
        }

        foreach ($tags as $tag) {
            if (!isset($tag['tag']) || !isset($tag['required']) || !$tag['required']) {
                continue;
            }

            if (!isset($tag['pattern']) && strpos($content, $tag['tag']) === false) {
                $this->owner->addError('content', t('list_pages', 'The following tag is required but was not found in your content: {tag}', [
                    '{tag}' => $tag['tag'],
                ]));
                $event->isValid = false;
                break;
            }
            if (isset($tag['pattern']) && !preg_match($tag['pattern'], $content)) {
                $this->owner->addError('content', t('list_pages', 'The following tag is required but was not found in your content: {tag}', [
                    '{tag}' => $tag['tag'],
                ]));
                $event->isValid = false;
                break;
            }
        }
    }

    /**
     * @param string $slug
     * @param int|null $list_id
     *
     * @return array
     */
    public function getAvailableTags($slug = '', ?int $list_id = null): array
    {
        if ($slug === '') {
            if ($this->owner instanceof ListPageType) {
                $slug = $this->owner->slug;
            } else {
                $slug = $this->owner->type->slug;
            }
        }

        $availableTags = [
            'subscribe-form' => [
                ['tag' => '[LIST_NAME]', 'required' => false],
                ['tag' => '[LIST_DISPLAY_NAME]', 'required' => false],
                ['tag' => '[LIST_INTERNAL_NAME]', 'required' => false],
                ['tag' => '[LIST_UID]', 'required' => false],
                ['tag' => '[LIST_FIELDS]', 'required' => true],
                ['tag' => '[SUBMIT_BUTTON]', 'required' => true],
            ],
            'unsubscribe-form' => [
                ['tag' => '[LIST_NAME]', 'required' => false],
                ['tag' => '[LIST_DISPLAY_NAME]', 'required' => false],
                ['tag' => '[LIST_INTERNAL_NAME]', 'required' => false],
                ['tag' => '[LIST_UID]', 'required' => false],
                ['tag' => '[UNSUBSCRIBE_EMAIL_FIELD]', 'required' => true],
                ['tag' => '[UNSUBSCRIBE_REASON_FIELD]', 'required' => false],
                ['tag' => '[SUBMIT_BUTTON]', 'required' => true],
            ],
            'subscribe-pending' => [
                ['tag' => '[LIST_NAME]', 'required' => false],
                ['tag' => '[LIST_DISPLAY_NAME]', 'required' => false],
                ['tag' => '[LIST_INTERNAL_NAME]', 'required' => false],
                ['tag' => '[LIST_UID]', 'required' => false],
            ],
            'subscribe-confirm' => [
                ['tag' => '[LIST_NAME]', 'required' => false],
                ['tag' => '[LIST_DISPLAY_NAME]', 'required' => false],
                ['tag' => '[LIST_INTERNAL_NAME]', 'required' => false],
                ['tag' => '[LIST_UID]', 'required' => false],
                ['tag' => '[UPDATE_PROFILE_URL]', 'required' => false],
            ],
            'update-profile' => [
                ['tag' => '[LIST_NAME]', 'required' => false],
                ['tag' => '[LIST_DISPLAY_NAME]', 'required' => false],
                ['tag' => '[LIST_INTERNAL_NAME]', 'required' => false],
                ['tag' => '[LIST_UID]', 'required' => false],
                ['tag' => '[LIST_FIELDS]', 'required' => true],
                ['tag' => '[SUBMIT_BUTTON]', 'required' => true],
                ['tag' => '[UNSUBSCRIBE_URL]', 'required' => false],
            ],
            'unsubscribe-confirm' => [
                ['tag' => '[LIST_NAME]', 'required' => false],
                ['tag' => '[LIST_DISPLAY_NAME]', 'required' => false],
                ['tag' => '[LIST_INTERNAL_NAME]', 'required' => false],
                ['tag' => '[LIST_UID]', 'required' => false],
                ['tag' => '[SUBSCRIBE_URL]', 'required' => false],
            ],
            'subscribe-confirm-email' => [
                ['tag' => '[LIST_NAME]', 'required' => false],
                ['tag' => '[LIST_DISPLAY_NAME]', 'required' => false],
                ['tag' => '[LIST_INTERNAL_NAME]', 'required' => false],
                ['tag' => '[LIST_UID]', 'required' => false],
                ['tag' => '[COMPANY_NAME]', 'required' => false],
                ['tag' => '[CURRENT_YEAR]', 'required' => false],
                ['tag' => '[SUBSCRIBE_URL]', 'required' => false],
                // 1.5.3
                ['tag' => '[UPDATE_PROFILE_URL]', 'required' => false],
                ['tag' => '[UNSUBSCRIBE_URL]', 'required' => false],
                ['tag' => '[COMPANY_FULL_ADDRESS]', 'required' => false],
            ],
            'unsubscribe-confirm-email' => [
                ['tag' => '[LIST_NAME]', 'required' => false],
                ['tag' => '[LIST_DISPLAY_NAME]', 'required' => false],
                ['tag' => '[LIST_INTERNAL_NAME]', 'required' => false],
                ['tag' => '[LIST_UID]', 'required' => false],
                ['tag' => '[COMPANY_NAME]', 'required' => false],
                ['tag' => '[CURRENT_YEAR]', 'required' => false],
                ['tag' => '[UNSUBSCRIBE_URL]', 'required' => false],
                // 1.5.3
                ['tag' => '[COMPANY_FULL_ADDRESS]', 'required' => false],
            ],
            'welcome-email' => [
                ['tag' => '[LIST_NAME]', 'required' => false],
                ['tag' => '[LIST_DISPLAY_NAME]', 'required' => false],
                ['tag' => '[LIST_INTERNAL_NAME]', 'required' => false],
                ['tag' => '[LIST_UID]', 'required' => false],
                ['tag' => '[UPDATE_PROFILE_URL]', 'required' => false],
                ['tag' => '[COMPANY_NAME]', 'required' => false],
                ['tag' => '[CURRENT_YEAR]', 'required' => false],
                ['tag' => '[UNSUBSCRIBE_URL]', 'required' => false],
                ['tag' => '[COMPANY_FULL_ADDRESS]', 'required' => false],
            ],
            'subscribe-confirm-approval' => [
                ['tag' => '[LIST_NAME]', 'required' => false],
                ['tag' => '[LIST_DISPLAY_NAME]', 'required' => false],
                ['tag' => '[LIST_INTERNAL_NAME]', 'required' => false],
                ['tag' => '[LIST_UID]', 'required' => false],
            ],
            'subscribe-confirm-approval-email' => [
                ['tag' => '[LIST_NAME]', 'required' => false],
                ['tag' => '[LIST_DISPLAY_NAME]', 'required' => false],
                ['tag' => '[LIST_INTERNAL_NAME]', 'required' => false],
                ['tag' => '[LIST_UID]', 'required' => false],
                ['tag' => '[UPDATE_PROFILE_URL]', 'required' => false],
                ['tag' => '[COMPANY_NAME]', 'required' => false],
                ['tag' => '[CURRENT_YEAR]', 'required' => false],
                ['tag' => '[UNSUBSCRIBE_URL]', 'required' => false],
                ['tag' => '[COMPANY_FULL_ADDRESS]', 'required' => false],
            ],
        ];

        // since 1.3.5.9
        $canLoadCustomFields = array_keys($availableTags);
        $toUnset = ['subscribe-form', 'unsubscribe-form', 'subscribe-pending'];
        $canLoadCustomFields = array_diff($canLoadCustomFields, $toUnset);
        if (!empty($list_id) && in_array($slug, $canLoadCustomFields)) {
            $criteria = new CDbCriteria();
            $criteria->select = 'tag';
            $criteria->compare('list_id', (int)$list_id);
            $criteria->order = 'sort_order ASC';
            $fields = ListField::model()->findAll($criteria);
            foreach ($availableTags as $_slug => $tags) {
                foreach ($fields as $field) {
                    $availableTags[$_slug][] = ['tag' => '[' . $field->tag . ']', 'required' => false];
                }
            }
        }
        //

        return $availableTags[$slug] ?? [];
    }
}
