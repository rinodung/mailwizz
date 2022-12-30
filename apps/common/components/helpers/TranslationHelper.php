<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * TranslationHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class TranslationHelper
{
    /**
     * @param string $languagePath
     * @param bool $canAddNewLanguage
     * @return array
     */
    public static function importFromPhpFiles(string $languagePath, bool $canAddNewLanguage = false): array
    {
        // the end result can be a partial success, which means the language can be created,
        // but importing (some of the) files failed
        // so we have both cases, a success and an error
        $result = [
            'success' => false,
            'error'   => false,
            'errors'  => [],
        ];

        if ((!file_exists($languagePath) || !is_dir($languagePath)) && !is_readable($languagePath)) {
            $result['error'] = true;
            $result['errors'] = [
                t('translations', 'Please make sure the folder "{name}" is readable!', ['{name}' => $languagePath]),
            ];
            return $result;
        }

        $regionCode            = '';
        $languageCode          = basename($languagePath);
        $languageAndRegionCode = $languageCode;

        try {
            $locale = app()->getLocale($languageAndRegionCode);
        } catch (Exception $e) {
            $result['error']  = true;
            $result['errors'] = [
                t('translations', 'The language "{name}" is not valid', ['{name}' => $languageAndRegionCode]),
            ];
            return $result;
        }

        if (strpos($languageCode, '_') !== false) {
            [$languageCode, $regionCode] = explode('_', $languageCode);
        }

        $attributes = [
            'language_code' => $languageCode,
        ];
        if (!empty($regionCode)) {
            $attributes['region_code'] = $regionCode;
        }

        $languageModel = Language::model()->findByAttributes($attributes);
        if (empty($languageModel)) {
            if (!$canAddNewLanguage) {
                $result['error']  = true;
                $result['errors'] = [
                    t('translations', 'No corresponding language found in the database for {name}', ['{name}' => $languageAndRegionCode]),
                ];
                return $result;
            }

            $languageModel = new Language();
            $attributes['name'] = ucwords($locale->getLanguage($languageAndRegionCode));
            $languageModel->attributes = $attributes;

            if (!$languageModel->save()) {
                $result['error']  = true;
                $result['errors'] = [
                    t('translations', 'The language "{name}" could not be created', ['{name}' => $languageAndRegionCode]),
                ];
                return $result;
            }
        }

        $errors   = [];
        $affected = 0;
        /** @var array $categories */
        $categories = FileSystemHelper::readDirectoryContents($languagePath);
        foreach ($categories as $category) {
            $categoryFile = $languagePath . '/' . $category;
            if (!is_file($categoryFile) || pathinfo($categoryFile, PATHINFO_EXTENSION) !== 'php' || !is_readable($categoryFile)) {
                $errors[] = t('translations', 'Please make sure the file "{name}" is readable and in the correct format!', ['{name}' => $categoryFile]);
                continue;
            }

            $categoryName = basename($category, '.php');

            // Now the extensions dont have the ext_ prefix
            if (substr($categoryName, 0, 4) === 'ext_') {
                $categoryName = substr_replace($categoryName, '', 0, 4);
            }

            if (strpos($categoryName, '_ext_') !== false) {
                $categoryName = (string)str_replace('_ext_', '_', $categoryName);
            }

            /** @var array $data */
            $data = require $categoryFile;
            if (!is_array($data)) {
                $errors[] = t('translations', 'Wrong data format for category - "{name}"', ['{name}' => $categoryFile]);
                continue;
            }

            foreach ($data as $message => $translation) {
                if (empty($message)) {
                    $errors[] = t('translations', 'Empty message found for category - "{name}" - skipping', ['{name}' => $categoryName]);
                    continue;
                }

                /** @var CDbCriteria $criteria */
                $criteria = new CDbCriteria();
                $criteria->compare('category', $categoryName);
                $criteria->compare('message', $message);
                $sourceMessage = TranslationSourceMessage::model()->find($criteria);

                $sourceMessageExists = true;
                if (empty($sourceMessage)) {
                    $sourceMessage = new TranslationSourceMessage();
                    $sourceMessage->category = $categoryName;
                    $sourceMessage->message  = (string)$message;
                    $sourceMessageExists = false;

                    if (!$sourceMessage->save()) {
                        // We use the category name as a key so we will overwrite these errors from within same category since they will be the same
                        $errors[$categoryName] = t('translations', 'Error saving the source message for category - "{name}" - skipping - {error}', [
                            '{name}' => $categoryName,
                            '{error}' => $sourceMessage->shortErrors->getAllAsString(),
                        ]);
                        continue;
                    }
                }

                if ($sourceMessageExists) {
                    $translationsCount = (int)TranslationMessage::model()->countByAttributes([
                        'id'       => $sourceMessage->id,
                        'language' => $languageModel->getLanguageAndLocaleCode(),
                    ]);

                    // Skip if the translation already exists in the database
                    if ($translationsCount > 0) {
                        continue;
                    }
                }

                $translationModel              = new TranslationMessage();
                $translationModel->id          = $sourceMessage->id;
                $translationModel->language    = $languageModel->getLanguageAndLocaleCode();
                $translationModel->translation = $translation;
                $translationModel->save();

                $affected++;
            }
        }

        if ($affected == 0) {
            $errors[] = t('translations', 'No new translations added');
        }

        $result['success'] = true;
        $result['error']   = !empty($errors);
        $result['errors']  = array_values($errors); // Getting the array values since we are using the category name as a key

        return $result;
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public static function importFromJsonFiles(string $path): bool
    {
        if (!file_exists($path) || !is_dir($path) || !is_readable($path)) {
            return false;
        }

        $files = (new Symfony\Component\Finder\Finder())
            ->files()
            ->name('*.json')
            ->in($path);

        $versions = [];
        foreach ($files as $file) {
            $versions[] = $file->getBasename('.json');
        }
        usort($versions, 'version_compare');

        foreach ($versions as $version) {
            self::importFromJsonFile($path . '/' . $version . '.json');
        }

        return true;
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public static function importFromJsonFile(string $path): bool
    {
        if (empty($path) || !is_file($path) || !is_readable($path)) {
            return false;
        }

        $messages = json_decode((string)file_get_contents($path));
        if (empty($messages) || !is_array($messages)) {
            return false;
        }

        foreach ($messages as $message) {
            if (empty($message->category) || empty($message->message)) {
                continue;
            }

            $source = TranslationSourceMessage::model()->findByAttributes([
                'category'  => $message->category,
                'message'   => $message->message,
            ]);

            if (!empty($source)) {
                continue;
            }

            $source = new TranslationSourceMessage();
            $source->category   = $message->category;
            $source->message    = $message->message;
            $source->save();
        }

        return true;
    }
}
