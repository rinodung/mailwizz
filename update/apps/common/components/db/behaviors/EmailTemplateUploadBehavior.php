<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailTemplateUploadBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property CustomerEmailTemplate $owner
 */
class EmailTemplateUploadBehavior extends CActiveRecordBehavior
{
    /**
     * @var string
     */
    private $_cdnSubdomain;

    /**
     * @return bool
     */
    public function handleUpload(): bool
    {
        // no reason to go further if there are errors.
        if ($this->owner->hasErrors() || empty($this->owner->archive)) {
            return false;
        }

        // we need the zip archive class, cannot work without.
        if (!class_exists('ZipArchive', false)) {
            $this->owner->addError('archive', t('app', 'ZipArchive class required in order to unzip the file.'));
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($this->owner->archive->tempName, ZipArchive::CREATE) !== true) {
            $this->owner->addError('archive', t('app', 'Cannot open the archive file.'));
            return false;
        }

        if (empty($this->owner->template_uid)) {
            $this->owner->template_uid = $this->owner->generateUid();
        }

        $storageDirName = $this->owner->template_uid;
        $tmpUploadPath = FileSystemHelper::getTmpDirectory() . '/' . $storageDirName;
        if (!file_exists($tmpUploadPath) && !mkdir($tmpUploadPath, 0777, true)) {
            $this->owner->addError('archive', t('app', 'Cannot create temporary directory "{dirPath}". Make sure the parent directory is writable by the webserver!', ['{dirPath}' => $tmpUploadPath]));
            return false;
        }

        $zip->extractTo($tmpUploadPath);
        $zip->close();

        // try to find the entry file, index.html
        $archiveName = (string)str_replace(['../', './', '..\\', '.\\', '..'], '', basename($this->owner->archive->name, '.zip'));
        $entryFilePath = null;
        $possibleEntryFiles = ['index.html', 'index.htm', $archiveName . '.html', $archiveName . '.htm'];
        foreach ($possibleEntryFiles as $entry) {
            if (is_file($file = $tmpUploadPath . '/' . $entry)) {
                $entryFilePath = $file;
                break;
            }
        }

        if ($entryFilePath === null && $files = FileSystemHelper::readDirectoryContents($tmpUploadPath, true)) {
            foreach ($files as $file) {
                $file = (string)str_replace(['../', './', '..\\', '.\\', '..'], '', $file);
                foreach ($possibleEntryFiles as $entry) {
                    if (substr($file, -strlen($entry)) === $entry) {
                        $entryFilePath = $file;
                        break;
                    }
                }
                if ($entryFilePath) {
                    break;
                }
            }
            // maybe named something else?
            if ($entryFilePath === null) {
                foreach ($files as $file) {
                    $file = (string)str_replace(['../', './', '..\\', '.\\', '..'], '', $file);
                    if (substr($file, -strlen('.html')) === '.html') {
                        $entryFilePath = $file;
                        break;
                    }
                    if (substr($file, -strlen('.htm')) === '.htm') {
                        $entryFilePath = $file;
                        break;
                    }
                }
            }
        }

        // the entry file was not found, too bad...
        if ($entryFilePath === null) {
            $this->owner->addError('archive', t('app', 'Cannot find template entry file, usually called index.html'));
            return false;
        }

        $entryFilePathDir = dirname($entryFilePath);
        $htmlContent = trim((string)file_get_contents($entryFilePath));

        if (empty($htmlContent)) {
            $this->owner->addError('archive', t('app', 'The template entry file seems to be empty.'));
            return false;
        }

        $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.gallery');
        if (!file_exists($storagePath) && !mkdir($storagePath, 0777, true)) {
            $this->owner->addError('archive', t('app', 'Cannot create temporary directory "{dirPath}". Make sure the parent directory is writable by the webserver!', ['{dirPath}' => $storagePath]));
            return false;
        }

        $storagePath .= '/' . $storageDirName;
        if (!file_exists($storagePath) && !mkdir($storagePath, 0777, true)) {
            $this->owner->addError('archive', t('app', 'Cannot create temporary directory "{dirPath}". Make sure the parent directory is writable by the webserver!', ['{dirPath}' => $storagePath]));
            return false;
        }

        libxml_use_internal_errors(true);
        $cleanContent = ioFilter()->purify($htmlContent);

        $query = qp($cleanContent, 'body', [
            'ignore_parser_warnings'    => true,
            'convert_to_encoding'       => app()->charset,
            'convert_from_encoding'     => app()->charset,
            'use_parser'                => 'html',
        ]);

        $images = $query->top()->find('img, amp-img');
        if ($images->length == 0) {
            $images = [];
        }

        $extensions     = ['png', 'jpg', 'jpeg', 'gif'];
        $foundImages    = [];
        $screenshot     = null;
        foreach ($extensions as $ext) {
            if (is_file($entryFilePathDir . '/screenshot.' . $ext) && copy($entryFilePathDir . '/screenshot.' . $ext, $storagePath . '/screenshot.' . $ext)) {
                $screenshot = '/frontend/assets/gallery/' . $storageDirName . '/screenshot.' . $ext;
                break;
            }
        }

        $imageSearchReplace = [];
        foreach ($images as $image) {
            if (!($src = urldecode($image->attr('src')))) {
                continue;
            }

            $src = (string)str_replace(['../', './', '..\\', '.\\', '..'], '', $src);
            $src = trim((string)$src);
            if (preg_match('/^https?/i', $src) || strpos($src, '//') === 0 || FilterVarHelper::url($src)) {
                continue;
            }

            if (!is_file($entryFilePathDir . '/' . $src)) {
                continue;
            }

            $ext = pathinfo($src, PATHINFO_EXTENSION);
            if (empty($ext) || !in_array(strtolower((string)$ext), $extensions)) {
                continue;
            }
            unset($ext);

            $imageInfo = ImageHelper::getImageSize($entryFilePathDir . '/' . $src);
            if (!is_array($imageInfo) || empty($imageInfo[0]) || empty($imageInfo[1])) {
                continue;
            }

            if (!copy($entryFilePathDir . '/' . $src, $storagePath . '/' . basename($src))) {
                continue;
            }

            if (empty($screenshot)) {
                $foundImages[] = [
                    'name'   => basename($src),
                    'width'  => $imageInfo[0],
                    'height' => $imageInfo[1],
                ];
            }

            $relSrc = 'frontend/assets/gallery/' . $storageDirName . '/' . basename($src);
            $newSrc = apps()->getAppUrl('frontend', $relSrc, true, true);
            if ($this->getCdnSubdomain()) {
                $newSrc = sprintf('%s/%s', $this->getCdnSubdomain(), $relSrc);
            }
            $imageSearchReplace[$image->attr('src')] = $newSrc;
        }

        if (empty($screenshot) && !empty($foundImages)) {
            $largestImage = ['name' => null, 'width' => 0, 'height' => 0];
            foreach ($foundImages as $imageData) {
                if ($imageData['width'] > $largestImage['width'] && $imageData['height'] > $largestImage['height']) {
                    $largestImage = $imageData;
                }
            }

            if (!empty($largestImage['name']) && $largestImage['width'] >= 160 && $largestImage['height'] >= 160) {
                $screenshot = '/frontend/assets/gallery/' . $storageDirName . '/' . $largestImage['name'];
            }
        }

        if (!empty($screenshot)) {
            $this->owner->screenshot        = $screenshot;
            $this->owner->create_screenshot = 'no';
        }

        if (empty($this->owner->name)) {
            $sanitizer = new IndieHD\FilenameSanitizer\FilenameSanitizer($this->owner->archive->name);
            $sanitizer->stripIllegalFilesystemCharacters();
            $this->owner->name = basename((string)$sanitizer->getFilename(), '.zip');
        }

        $sort = [];
        foreach ($imageSearchReplace as $k => $v) {
            $sort[] = strlen((string)$k);
        }
        array_multisort($imageSearchReplace, $sort, SORT_NUMERIC, SORT_DESC);

        $this->owner->content = (string)str_replace(array_keys($imageSearchReplace), array_values($imageSearchReplace), $htmlContent);

        // because bg images escape the above code block and looping each element is out of the question
        // (042 and 047 are octal quotes)
        preg_match_all('/url\((\042|\047)?([a-z0-9_\-\s\.\/]+)(\042|\047)?\)/six', $this->owner->content, $matches);
        if (!empty($matches[2])) {
            foreach ($matches[2] as $src) {
                $originalSrc = $src;

                $src = urldecode($src);
                $src = (string)str_replace(['../', './', '..\\', '.\\', '..'], '', $src);
                $src = trim((string)$src);

                if (preg_match('/^https?/i', $src) || strpos($src, '//') === 0 || FilterVarHelper::url($src)) {
                    continue;
                }

                if (!is_file($entryFilePathDir . '/' . $src)) {
                    $this->owner->content = (string)str_replace($originalSrc, '', $this->owner->content);
                    continue;
                }

                $extensionName = strtolower(pathinfo($src, PATHINFO_EXTENSION));
                if (!in_array($extensionName, (array)app_param('files.images.extensions', []))) {
                    $this->owner->content = (string)str_replace($originalSrc, '', $this->owner->content);
                    continue;
                }

                $imageInfo = ImageHelper::getImageSize($entryFilePathDir . '/' . $src);
                if (!is_array($imageInfo) || empty($imageInfo[0]) || empty($imageInfo[1])) {
                    $this->owner->content = (string)str_replace($originalSrc, '', $this->owner->content);
                    continue;
                }

                if (!copy($entryFilePathDir . '/' . $src, $storagePath . '/' . basename($src))) {
                    $this->owner->content = (string)str_replace($originalSrc, '', $this->owner->content);
                    continue;
                }

                $relSrc = 'frontend/assets/gallery/' . $storageDirName . '/' . basename($src);
                $newSrc = apps()->getAppUrl('frontend', $relSrc, true, true);
                if ($this->getCdnSubdomain()) {
                    $newSrc = sprintf('%s/%s', $this->getCdnSubdomain(), $relSrc);
                }
                $this->owner->content = (string)str_replace($originalSrc, $newSrc, $this->owner->content);
            }
        }

        libxml_use_internal_errors(false);

        FileSystemHelper::deleteDirectoryContents($tmpUploadPath, true, 1);

        $this->owner->content = StringHelper::decodeSurroundingTags($this->owner->content);

        // give a chance for last moment changes
        hooks()->doAction('email_template_upload_behavior_handle_upload_before_save_content', [
            'template'        => $this->owner,
            'originalContent' => $this->owner->content,
            'storagePath'     => $storagePath,
            'storageDirName'  => $storageDirName,
            'cdnSubdomain'    => $this->getCdnSubdomain(),
        ]);

        return $this->owner->save(false);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function afterDelete($event)
    {
        $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.gallery');
        $templatePath = $storagePath . '/' . $this->owner->template_uid;

        if (file_exists($templatePath) && is_dir($templatePath)) {
            FileSystemHelper::deleteDirectoryContents($templatePath, true, 1);
        }
    }

    /**
     * @return string
     */
    protected function getCdnSubdomain(): string
    {
        if ($this->_cdnSubdomain !== null) {
            return $this->_cdnSubdomain;
        }
        $this->_cdnSubdomain = '';

        if (app()->hasComponent('customer') && customer()->getId() && ($customer = customer()->getModel())) {
            if ($customer->getGroupOption('cdn.enabled', 'no') == 'yes' && $customer->getGroupOption('cdn.use_for_email_assets', 'no') == 'yes') {
                $this->_cdnSubdomain = (string)$customer->getGroupOption('cdn.subdomain');
            }
        }

        /** @var OptionCustomerCdn $optionCustomerCdn */
        $optionCustomerCdn = container()->get(OptionCustomerCdn::class);

        if (!$this->_cdnSubdomain &&  $optionCustomerCdn->getIsEnabled() && $optionCustomerCdn->getUseForEmailAssets() && strlen($optionCustomerCdn->getSubdomain())) {
            $this->_cdnSubdomain = $optionCustomerCdn->getSubdomain();
        }

        /** @var OptionCdn $optionCdn */
        $optionCdn = container()->get(OptionCdn::class);

        if (!$this->_cdnSubdomain && $optionCdn->getIsEnabled() && $optionCdn->getUseForEmailAssets() && strlen($optionCdn->getSubdomain())) {
            $this->_cdnSubdomain = $optionCdn->getSubdomain();
        }

        if (!empty($this->_cdnSubdomain) && stripos($this->_cdnSubdomain, 'http') !== 0) {
            $this->_cdnSubdomain = 'http://' . $this->_cdnSubdomain;
        }

        return $this->_cdnSubdomain;
    }
}
