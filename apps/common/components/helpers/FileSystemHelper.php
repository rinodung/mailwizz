<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * FileSystemHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class FileSystemHelper
{
    /**
     * @return string
     */
    public static function getTmpDirectory(): string
    {
        static $tempDir;
        if ($tempDir !== null) {
            return $tempDir;
        }

        if (CommonHelper::functionExists('sys_get_temp_dir')) {
            $tmp = @sys_get_temp_dir();
            if (!empty($tmp) && is_dir($tmp) && is_writable($tmp)) {
                return $tempDir = $tmp;
            }
        }

        foreach (['TMP', 'TEMP', 'TMPDIR'] as $evar) {
            if ($tmp = (string)@getenv($evar)) {
                if (file_exists($tmp) && is_dir($tmp) && is_writable($tmp)) {
                    return $tempDir = $tmp;
                }
            }
        }

        $tmp = (string)Yii::getPathOfAlias('common.runtime.tmp');
        if (!file_exists($tmp) || !is_dir($tmp)) {
            mkdir($tmp, 0777, true);
        }

        return $tempDir = $tmp;
    }

    /**
     * @param string $path
     *
     * @return array
     */
    public static function getDirectoryNames(string $path): array
    {
        return array_map('basename', array_values(self::getDirectoriesRecursive($path)));
    }

    /**
     * @param string $path
     * @param int $maxDepth
     *
     * @return array
     */
    public static function getDirectoriesRecursive(string $path, int $maxDepth = 0): array
    {
        $directories = [];

        if (!is_dir($path)) {
            return $directories;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        $iterator->setMaxDepth($maxDepth);

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isDir() || in_array($file->getFilename(), ['.', '..'])) {
                continue;
            }

            $directories[] = $file->__toString();
        }

        return $directories;
    }

    /**
     * @param string $path
     * @param bool $delDir
     * @param int $level
     *
     * @return bool
     */
    public static function deleteDirectoryContents(string $path, bool $delDir = false, int $level = 0): bool
    {
        $path = rtrim((string)$path, DIRECTORY_SEPARATOR);

        if (!($currentDir = opendir($path))) {
            return false;
        }

        while (false !== ($fileName = @readdir($currentDir))) {
            if ($fileName != '.' and $fileName != '..') {
                if (is_dir($path . DIRECTORY_SEPARATOR . $fileName)) {
                    if (substr($fileName, 0, 1) != '.') {
                        self::deleteDirectoryContents($path . DIRECTORY_SEPARATOR . $fileName, $delDir, $level + 1);
                    }
                } else {
                    unlink($path . DIRECTORY_SEPARATOR . $fileName);
                }
            }
        }
        @closedir($currentDir);

        if ($delDir == true and $level > 0) {
            return @rmdir($path);
        }

        return true;
    }

    /**
     * @param string $sourceDir
     * @param bool $includePath
     * @param bool $recursive
     *
     * @return array
     */
    public static function readDirectoryContents(string $sourceDir, bool $includePath = false, bool $recursive = false): array
    {
        static $fileData = [];

        if ($fp = opendir($sourceDir)) {
            if ($recursive === false) {
                $fileData = [];
                $sourceDir = rtrim((string)realpath($sourceDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            }

            while (false !== ($file = readdir($fp))) {
                if (is_dir($sourceDir . $file) && strncmp($file, '.', 1) !== 0) {
                    self::readDirectoryContents($sourceDir . $file . DIRECTORY_SEPARATOR, $includePath, true);
                } elseif (strncmp($file, '.', 1) !== 0) {
                    $fileData[] = $includePath ? $sourceDir . $file : $file;
                }
            }
            return $fileData;
        }
        return [];
    }

    /**
     * @param string $source
     * @param string $destination
     *
     * @return bool
     */
    public static function copyDirectoryContents(string $source, string $destination): bool
    {
        if (!file_exists($source) || !is_dir($source) || !is_readable($source)) {
            return false;
        }

        if ((!file_exists($destination) || !is_dir($destination)) && !mkdir($destination, 0777, true)) {
            return false;
        }

        $result = true;

        /** @var RecursiveDirectoryIterator $iterator */
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {

            /** @var SplFileInfo $file */
            $file = $item;

            if ($file->isDir() && in_array($file->getFilename(), ['.', '..'])) {
                continue;
            }

            if ($file->isDir()) {
                $result = mkdir($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), 0777, true);
            } else {
                $result = copy((string)$file->getRealPath(), $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }

            if (!$result) {
                break;
            }
        }

        return (bool)$result;
    }

    /**
     * @param string $source
     * @param string $destination
     *
     * @return bool
     */
    public static function copyOnlyDirectoryContents(string $source, string $destination): bool
    {
        if (!file_exists($source) || !is_dir($source) || !is_readable($source)) {
            return false;
        }

        if ((!file_exists($destination) || !is_dir($destination)) && !mkdir($destination, 0777, true)) {
            return false;
        }

        $result = true;

        /** @var RecursiveDirectoryIterator $iterator */
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($iterator as $item) {

            /** @var SplFileInfo $file */
            $file = $item;

            if ($file->isDir() && in_array($file->getFilename(), ['.', '..'])) {
                continue;
            }

            if ($file->isDir()) {
                $result = mkdir($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), 0777, true);
            } else {
                $result = copy((string)$file->getRealPath(), $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }

            if (!$result) {
                break;
            }
        }

        return (bool)$result;
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    public static function getFileContents(string $filePath): string
    {
        if (!is_file($filePath)) {
            return '';
        }

        $contents = '';
        if (CommonHelper::functionExists('fopen')) {
            if ($handle = fopen($filePath, 'r')) {
                while (($buffer = fgets($handle, 4096)) !== false) {
                    $contents .= $buffer;
                }
                if (FileSystemHelper::isStreamResource($handle)) {
                    fclose($handle);
                }
            }
            return $contents;
        }

        if (CommonHelper::functionExists('file_get_contents')) {
            return (string)file_get_contents($filePath);
        }

        return '';
    }

    /**
     * @param array $extraAliases
     *
     * @return array
     */
    public static function clearCache(array $extraAliases = []): array
    {
        $messages  = [];
        $gitignore = null;
        if (is_file($filePath = (string)Yii::getPathOfAlias('common.data.gitignore') . '.txt') && is_readable($filePath)) {
            $gitignore = file_get_contents($filePath);
        }

        $aliases = CMap::mergeArray((array)app_param('cache.directory.aliases', []), $extraAliases);
        $aliases = array_unique($aliases);

        foreach ($aliases as $alias) {

            // make sure we only flush cache folders
            if (substr($alias, -5) != 'cache') {
                continue;
            }

            // and procced deleting
            $directory = (string)Yii::getPathOfAlias($alias);
            if (file_exists($directory) && is_dir($directory)) {
                $messages[] = sprintf('Clearing the "%s" directory...', $directory);
                FileSystemHelper::deleteDirectoryContents($directory, true);
                if (!empty($gitignore)) {
                    $messages[] = sprintf('Creating the "%s" file', $directory . '/.gitignore');
                    file_put_contents($directory . '/.gitignore', $gitignore);
                }
            }
        }

        return $messages;
    }

    /**
     * @param mixed $resource
     *
     * @return bool
     */
    public static function isStreamResource($resource): bool
    {
        return is_resource($resource) && get_resource_type($resource) === 'stream';
    }
}
