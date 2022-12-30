<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List form custom assets extension
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class ListFormCustomAssetsExt extends ExtensionInit
{
    /**
     * @var string
     */
    public $name = 'List form custom assets';

    /**
     * @var string
     */
    public $description = 'Will add the ability to add custom assets (css/js) to a form.';

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
        'subscribe'           => 'subscribe-form',
        'subscribe_pending'   => 'subscribe-pending',
        'subscribe_confirm'   => 'subscribe-confirm',
        'update_profile'      => 'update-profile',
        'unsubscribe_confirm' => 'unsubscribe-confirm',
        'unsubscribe'         => 'unsubscribe-form',
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
            hooks()->addAction('after_active_form_fields', [$this, '_insertCustomerFields']);
            hooks()->addAction('controller_action_save_data', [$this, '_saveCustomerData']);
            hooks()->addAction('customer_controller_list_page_before_action', [$this, '_loadCustomerAssets']);
        } elseif ($this->isAppName('frontend')) {
            hooks()->addAction('frontend_controller_lists_before_render', [$this, '_loadFrontendAssets']);
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
        CREATE TABLE IF NOT EXISTS `{{list_form_custom_asset}}` (
          `asset_id` INT NOT NULL AUTO_INCREMENT,
          `list_id` INT(11) NOT NULL,
          `type_id` INT(11) NOT NULL,
          `asset_url` TEXT NOT NULL,
          `asset_type` VARCHAR(10) NOT NULL,
          `date_added` DATETIME NOT NULL,
          `last_updated` DATETIME NOT NULL,
          PRIMARY KEY (`asset_id`),
          INDEX `fk_list_form_custom_asset_list1_idx` (`list_id` ASC),
          INDEX `fk_list_form_custom_asset_list_page_type1_idx` (`type_id` ASC),
          CONSTRAINT `fk_list_form_custom_asset_list1`
            FOREIGN KEY (`list_id`)
            REFERENCES `{{list}}` (`list_id`)
            ON DELETE CASCADE
            ON UPDATE NO ACTION,
          CONSTRAINT `fk_list_form_custom_asset_list_page_type1`
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

        db()->createCommand('DROP TABLE IF EXISTS `{{list_form_custom_asset}}`')->execute();

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
    public function _insertCustomerFields(CAttributeCollection $collection)
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

        if (!$this->getData('models')) {

            /** @var ListFormCustomAsset[] $models */
            $models = ListFormCustomAsset::model()->findAllByAttributes([
                'list_id'   => (int)$list->list_id,
                'type_id'   => (int)$pageType->type_id,
            ]);

            if (empty($models)) {
                $models = [];
            }

            $this->setData('models', $models);
        }

        /** @var ListFormCustomAsset[] $models */
        $models = $this->getData('models');

        foreach ($models as $model) {
            $model->list_id = (int)$list->list_id;
            $model->type_id = (int)$pageType->type_id;
        }

        $model = new ListFormCustomAsset();
        $form  = $collection->itemAt('form');

        $controller->renderInternal(dirname(__FILE__) . '/customer/views/_form.php', compact('models', 'model', 'form'));
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

        ListFormCustomAsset::model()->deleteAllByAttributes([
            'list_id' => (int)$list->list_id,
            'type_id' => (int)$pageType->type_id,
        ]);

        /** @var array $postModels */
        $postModels = (array)request()->getPost('ListFormCustomAsset', []);

        /** @var ListFormCustomAsset[] $models */
        $models = [];

        $errors = false;
        foreach ($postModels as $attributes) {
            $model = new ListFormCustomAsset();
            $model->attributes = $attributes;
            $model->list_id    = (int)$list->list_id;
            $model->type_id    = (int)$pageType->type_id;
            if (!$model->save()) {
                $errors = true;
            }
            $models[] = $model;
        }

        $this->setData('models', $models);

        if ($errors) {

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
    public function _loadFrontendAssets()
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

        /** @var ListFormCustomAsset[] $assets */
        $assets = ListFormCustomAsset::model()->findAllByAttributes([
            'list_id'   => $list->list_id,
            'type_id'   => $pageType->type_id,
        ]);

        if (empty($assets)) {
            return;
        }

        foreach ($assets as $asset) {
            if ($asset->asset_type == ListFormCustomAsset::ASSET_TYPE_CSS) {

                /** @var CList $styles */
                $styles = $controller->getData('pageStyles');
                $styles->add(['src' => $asset->asset_url, 'priority' => 1000]);
            } elseif ($asset->asset_type == ListFormCustomAsset::ASSET_TYPE_JS) {

                /** @var CList $scripts */
                $scripts = $controller->getData('pageScripts');
                $scripts->add(['src' => $asset->asset_url, 'priority' => 1000]);
            }
        }
    }

    /**
     * @return void
     * @throws CException
     */
    public function _loadCustomerAssets()
    {
        /** @var Controller|null $controller */
        $controller = app()->getController();

        if (empty($controller)) {
            return;
        }

        /** @var string $assetsUrl */
        $assetsUrl = assetManager()->publish(dirname(__FILE__) . '/customer/assets/', false, -1, MW_DEBUG);

        /** @var CList $scripts */
        $scripts = $controller->getData('pageScripts');

        $scripts->add(['src' => $assetsUrl . '/customer.js', 'priority' => 1000]);
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

        $assets = ListFormCustomAsset::model()->findAllByAttributes([
            'list_id' => (int)$oldList->list_id,
        ]);

        foreach ($assets as $asset) {
            $asset = clone $asset;
            $asset->setIsNewRecord(true);
            $asset->asset_id        = null;
            $asset->list_id         = (int)$newList->list_id;
            $asset->date_added      = MW_DATETIME_NOW;
            $asset->last_updated    = MW_DATETIME_NOW;
            $asset->save(false);
        }

        return $newList;
    }
}
