<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PageTypeEmailSubjectBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.8.8
 */

/**
 * @property ListPageType|ListPage $owner
 */
class PageTypeEmailSubjectBehavior extends CActiveRecordBehavior
{
    /**
     * @var string
     */
    public $email_subject = '';

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
     * @throws CException
     */
    public function beforeSave($event)
    {
        if ($this->getCanHaveEmailSubject()) {
            $this->owner->modelMetaData->getModelMetaData()->add('email_subject', $this->email_subject);
        }
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function afterFind($event)
    {
        if ($this->getCanHaveEmailSubject()) {
            if (!($this->email_subject = $this->owner->modelMetaData->getModelMetaData()->itemAt('email_subject'))) {
                $this->email_subject = $this->getDefaultEmailSubject();
            }
        }
    }

    /**
     * @return bool
     */
    public function getCanHaveEmailSubject(): bool
    {
        return stripos($this->getTheSlug(), 'email') !== false;
    }

    /**
     * @return string
     */
    public function getDefaultEmailSubject(): string
    {
        if (!$this->getCanHaveEmailSubject()) {
            return '';
        }

        if ($this->getTheSlug() == 'subscribe-confirm-email') {
            return t('list_subscribers', 'Please confirm your subscription');
        }

        if ($this->getTheSlug() == 'unsubscribe-confirm-email') {
            return t('list_subscribers', 'Please confirm your unsubscription');
        }

        if ($this->getTheSlug() == 'welcome-email') {
            return t('list_subscribers', 'Thank you for your subscription!');
        }

        if ($this->getTheSlug() == 'subscribe-confirm-approval-email') {
            return t('list_subscribers', 'Your subscription has been approved!');
        }

        return '';
    }

    /**
     * @return string
     */
    public function getTheSlug(): string
    {
        if ($this->owner instanceof ListPage) {
            if (empty($this->owner->type)) {
                return '';
            }
            return (string)$this->owner->type->slug;
        }

        /** @var ListPageType $owner */
        $owner = $this->owner;

        return (string)$owner->slug;
    }
}
