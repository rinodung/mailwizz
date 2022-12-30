<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * LanguageUploadForm
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.1
 */

class LanguageUploadForm extends FormModel
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
            ['archive', 'required'],
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
     * @return bool
     */
    public function upload(): bool
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

        if (!app()->hasComponent('messages')) {
            $this->addError('archive', t('languages', 'The archive upload is only allowed for Db message source.'));
            return false;
        }

        /** @var string $languagesDir */
        $languagesDir = (string)Yii::getPathOfAlias('common.messages');

        if ((!file_exists($languagesDir) || !is_dir($languagesDir)) && !mkdir($languagesDir, 0777, true)) {
            $this->addError('archive', t('app', 'Cannot create directory "{dirPath}". Make sure the parent directory is writable by the webserver!', ['{dirPath}' => $languagesDir]));
            return false;
        }

        if (!is_writable($languagesDir)) {
            $this->addError('archive', t('app', 'The directory "{dirPath}" is not writable by the webserver!', ['{dirPath}' => $languagesDir]));
            return false;
        }

        $existingLanguageFolders = (array)FileSystemHelper::getDirectoryNames($languagesDir);

        $zip->extractTo($languagesDir);
        $zip->close();

        $updatedLanguageFolders = (array)FileSystemHelper::getDirectoryNames($languagesDir);

        $newLanguages = array_diff($updatedLanguageFolders, $existingLanguageFolders);
        if (empty($newLanguages)) {
            return true;
        }

        $errors = [];
        foreach ($newLanguages as $dirName) {
            try {
                $locale = app()->getLocale($dirName);
            } catch (Exception $e) {
                FileSystemHelper::deleteDirectoryContents($languagesDir . '/' . $dirName, true, 1);
                $errors[] = t('languages', 'The language directory {dirName} is not valid and was deleted!', [
                    '{dirName}' => $dirName,
                ]);
                continue;
            }

            $languageCode = $regionCode = '';
            if (strpos($dirName, '_') !== false) {
                $languageAndLocaleCode = explode('_', $dirName);
                [$languageCode, $regionCode] = $languageAndLocaleCode;
            } else {
                $languageCode = $dirName;
            }

            $criteria = new CDbCriteria();
            $criteria->compare('language_code', $languageCode);
            if (!empty($regionCode)) {
                $criteria->compare('region_code', $regionCode);
            }

            /** @var Language|null $language */
            $language = Language::model()->find($criteria);

            if (empty($language)) {
                $language = new Language();
                $language->name = ucwords($locale->getLanguage($dirName));
                $language->language_code = $languageCode;
                $language->region_code   = $regionCode;
                if (!$language->save()) {
                    FileSystemHelper::deleteDirectoryContents($languagesDir . '/' . $dirName, true, 1);
                    $errors[] = t('languages', 'The language "{languageName}" cannot be saved, failure reason: ', [
                        '{languageName}' => $language->name,
                    ]);
                    $errors[] = $language->shortErrors->getAllAsString();
                    continue;
                }
            }

            if (app()->getComponent('messages') instanceof CDbMessageSource) {
                $result = TranslationHelper::importFromPhpFiles($languagesDir . '/' . $dirName);
                if ($result['error']) {
                    $importErrors = CMap::mergeArray(array_slice($result['errors'], 0, 10), ['...']);
                    $errors       = CMap::mergeArray($errors, $importErrors);
                }
            }
        }

        if (!empty($errors)) {
            $this->addErrors(['archive' => $errors]);
            return false;
        }

        return true;
    }
}
