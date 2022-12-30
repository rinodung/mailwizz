<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ThemeHandlerForm
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class ThemeHandlerForm extends FormModel
{
    /**
     * @var CUploadedFile
     */
    public $archive;

    /**
     * @return array
     */
    public function rules()
    {
        $mimes = null;
        if (CommonHelper::functionExists('finfo_open')) {

            /** @var FileExtensionMimes $extensionMimes */
            $extensionMimes = app()->getComponent('extensionMimes');

            /** @var array $mimes */
            $mimes = $extensionMimes->get('zip')->toArray();
        }

        $rules = [
            ['archive', 'required', 'on' => 'upload'],
            ['archive', 'file', 'types' => ['zip'], 'mimeTypes' => $mimes, 'allowEmpty' => true],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'archive'   => t('app', 'Archive'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @param string $appName
     *
     * @return bool
     */
    public function upload(string $appName): bool
    {
        // no reason to go further if there are errors.
        if (!$this->validate()) {
            return false;
        }

        // we need the zip archive class, cannot work without.
        if (!class_exists('ZipArchive', false)) {
            $this->addError('archive', t('app', 'ZipArchive class required in order to unzip the file.'));
            return false;
        }

        $zip = new ZipArchive();
        if (!$zip->open($this->archive->tempName)) {
            $this->addError('archive', t('app', 'Cannot open the archive file.'));
            return false;
        }

        $themesDir = (string)Yii::getPathOfAlias('root.' . $appName . '.themes');
        if ((!file_exists($themesDir) || !is_dir($themesDir)) && !mkdir($themesDir, 0777, true)) {
            $this->addError('archive', t('app', 'Cannot create directory "{dirPath}". Make sure the parent directory is writable by the webserver!', ['{dirPath}' => $themesDir]));
            return false;
        }

        if (!is_writable($themesDir)) {
            $this->addError('archive', t('app', 'The directory "{dirPath}" is not writable by the webserver!', ['{dirPath}' => $themesDir]));
            return false;
        }

        $zip->extractTo($themesDir);
        $zip->close();

        return true;
    }

    /**
     * @param string $appName
     *
     * @return CArrayDataProvider
     * @throws ReflectionException
     */
    public function getDataProvider(string $appName): CArrayDataProvider
    {
        /** @var CWebApplication $app */
        $app = app();

        /** @var ThemeManager $manager */
        $manager = $app->getComponent('themeManager');

        $themesInstances = $manager->getThemesInstances($appName);

        $themes = [];
        foreach ($themesInstances as $theme) {
            $description = html_encode($theme->description);
            $name        = html_encode($theme->name);
            $pageUrl     = '';

            if ($manager->isThemeEnabled($theme->dirName, $appName)) {
                if (!$theme->pageUrl) {
                    /** @var string $className */
                    $className  = (string)get_class($theme);
                    // @phpstan-ignore-next-line
                    $reflection = new ReflectionClass((string)$className);
                    if ($reflection->getMethod('settingsPage')->class == $className) {
                        $pageUrl = createUrl('theme/settings', ['app' => $appName, 'theme' => $theme->dirName]);
                    }
                } else {
                    $pageUrl = $theme->pageUrl;
                }
                if ($pageUrl) {
                    $name = CHtml::link($name, $pageUrl);
                }
            }

            $themes[] = [
                'id'            => $theme->dirName,
                'name'          => $name,
                'description'   => $description,
                'version'       => $theme->version,
                'author'        => $theme->email ? CHtml::link(html_encode($theme->author), 'mailto:' . html_encode($theme->email)) : html_encode($theme->author),
                'website'       => $theme->website ? CHtml::link(t('themes', 'Visit website'), html_encode($theme->website), ['target' => '_blank']) : null,
                'enabled'       => $manager->isThemeEnabled($theme->dirName, $appName),
                'pageUrl'       => $pageUrl,
                'enableUrl'     => createUrl('theme/enable', ['app' => $appName, 'name' => $theme->dirName]),
                'disableUrl'    => createUrl('theme/disable', ['app' => $appName, 'name' => $theme->dirName]),
                'deleteUrl'     => createUrl('theme/delete', ['app' => $appName, 'name' => $theme->dirName]),
            ];
        }

        return new CArrayDataProvider($themes);
    }
}
