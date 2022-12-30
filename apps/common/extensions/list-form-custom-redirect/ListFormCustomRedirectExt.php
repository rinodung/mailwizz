<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List form custom redirect extension
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class ListFormCustomRedirectExt extends ExtensionInit
{
    /**
     * @var string
     */
    public $name = 'List form custom redirect';

    /**
     * @var string
     */
    public $description = 'Will add custom redirect for list forms where applicable.';

    /**
     * @var string
     */
    public $version = '2.0.0';

    /**
     * @var string
     */
    public $minAppVersion = '2.0.0';

    /**
     * @var string
     */
    public $author = 'MailWizz Development Team';

    /**
     * @var string
     */
    public $website = 'https://www.mailwizz.com/';

    /**
     * @var string
     */
    public $email = 'support@mailwizz.com';

    /**
     * @var array
     */
    public $allowedApps = ['customer', 'frontend'];

    /**
     * @var array
     */
    public $actionToPageType = [
        'subscribe_pending'   => 'subscribe-pending',
        'subscribe_confirm'   => 'subscribe-confirm',
        'unsubscribe_confirm' => 'unsubscribe-confirm',
    ];

    /**
     * @var bool
     */
    protected $_canBeDeleted = false;

    /**
     * @var bool
     */
    protected $_canBeDisabled = true;

    /**
     * @inheritDoc
     */
    public function run()
    {
        $this->importClasses('common.models.*');

        if ($this->isAppName('customer')) {
            hooks()->addAction('after_active_form_fields', [$this, '_insertCustomerField']);
            hooks()->addAction('controller_action_save_data', [$this, '_saveCustomerData']);
        } elseif ($this->isAppName('frontend')) {
            hooks()->addAction('frontend_controller_lists_before_render', [$this, '_redirectIfNeeded']);
        }

        hooks()->addFilter('models_lists_after_copy_list', [$this, '_modelsListsAfterCopyList']);
    }

    /**
     * @inheritDoc
     */
    public function beforeEnable()
    {
        db()->createCommand('SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0')->execute();
        db()->createCommand('SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0')->execute();
        db()->createCommand('SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE=""')->execute();

        db()->createCommand('
        CREATE TABLE IF NOT EXISTS `{{list_form_custom_redirect}}` (
          `redirect_id` INT NOT NULL AUTO_INCREMENT,
          `list_id` INT(11) NOT NULL,
          `type_id` INT(11) NOT NULL,
          `url` TEXT NOT NULL,
          `timeout` INT NOT NULL DEFAULT 0,
          `date_added` DATETIME NOT NULL,
          `last_updated` DATETIME NOT NULL,
          PRIMARY KEY (`redirect_id`),
          INDEX `fk_list_form_custom_redirect_list1_idx` (`list_id` ASC),
          INDEX `fk_list_form_custom_redirect_list_page_type1_idx` (`type_id` ASC),
          CONSTRAINT `fk_list_form_custom_redirect_list1`
            FOREIGN KEY (`list_id`)
            REFERENCES `{{list}}` (`list_id`)
            ON DELETE CASCADE
            ON UPDATE NO ACTION,
          CONSTRAINT `fk_list_form_custom_redirect_list_page_type1`
            FOREIGN KEY (`type_id`)
            REFERENCES `{{list_page_type}}` (`type_id`)
            ON DELETE CASCADE
            ON UPDATE NO ACTION)
        ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ')->execute();

        db()->createCommand('SET SQL_MODE=@OLD_SQL_MODE')->execute();
        db()->createCommand('SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS')->execute();
        db()->createCommand('SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS')->execute();

        return true;
    }

    /**
     * @inheritDoc
     */
    public function beforeDisable()
    {
        db()->createCommand('SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0')->execute();
        db()->createCommand('SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0')->execute();
        db()->createCommand('SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE=""')->execute();

        db()->createCommand('DROP TABLE IF EXISTS `{{list_form_custom_redirect}}`')->execute();

        db()->createCommand('SET SQL_MODE=@OLD_SQL_MODE')->execute();
        db()->createCommand('SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS')->execute();
        db()->createCommand('SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS')->execute();

        return true;
    }

    /**
     * @param CAttributeCollection $collection
     *
     * @return void
     * @throws CException
     */
    public function _insertCustomerField(CAttributeCollection $collection)
    {
        /** @var Controller $controller */
        $controller = $collection->itemAt('controller');

        if ($controller->getId() != 'list_page' || $controller->getAction()->getId() != 'index') {
            return;
        }

        /** @var Lists $list */
        $list = $controller->getData('list');

        /** @var ListPageType $pageType */
        $pageType = $controller->getData('pageType');

        if (!in_array($pageType->slug, array_values($this->actionToPageType))) {
            return;
        }

        if (!$this->getData('model')) {
            $model = ListFormCustomRedirect::model()->findByAttributes([
                'list_id'   => (int)$list->list_id,
                'type_id'   => (int)$pageType->type_id,
            ]);

            if (empty($model)) {
                $model = new ListFormCustomRedirect();
            }

            $this->setData('model', $model);
        }

        /** @var ListFormCustomRedirect $model */
        $model = $this->getData('model');
        $model->list_id = (int)$list->list_id;
        $model->type_id = (int)$pageType->type_id;

        $form = $collection->itemAt('form');

        $controller->renderInternal(dirname(__FILE__) . '/customer/views/_form.php', compact('model', 'form'));
    }

    /**
     * @param CAttributeCollection $collection
     *
     * @return void
     * @throws CException
     */
    public function _saveCustomerData(CAttributeCollection $collection)
    {
        /** @var Controller $controller */
        $controller = $collection->itemAt('controller');

        if ($controller->getId() != 'list_page' || $controller->getAction()->getId() != 'index') {
            return;
        }

        /** @var Lists $list */
        $list = $collection->itemAt('list');

        /** @var ListPageType $pageType */
        $pageType = $collection->itemAt('pageType');

        if (!in_array($pageType->slug, array_values($this->actionToPageType))) {
            return;
        }

        if (!$collection->itemAt('success')) {
            return;
        }

        if (!$this->getData('model')) {
            $model = ListFormCustomRedirect::model()->findByAttributes([
                'list_id' => (int)$list->list_id,
                'type_id' => (int)$pageType->type_id,
            ]);

            if (empty($model)) {
                $model = new ListFormCustomRedirect();
            }

            $this->setData('model', $model);
        }

        /** @var ListFormCustomRedirect $model */
        $model = $this->getData('model');

        /** @var array $post */
        $post = (array)request()->getOriginalPost('', []);
        $url  = isset($post[$model->getModelName()]['url']) ? (string)str_replace('&amp;', '&', strip_tags((string)ioFilter()->purify($post[$model->getModelName()]['url']))) : '';

        $model->attributes  = (array)request()->getPost($model->getModelName(), []);
        $model->url         = $url;
        $model->list_id     = (int)$list->list_id;
        $model->type_id     = (int)$pageType->type_id;

        if (!$model->save()) {

            // prevent redirect
            $collection->add('success', false);

            // remove success messages and add ours
            notify()->clearSuccess()->addError(t('app', 'Your form contains errors, please correct them and try again.'));
        }
    }

    /**
     * @return void
     * @throws CException
     */
    public function _redirectIfNeeded()
    {
        /** @var Controller $controller */
        $controller = app()->getController();

        /** @var CAction $action */
        $action = $controller->getAction();

        if (!in_array($action->getId(), array_keys($this->actionToPageType))) {
            return;
        }

        $list_uid = (string)request()->getQuery('list_uid', '');
        if (empty($list_uid)) {
            return;
        }

        /** @var Lists|null $list */
        $list = Lists::model()->findByUid($list_uid);
        if (empty($list)) {
            return;
        }

        /** @var ListPageType|null $pageType */
        $pageType = ListPageType::model()->findByAttributes([
            'slug' => $this->actionToPageType[$action->getId()],
        ]);
        if (empty($pageType)) {
            return;
        }

        /** @var ListFormCustomRedirect|null $redirect */
        $redirect = ListFormCustomRedirect::model()->findByAttributes([
            'list_id'   => $list->list_id,
            'type_id'   => $pageType->type_id,
        ]);

        if (empty($redirect) || empty($redirect->url)) {
            return;
        }

        // since 1.3.5, allow using custom subscriber tags in redirect url
        $subscriber_uid = (string)request()->getQuery('subscriber_uid', '');
        if (!empty($subscriber_uid) && strpos($redirect->url, '[') !== false && strpos($redirect->url, ']') !== false) {

            /** @var ListSubscriber|null $subscriber */
            $subscriber = ListSubscriber::model()->findByAttributes([
                'list_id'        => $list->list_id,
                'subscriber_uid' => $subscriber_uid,
            ]);

            if (!empty($subscriber)) {
                // fake it so we can use CampaignHelper class
                $campaign = new Campaign();
                $campaign->campaign_id = 0;
                $campaign->customer_id = $list->customer_id;
                $campaign->list_id     = (int)$list->list_id;
                $campaign->addRelatedRecord('customer', $list->customer, false);
                $campaign->addRelatedRecord('list', $list, false);
                $searchReplace = CampaignHelper::getCommonTagsSearchReplace($redirect->url, $campaign, $subscriber);
                $redirect->url = str_replace(array_keys($searchReplace), array_map('urlencode', array_values($searchReplace)), $redirect->url);
                unset($campaign);
            }
        }

        if (!FilterVarHelper::url($redirect->url)) {
            return;
        }

        if ($redirect->timeout == 0) {
            request()->redirect($redirect->url);
        }

        clientScript()->registerMetaTag($redirect->timeout . ';' . $redirect->url, null, 'refresh');
    }

    /**
     * @param Lists|null $newList
     * @param Lists $oldList
     *
     * @return Lists|null
     */
    public function _modelsListsAfterCopyList(?Lists $newList, Lists $oldList): ?Lists
    {
        if ($newList === null) {
            return null;
        }

        $redirects = ListFormCustomRedirect::model()->findAllByAttributes([
            'list_id' => (int)$oldList->list_id,
        ]);

        foreach ($redirects as $redirect) {
            $redirect = clone $redirect;
            $redirect->setIsNewRecord(true);
            $redirect->redirect_id  = null;
            $redirect->list_id      = (int)$newList->list_id;
            $redirect->date_added   = MW_DATETIME_NOW;
            $redirect->last_updated = MW_DATETIME_NOW;
            $redirect->save(false);
        }

        return $newList;
    }
}
