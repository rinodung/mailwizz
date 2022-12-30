<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ImageHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class ImageHelper
{
    /**
     * @param string $imageFilePath
     * @param int $width
     * @param int $height
     * @param bool $forceSize
     *
     * @return string
     */
    public static function resize(string $imageFilePath, int $width = 0, int $height = 0, bool $forceSize = false): string
    {
        if (!extension_loaded('gd') || !CommonHelper::functionExists('gd_info')) {
            Yii::log(sprintf('Called %s but GD is not loaded!', __METHOD__), CLogger::LEVEL_ERROR);
            return '';
        }

        $_imageFilePath = rawurldecode($imageFilePath);
        if (false === ($_imageFilePath = realpath((string)Yii::getPathOfAlias('root') . '/' . ltrim((string)$imageFilePath, '/')))) {
            $_imageFilePath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim((string)$imageFilePath, '/');
        }

        $imageFilePath = (string)str_replace('\\', '/', $_imageFilePath);

        $extension = pathinfo($imageFilePath, PATHINFO_EXTENSION);
        if (empty($extension) || !in_array($extension, (array)app_param('files.images.extensions', []))) {
            return '';
        }

        $extensionName = strtolower(pathinfo($imageFilePath, PATHINFO_EXTENSION));
        if (!in_array($extensionName, (array)app_param('files.images.extensions', []))) {
            return '';
        }

        if (!is_file($imageFilePath) || !($imageInfo = self::getImageSize($imageFilePath))) {
            return '';
        }

        [$originalWidth, $originalHeight] = $imageInfo;

        $width  = (int)$width  > 0 ? (int)$width : (int)$originalWidth;
        $height = (int)$height > 0 ? (int)$height : (int)$originalHeight;

        if (empty($width) && empty($height)) {
            return '';
        }

        if (empty($width)) {
            $width = (int)(floor($originalWidth * $height / $originalHeight));
        } elseif (empty($height)) {
            $height = (int)(floor($originalHeight * $width / $originalWidth));
        }

        $md5File    = (string)md5_file($imageFilePath);
        $filePrefix = substr($md5File, 0, 2) . substr($md5File, 10, 2) . substr($md5File, 20, 2) . substr($md5File, 30, 2);

        $baseResizeUrl  = apps()->getAppUrl('frontend', 'frontend/assets/files/resized/' . $width . 'x' . $height, false, true) . '/';
        $baseResizePath = (string)Yii::getPathOfAlias('root.frontend.assets.files.resized.' . $width . 'x' . $height);

        $imageName      = $filePrefix . '-' . basename($imageFilePath);
        $alreadyResized = $baseResizePath . '/' . $imageName;

        $oldImageLastModified = @filemtime($imageFilePath);
        $newImageLastModified = 0;

        if ($isAlreadyResized = is_file($alreadyResized)) {
            $newImageLastModified = @filemtime($alreadyResized);
        }

        if ($isAlreadyResized && self::getImageSize($alreadyResized) && $oldImageLastModified < $newImageLastModified) {
            return $baseResizeUrl . rawurlencode($imageName);
        }

        if (!file_exists($baseResizePath) && !mkdir($baseResizePath, 0777, true)) {
            return '';
        }

        // since 1.5.2 - if the sizes are larger than the original image, just copy the image over
        if ($width >= $originalWidth && $height >= $originalHeight) {
            if (copy($imageFilePath, $baseResizePath . '/' . $imageName)) {
                return $baseResizeUrl . rawurlencode($imageName);
            }
        }

        try {
            $thumb = new PHPThumbGD($imageFilePath);

            if (!$forceSize) {
                $thumb->adaptiveResize($width, $height);
            } else {
                $thumb->resize($width, $height);
            }

            $thumb->save($baseResizePath . '/' . $imageName);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            return '';
        }

        return $baseResizeUrl . rawurlencode($imageName);
    }

    /**
     * This is a wrapper over getimagesize with better checks against the file we are testing
     *
     * @param string $file
     *
     * @return array|false
     */
    public static function getImageSize(string $file)
    {
        if (empty($file) || !is_file($file)) {
            return false;
        }

        $extension = (string)pathinfo($file, PATHINFO_EXTENSION);
        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'gif'])) {
            return false;
        }

        if (CommonHelper::functionExists('finfo_open')) {
            $info = finfo_open(FILEINFO_MIME_TYPE);
            if (!$info) {
                return false;
            }

            $mimeType = finfo_file($info, $file);

            finfo_close($info);

            $isImage = is_string($mimeType) && stripos($mimeType, 'image/') === 0;

            if (!$isImage) {
                return false;
            }
        }

        $info = getimagesize($file);
        return is_array($info) &&
               !empty($info[0]) &&
               !empty($info[1]) &&
               (int)$info[0] > 0 &&
               (int)$info[1] > 0 &&
               !empty($info['mime']) ? $info : false;
    }
}
