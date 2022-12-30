<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * GetExtensionInstanceFromCurrentClassInstanceTrait
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

trait GetExtensionInstanceFromCurrentClassInstanceTrait
{
    /**
     * @var array
     */
    private static $_foundExtensionInstancesFromCurrentClassInstance = [];

    /**
     * @return ExtensionInit
     * @throws ReflectionException
     * @throws Exception
     */
    public function getExtensionInstanceFromCurrentClassInstance(): ExtensionInit
    {
        /** @var ExtensionInit $extension */
        $extension = null;

        /** @var array $fileParts */
        $fileParts = explode(DIRECTORY_SEPARATOR, (string)(new ReflectionClass($this))->getFileName());

        /** throw away the file name */
        array_pop($fileParts);

        /** @var int $maxLevels */
        $maxLevels = 10;

        /** @var int $currentLevel */
        $currentLevel = 0;

        /** @var int $levelsTried */
        $levelsTried = 0;

        /** @var array $reservedWords */
        $reservedWords = [
            'common', 'models', 'behaviors', 'views', 'controllers', 'components', 'traits', 'utils',
            'composer', 'lib', 'inc', '_info', 'data', 'vendor', 'vendors', 'helpers',
        ];
        $reservedWords = CMap::mergeArray($reservedWords, apps()->getAvailableApps());

        while (!empty($fileParts)) {
            if ($currentLevel > $maxLevels) {
                break;
            }
            $currentLevel++;

            $dirName = array_pop($fileParts);
            if (empty($dirName)) {
                break;
            }

            /** if it's cached already */
            if (in_array($dirName, self::$_foundExtensionInstancesFromCurrentClassInstance)) {
                $extension = extensionsManager()->getExtensionInstance($dirName);
                break;
            }

            /** make sure we jump over some common words */
            if (in_array($dirName, $reservedWords)) {
                continue;
            }
            $levelsTried++;

            $className  = StringHelper::simpleCamelCase($dirName);
            $className .= 'Ext';

            $currentDir = implode(DIRECTORY_SEPARATOR, $fileParts) . DIRECTORY_SEPARATOR . $dirName;

            /** make sure we don't go higher than the webroot */
            if (stripos($currentDir, MW_ROOT_PATH) !== 0) {
                break;
            }

            $initFilePath = $currentDir . DIRECTORY_SEPARATOR . $className . '.php';
            if (!is_file($initFilePath)) {
                continue;
            }

            /** @var ExtensionInit $extension */
            $extension = extensionsManager()->getExtensionInstance($dirName);

            /** cache it */
            self::$_foundExtensionInstancesFromCurrentClassInstance[] = $dirName;

            break;
        }

        if (empty($extension)) {
            throw new Exception(
                'Unable to locate the required extension instance, please override 
				"' . get_called_class() . '::getExtension()" to return a proper ExtensionInit instance!'
            );
        }

        return $extension;
    }
}
