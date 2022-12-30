<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * FileSizeTrait
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

trait FileSizeTrait
{
    /**
     * @return array
     */
    public function getFileSizeOptions(): array
    {
        $options = [];
        $size = 1024 * 1024 * 1;
        $options[$size] = t('settings', '{n} Megabyte|{n} Megabytes', 1);
        for ($i = 2; $i <= 5; ++$i) {
            $size = 1024 * 1024 * $i;
            $options[$size] = t('settings', '{n} Megabyte|{n} Megabytes', $i);
        }
        for ($i = 10; $i <= 50; ++$i) {
            if ($i % 5 == 0) {
                $size = 1024 * 1024 * $i;
                $options[$size] = t('settings', '{n} Megabyte|{n} Megabytes', $i);
            }
        }
        $size = 1024 * 1024 * 100;
        $options[$size] = t('settings', '{n} Megabyte|{n} Megabytes', 100);

        // 1.5.0
        $options = (array)hooks()->applyFilters('get_upload_allowed_max_file_size_options', $options);

        return $options;
    }
}
