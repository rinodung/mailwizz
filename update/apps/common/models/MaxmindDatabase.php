<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * MaxmindDatabase
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.5
 */

class MaxmindDatabase extends FormModel
{
    /**
     * @return CArrayDataProvider
     */
    public function getDataProvider(): CArrayDataProvider
    {
        return new CArrayDataProvider([
            [
                'id'     => strtolower((string)basename((string)app_param('ip.location.maxmind.db.path', ''))),
                'name'   => basename((string)app_param('ip.location.maxmind.db.path', '')),
                'path'   => (string)app_param('ip.location.maxmind.db.path', ''),
                'url'    => (string)app_param('ip.location.maxmind.db.url', ''),
                'exists' => is_file((string)app_param('ip.location.maxmind.db.path', '')),
            ],
        ]);
    }

    /**
     * Add error message
     *
     * @return void
     */
    public static function addNotifyErrorIfMissingDbFile()
    {
        if (is_file((string)app_param('ip.location.maxmind.db.path', ''))) {
            return;
        }

        $errorMessage = [
            t('ip_location', 'The database file which should be located at "{path}" is missing!', ['{path}' => app_param('ip.location.maxmind.db.path', '')]),
            t('ip_location', 'Please download latest version from {link}, decompress it and place the resulted .mmdb file to be accessible at the above path!', [
                '{link}' => CHtml::link(t('ip_location', 'Maxmind\'s site'), app_param('ip.location.maxmind.db.url', ''), ['target' => '_blank']),
            ]),
        ];
        notify()->addError($errorMessage);
    }
}
