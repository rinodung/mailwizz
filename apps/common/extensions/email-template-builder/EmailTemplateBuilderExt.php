<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailTemplateBuilderExt
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class EmailTemplateBuilderExt extends ExtensionInit
{
    /**
     * @var string
     */
    public $name = 'Email Template Builder';

    /**
     * @var string
     */
    public $description = 'Drag and Drop Email Template Builder For MailWizz EMA';

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
    public $allowedApps = ['backend', 'customer'];

    /**
     * @var bool
     */
    protected $_canBeDeleted = false;

    /**
     * @var bool
     */
    protected $_canBeDisabled = true;

    /**
     * @var string
     */
    protected $detectedLanguage = 'en';

    /**
     * @var string
     */
    private $_assetsAlias = 'root.frontend.assets.cache.ext-email-template-builder';

    /**
     * @var string
     */
    private $_assetsRelativeUrl = '/frontend/assets/cache/ext-email-template-builder';

    /**
     * @var string
     */
    private $_assetsUrl = '';

    /**
     * @inheritDoc
     */
    public function run()
    {
        /** @var CkeditorExt|null $ckeditor */
        $ckeditor = $this->getManager()->getExtensionInstance('ckeditor');

        /**
         * This extension depends on ckeditor so we need to make sure it is enabled.
         */
        if (empty($ckeditor)) {
            return;
        }

        /**
         * Make sure we enable the file manager
         */
        if (($this->isAppName('customer') || $this->isAppName('backend')) && !$ckeditor->getIsFilemanagerEnabled()) {

            /** @var CkeditorExtCommon $model */
            $model = container()->get(CkeditorExtCommon::class);
            $model->saveAttributes([
                'enable_filemanager_user'     => 1,
                'enable_filemanager_customer' => 1,
            ]);
        }

        /**
         * Register the assets just after ckeditor is done.
         */
        hooks()->addAction('wysiwyg_editor_instance', [$this, '_createNewEditorInstance'], 99);

        /**
         * Customer area only
         */
        if ($this->isAppName('customer')) {

            /**
             * Handle the builder for customer area, in the templates controller
             */
            hooks()->addAction('customer_controller_templates_before_action', [$this, '_customerControllerTemplatesBeforeAction']);

            /**
             * Handle the builder for customer area, in the campaigns controller
             */
            hooks()->addAction('customer_controller_campaigns_before_action', [$this, '_customerControllerCampaignsBeforeAction']);

            /**
             * CKEditor controller
             */
            hooks()->addAction('customer_controller_ckeditor_ext_ckeditor_before_action', [$this, '_controllerExtCkeditorBeforeAction']);
        }

        /**
         * Backend area only
         */
        if ($this->isAppName('backend')) {

            /**
             * Handle the builder for backend area, in the email templates gallery controller
             */
            hooks()->addAction('backend_controller_email_templates_gallery_before_action', [$this, '_backendControllerEmailTemplatesGalleryBeforeAction']);

            /**
             * CKEditor controller
             */
            hooks()->addAction('backend_controller_ckeditor_ext_ckeditor_before_action', [$this, '_controllerExtCkeditorBeforeAction']);
        }
    }

    /**
     * @inheritDoc
     */
    public function beforeEnable()
    {
        $this->publishAssets();
        return true;
    }

    /**
     * @inheritDoc
     */
    public function beforeDisable()
    {
        $this->unpublishAssets();
        return true;
    }

    /**
     * @param array $editorOptions
     *
     * @return void
     */
    public function _createNewEditorInstance(array $editorOptions = [])
    {
        $this->registerAssets();
    }

    /**
     * @param CAction $action
     *
     * @return void
     */
    public function _customerControllerTemplatesBeforeAction(CAction $action)
    {
        if (!in_array($action->getId(), ['create', 'update'])) {
            return;
        }

        // add the button
        hooks()->addAction('before_wysiwyg_editor_right_side', [$this, '_beforeWysiwygEditorRightSide']);

        // add the code to handle the editor
        hooks()->addAction('after_wysiwyg_editor', [$this, '_afterWysiwygEditor']);

        // add the code to save the editor data
        hooks()->addAction('controller_action_save_data', [$this, '_controllerActionSaveData']);
    }

    /**
     * @param CAction $action
     *
     * @return void
     */
    public function _customerControllerCampaignsBeforeAction(CAction $action)
    {
        if (!in_array($action->getId(), ['template'])) {
            return;
        }

        // add the button
        hooks()->addAction('before_wysiwyg_editor_right_side', [$this, '_beforeWysiwygEditorRightSide']);

        // add the code to handle the editor
        hooks()->addAction('after_wysiwyg_editor', [$this, '_afterWysiwygEditor']);

        // add the code to save the editor data
        hooks()->addAction('controller_action_save_data', [$this, '_controllerActionSaveData']);
    }

    /**
     * @param CAction $action
     *
     * @return void
     */
    public function _backendControllerEmailTemplatesGalleryBeforeAction(CAction $action)
    {
        if (!in_array($action->getId(), ['create', 'update'])) {
            return;
        }

        // add the button
        hooks()->addAction('before_wysiwyg_editor_right_side', [$this, '_beforeWysiwygEditorRightSide']);

        // add the code to handle the editor
        hooks()->addAction('after_wysiwyg_editor', [$this, '_afterWysiwygEditor']);

        // add the code to save the editor data
        hooks()->addAction('controller_action_save_data', [$this, '_controllerActionSaveData']);
    }

    /**
     * @param CAction $action
     *
     * @return void
     */
    public function _controllerExtCkeditorBeforeAction(CAction $action)
    {
        if ($action->getId() != 'filemanager') {
            return;
        }

        // add image handling code for file manager
        hooks()->addAction('ext_ckeditor_elfinder_filemanager_view_html_head', [$this, '_extCkeditorElfinderFilemanagerViewHtmlHead']);
    }

    /**
     * Add the button to toggle the editor
     *
     * @param array $params
     *
     * @return void
     */
    public function _beforeWysiwygEditorRightSide(array $params = [])
    {
        $toggle = CHtml::link($this->t('Toggle template builder'), 'javascript:;', [
            'class' => 'btn btn-flat btn-primary',
            'title' => $this->t('Toggle template builder'),
            'id'    => 'btn_' . $params['template']->getModelName() . '_content',
        ]);

        $info = CHtml::link(IconHelper::make('info'), '#page-info-toggle-template-builder', [
            'title'         => t('app', 'Info'),
            'class'         => 'btn btn-primary btn-flat no-spin',
            'data-toggle'   => 'modal',
        ]);

        echo $toggle . ' ' . $info;
    }

    /**
     * The view after ckeditor
     *
     * @param array $params
     *
     * @return void
     * @throws CException
     */
    public function _afterWysiwygEditor(array $params = [])
    {
        /** @var CustomerEmailTemplate $model */
        $model = null;
        if (!empty($params['template'])) {
            $model = $params['template'];
        }

        if (empty($model) || !is_object($model) || !($model instanceof ActiveRecord)) {
            return;
        }

        if (!$model->asa('modelMetaData') || !method_exists($model->modelMetaData, 'getModelMetaData')) {
            return;
        }

        /** @var string $modelName */
        $modelName = $model->getModelName();
        $builderId = $modelName . '_content';

        /** @var CkeditorExt $ckeditor */
        $ckeditor = $this->getManager()->getExtensionInstance('ckeditor');

        $options = [
            'rootId'        => 'builder_' . $builderId,
            'lang'          => $this->detectedLanguage,
            'mediaBaseUrl'  => $this->getAssetsUrl() . '/static/media/',
            'ckeditor'      => [
                'scriptUrl' => $ckeditor->getAssetsUrl() . '/ckeditor/ckeditor.js',
                'config'    => [
                    'toolbar' => 'Emailbuilder',
                ],
            ],
        ];

        if ($ckeditor->getIsFilemanagerEnabled()) {
            $options['managerUrl'] = $ckeditor->getFilemanagerUrl();
            $options['ckeditor']['config']['filebrowserBrowseUrl'] = $ckeditor->getFilemanagerUrl();
        }

        $json = [];
        $contentJson = (string)$model->modelMetaData->getModelMetaData()->itemAt('content_json');
        if (!empty($contentJson)) {
            $contentJson = (array)json_decode((string)base64_decode($contentJson), true);
            if (!empty($contentJson)) {
                $json = $contentJson;
                unset($contentJson);
            }
        }

        /** @var array $post */
        $post = (array)request()->getOriginalPost('', []);
        if (isset($post[$modelName]['content_json'])) {
            $contentJson = (array)json_decode((string)$post[$modelName]['content_json'], true);
            if (!empty($contentJson)) {
                $json = $contentJson;
                unset($contentJson);
            }
        }

        app()->getController()->renderInternal(dirname(__FILE__) . '/common/views/after-editor.php', [
            'json'      => $json,
            'options'   => $options,
            'modelName' => $modelName,
            'builderId' => $builderId,
            'extension' => $this,
        ]);
    }

    /**
     * @param CAttributeCollection $collection
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function _controllerActionSaveData(CAttributeCollection $collection)
    {
        if (!$collection->itemAt('success')) {
            return;
        }

        /** @var ActiveRecord $template */
        $template = $collection->itemAt('template');

        /** @var array $post */
        $post = (array)request()->getOriginalPost('', []);

        if (isset($post[$template->getModelName()]['content_json'])) {
            $contentJson = $post[$template->getModelName()]['content_json'];
            if (!empty($contentJson)) {
                $contentJson = base64_encode((string)json_encode(json_decode($contentJson, true)));
                $template->modelMetaData->setModelMetaData('content_json', $contentJson)->saveModelMetaData();
            }
        }
    }

    /**
     * Render the javascript code for elfinder
     *
     * @return void
     */
    public function _extCkeditorElfinderFilemanagerViewHtmlHead()
    {
        $script = file_get_contents(dirname(__FILE__) . '/common/assets/static/js/code-elfinder.js');
        echo sprintf("<script>\n%s\n</script>", $script);
    }

    /**
     * @return void
     */
    public function registerAssets()
    {
        static $_assetsRegistered = false;
        if ($_assetsRegistered) {
            return;
        }
        $_assetsRegistered = true;

        /** @var string $assetsUrl */
        $assetsUrl = $this->getAssetsUrl();

        // find the language file, if any.
        $language     = str_replace('_', '-', app()->getLanguage());
        $languageFile = '';

        if (is_file(dirname(__FILE__) . '/common/assets/languages/' . $language . '.js')) {
            $languageFile = $language . '.js';
        }

        if ($languageFile === '' && strpos($language, '-') !== false) {
            $language = explode('-', $language);
            $language = $language[0];
            if (is_file(dirname(__FILE__) . '/common/assets/languages/' . $language . '.js')) {
                $languageFile = $language . '.js';
            }
        }

        // if language found, register it.
        if ($languageFile !== '') {
            $this->detectedLanguage = $language;
            clientScript()->registerScriptFile($assetsUrl . '/languages/' . $languageFile);
        }

        // register the rest of css/js
        clientScript()->registerCssFile($assetsUrl . '/static/css/main.c87ec30c.css');
        clientScript()->registerCssFile($assetsUrl . '/static/css/code-editor.css');
        clientScript()->registerScriptFile($assetsUrl . '/static/js/main.7a7f902f.js');
        clientScript()->registerScriptFile($assetsUrl . '/static/js/code-editor.js');
    }

    /**
     * @return string
     */
    public function getAssetsAlias(): string
    {
        return $this->_assetsAlias;
    }

    /**
     * @return string
     */
    public function getAssetsRelativeUrl(): string
    {
        return apps()->getAppUrl('frontend', (string)$this->_assetsRelativeUrl, false, true);
    }

    /**
     * @return string
     */
    public function getAssetsAbsoluteUrl(): string
    {
        return apps()->getAppUrl('frontend', (string)$this->_assetsRelativeUrl, true, true);
    }

    /**
     * @return string
     */
    public function getAssetsUrl(): string
    {
        if ($this->_assetsUrl !== '') {
            return $this->_assetsUrl;
        }

        return $this->publishAssets();
    }

    /**
     * @return string
     */
    public function publishAssets(): string
    {
        $src = dirname(__FILE__) . '/common/assets/';
        $dst = (string)Yii::getPathOfAlias($this->getAssetsAlias());

        $isDebug = MW_DEBUG;
        // @phpstan-ignore-next-line
        if (is_dir($dst) && empty($isDebug)) {
            return $this->_assetsUrl = $this->getAssetsAbsoluteUrl();
        }

        CFileHelper::copyDirectory($src, $dst, ['newDirMode' => 0777]);
        return $this->_assetsUrl = $this->getAssetsAbsoluteUrl();
    }

    /**
     * Unpublish assets
     *
     * @return void
     */
    public function unpublishAssets(): void
    {
        $dst = (string)Yii::getPathOfAlias($this->getAssetsAlias());
        if (is_dir($dst)) {
            CFileHelper::removeDirectory($dst);
        }
    }
}
