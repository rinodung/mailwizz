<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * MigrateLanguageMessagesCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0
 *
 */

class MigrateLanguageMessagesCommand extends ConsoleCommand
{
    /**
     * @var int
     */
    public $verbose = 1;

    /**
     * @return int
     */
    public function actionIndex()
    {
        hooks()->doAction('console_command_migrate_language_messages_before_process', $this);

        $result = $this->process();

        hooks()->doAction('console_command_migrate_language_messages_after_process', $this);

        return $result;
    }

    /**
     * @return int
     */
    protected function process()
    {
        $messagesPath = (string)Yii::getPathOfAlias('common.messages');

        if ((!file_exists($messagesPath) || !is_dir($messagesPath)) && !is_readable($messagesPath)) {
            $this->stdout(sprintf('Please make sure the folder "%s" is readable!', $messagesPath));
            return 0;
        }

        /** @var array $languages */
        $languages = FileSystemHelper::getDirectoryNames($messagesPath);

        foreach ($languages as $language) {
            $languagePath = $messagesPath . '/' . $language;

            $this->stdout(sprintf('Processing language - "%s"', $language));

            $result = TranslationHelper::importFromPhpFiles($languagePath);

            if (!$result['success']) {
                $this->stdout(sprintf('Language "%s" was not imported', $language));
                if ($result['error']) {
                    $this->stdout(implode(PHP_EOL, $result['errors']));
                }
                continue;
            }

            $output = sprintf('Language "%s" was imported successfully!', $language);
            if ($result['error']) {
                $output = sprintf('Language "%s" was imported successfully but with the following errors:', $language) . PHP_EOL;
                $output .= implode(PHP_EOL, $result['errors']);
            }

            $this->stdout($output);
        }
        return 0;
    }
}
