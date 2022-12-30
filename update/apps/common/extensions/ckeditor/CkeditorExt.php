<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CkeditorExt
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class CkeditorExt extends ExtensionInit
{
    /**
     * @var string
     */
    public $name = 'CKeditor';

    /**
     * @var string
     */
    public $description = 'CKeditor for MailWizz EMA';

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
     * @inheritDoc
     */
    public function run()
    {
        $this->preRun();

        /** @var CkeditorExtCommon $model */
        $model = container()->get(CkeditorExtCommon::class);

        // the callback to register the editor, if enabled
        if ($model->getIsEditorEnabled()) {
            hooks()->addAction('wysiwyg_editor_instance', [$this, 'createNewEditorInstance']);
        }

        $this->addUrlRules([
            ['ckeditor/settings',               'pattern' => 'extensions/ckeditor/settings'],
            ['ckeditor/fm',                     'pattern' => 'extensions/ckeditor/fm'],
            ['ckeditor/filemanager',            'pattern' => 'extensions/ckeditor/filemanager'],
            ['ckeditor/filemanager_connector',  'pattern' => 'extensions/ckeditor/filemanager/connector'],
        ]);

        $this->addControllerMap([
            'ckeditor' => [
                'class' => 'common.controllers.CkeditorExtCommonCkeditorController',
            ],
        ]);

        if ($this->getIsFilemanagerEnabled()) {
            hooks()->addFilter(apps()->getCurrentAppName() . '_left_navigation_menu_items', [$this, '_addFilemanagerMenuItem']);
        }
    }

    /**
     * Add the landing page for this extension (settings/general info/etc)
     *
     * @return string
     */
    public function getPageUrl()
    {
        return $this->createUrl('ckeditor/settings');
    }

    /**
     * @param array $editorOptions
     *
     * @return void
     * @throws CException
     */
    public function createNewEditorInstance(array $editorOptions = [])
    {
        $this->registerAssets();

        $defaultWysiwygOptions = $this->getDefaultEditorOptions();
        $wysiwygOptions = (array)hooks()->applyFilters('wysiwyg_editor_global_options', $defaultWysiwygOptions);
        $wysiwygOptions = CMap::mergeArray($wysiwygOptions, $editorOptions);

        if (!isset($wysiwygOptions['id'])) {
            return;
        }

        // since 1.6.3
        if (!empty($wysiwygOptions['fullPage']) && empty($wysiwygOptions['newpage_html'])) {
            $wysiwygOptions['newpage_html'] = app_param('email.templates.stub', '');
        }

        $editorId       = html_encode($wysiwygOptions['id']);
        $optionsVarName = 'wysiwygOptions' . ($editorId);
        $editorVarName  = 'wysiwygInstance' . ($editorId);
        $script         = '';

        unset($wysiwygOptions['id']);

        /**
         * For some reason, ckeditor does not inject default new page so we have to do it for it.
         * This should be temporary, at least until ckeditor fixes this issue.
         *
         * @since 1.6.3
         */
        if (!empty($wysiwygOptions['newpage_html'])) {
            $initContentLengthVarName = 'wysiwygInitContentLength' . ($editorId);
            $initContentStubVarName   = 'wysiwygInitContentStub' . ($editorId);

            $script .= $initContentLengthVarName . ' = $("#' . $editorId . '").length ? $("#' . $editorId . '").val().length : 0;' . "\n";
            $script .= $initContentStubVarName . ' = ' . CJavaScript::encode($wysiwygOptions['newpage_html']) . "\n";

            if (empty($wysiwygOptions['on']) || !is_array($wysiwygOptions['on'])) {
                $wysiwygOptions['on'] = [];
            }

            $wysiwygOptions['on'] = CMap::mergeArray([
                'instanceReady' => sprintf('js: function(evt) {
			        var editorContentLength = $(evt.editor.element.$).length ? $(evt.editor.element.$).val().length : 0;
			        if (%s === 0 || editorContentLength === 0) {
	        	        evt.editor.setData(%s);
	        	    }
	        	}', $initContentLengthVarName, $initContentStubVarName),
                'change' => sprintf('js: function(evt) {
			        if (evt.editor.getData().length === 0) {
			            evt.editor.setData(%s);
			        }
			    }', $initContentStubVarName),
                'mode' => 'js: function(evt){
		            var editor = evt.editor;
		            if (this.mode == \'source\') {
	                    var editable = editor.editable();
	                    editable.attachListener(editable, \'input\', function() {
	                        editor.editable().fire(\'input\');
	                    });
	                }
		        }',
            ], $wysiwygOptions['on']);
        }
        //

        $script .= $optionsVarName . ' = ' . CJavaScript::encode($wysiwygOptions) . ';' . "\n";
        $script .= '$("#' . $editorId . '").ckeditor(' . $optionsVarName . ');' . "\n";
        $script .= $editorVarName . ' = CKEDITOR.instances["' . $editorId . '"];' . "\n";

        clientScript()->registerScript(sha1(__METHOD__ . $editorId), $script);
    }

    /**
     * @return string
     */
    public function getEditorToolbar(): string
    {
        /** @var CkeditorExtCommon $model */
        $model = container()->get(CkeditorExtCommon::class);

        return (string)hooks()->applyFilters('wysiwyg_editor_toolbar', $model->getDefaultToolbar());
    }

    /**
     * @return array
     */
    public function getEditorToolbars(): array
    {
        return (array)hooks()->applyFilters('wysiwyg_editor_toolbars', ['Default', 'Simple', 'Full']);
    }

    /**
     * @return array
     * @throws CException
     */
    public function getFilemanagerThemes(): array
    {
        // cache
        static $themes = null;

        // if already loaded, return them all.
        if ($themes !== null && is_array($themes)) {
            return $themes;
        }

        if ($themes === null) {
            $themes    = [];
            $assetsUrl = $this->getAssetsUrl();
            $folders   = (array)FileSystemHelper::getDirectoryNames((string)Yii::getPathOfAlias($this->getPathAlias()) . '/common/assets/elfinder/themes/');
            foreach ($folders as $folderName) {
                $themes[] = [
                    'name' => $folderName,
                    'url'  => $assetsUrl . '/elfinder/themes/' . $folderName . '/css/theme.css',
                ];
            }
        }

        $themes = (array)hooks()->applyFilters('wysiwyg_filemanager_available_themes', $themes);
        $urls   = [];
        $names  = [];

        /**
         * @var int $index
         * @var array $theme
         */
        foreach ($themes as $index => $theme) {
            if (!isset($theme['name'], $theme['url'])) {
                unset($themes[$index]);
                continue;
            }
            $themeName = strtolower((string)$theme['name']);
            $themeUrl  = strtolower((string)$theme['url']);
            if (isset($urls[$themeUrl]) || isset($names[$themeName])) {
                unset($themes[$index]);
                continue;
            }
            $urls[$themeUrl]   = true;
            $names[$themeName] = true;
        }
        unset($names, $urls);

        return $themes;
    }

    /**
     * @param string $name
     *
     * @return array
     * @throws CException
     */
    public function getFilemanagerTheme(string $name): array
    {
        if (empty($name)) {
            return [];
        }
        $themes = $this->getFilemanagerThemes();
        foreach ($themes as $theme) {
            if (strtolower((string)$theme['name']) == strtolower((string)$name)) {
                return $theme;
            }
        }
        return [];
    }

    /**
     * @return array
     */
    public function getDefaultEditorOptions(): array
    {
        $toolbar  = $this->getEditorToolbar();
        $toolbars = $this->getEditorToolbars();

        if (empty($toolbar) || empty($toolbars) || !in_array($toolbar, $toolbars)) {
            $toolbar = 'Default';
        }

        $orientation = app()->getLocale()->orientation;
        if (app()->getController()) {
            $orientation = app()->getController()->getHtmlOrientation();
        }

        $options = [
            'toolbar'               => $toolbar,
            'language'              => $this->detectedLanguage,
            'contentsLanguage'      => app()->getLocale()->getLanguageID($this->detectedLanguage),
            'contentsLangDirection' => $orientation,
            'contentsCss'           => [
                apps()->getBaseUrl('assets/css/bootstrap.min.css'),
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css',
                'https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css',
                apps()->getBaseUrl('assets/css/adminlte.css'),
                apps()->getBaseUrl('assets/css/skin-blue.css'),
            ],
        ];

        if ($this->getIsFilemanagerEnabled()) {
            $options['filebrowserBrowseUrl'] = $this->getFilemanagerUrl();
            // $options['filebrowserImageWindowWidth'] = 920;
            $options['filebrowserImageWindowHeight'] = 400;
        }

        return $options;
    }

    /**
     * @return void
     * @throws CException
     */
    public function registerAssets(): void
    {
        static $_assetsRegistered = false;
        if ($_assetsRegistered) {
            return;
        }
        $_assetsRegistered = true;

        // set a flag to know which editor is active.
        app_param_set('wysiwyg', 'ckeditor');

        $assetsUrl = $this->getAssetsUrl();
        clientScript()->registerScriptFile($assetsUrl . '/ckeditor/ckeditor.js');
        clientScript()->registerScriptFile($assetsUrl . '/ckeditor/adapters/jquery.js');

        // find the language file, if any.
        $language     = (string)str_replace('_', '-', app()->getLanguage());
        $languageFile = '';

        if (is_file(dirname(__FILE__) . '/common/assets/ckeditor/lang/' . $language . '.js')) {
            $languageFile = $language . '.js';
        }

        if ($languageFile === '' && strpos($language, '-') !== false) {
            $language = explode('-', $language);
            $language = $language[0];
            if (is_file(dirname(__FILE__) . '/common/assets/ckeditor/lang/' . $language . '.js')) {
                $languageFile = $language . '.js';
            }
        }

        // if language found, register it.
        if ($languageFile !== '') {
            $this->detectedLanguage = $language;
            clientScript()->registerScriptFile($assetsUrl . '/ckeditor/lang/' . $languageFile);
        }
    }

    /**
     * @return string
     * @throws CException
     */
    public function getAssetsUrl(): string
    {
        return (string)assetManager()->publish(dirname(__FILE__) . '/common/assets', false, -1, MW_DEBUG);
    }

    /**
     * @return bool
     */
    public function getIsFilemanagerEnabled(): bool
    {
        // make sure we have the models loaded since this method is called from other extesions as well
        $this->preRun();

        /** @var CkeditorExtCommon $model */
        $model = container()->get(CkeditorExtCommon::class);

        if (
            ($this->isAppName('backend') && $model->getIsFilemanagerEnabledForUser()) ||
            ($this->isAppName('customer') && $model->getIsFilemanagerEnabledForCustomer())
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getFilemanagerUrl(): string
    {
        return $this->createUrl('ckeditor/filemanager');
    }

    /**
     * @param array $menuItems
     *
     * @return array
     */
    public function _addFilemanagerMenuItem(array $menuItems): array
    {
        $key = $this->isAppName('backend') ? 'email-templates' : 'templates';
        if (!isset($menuItems[$key])) {
            return $menuItems;
        }

        if (!isset($menuItems[$key]['active'])) {
            $menuItems[$key]['active'] = [];
        }

        if (is_string($menuItems[$key]['active'])) {
            $menuItems[$key]['active'] = [
                $menuItems[$key]['active'],
            ];
        }

        $menuItems[$key]['active'][] = $this->getRoute('ckeditor/fm');
        $menuItems[$key]['items'][] = [
            'url'       => $this->createUrl('ckeditor/fm'),
            'label'     => $this->t('File manager'),
            'active'    => strpos(app()->getController()->getRoute(), $this->getRoute('ckeditor/fm')) === 0,
        ];

        return $menuItems;
    }

    /**
     * @return void
     */
    protected function preRun(): void
    {
        if (!container()->has(CkeditorExtCommon::class)) {
            $this->importClasses('common.models.*');

            // register the common model in container for singleton access
            container()->add(CkeditorExtCommon::class, CkeditorExtCommon::class);
        }
    }
}
