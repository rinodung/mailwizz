<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Mime types list for various extensions used across the application
 *
 * This file should not be altered in any way, instead create and use mimes-custom.php file
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.2
 */

return [
    //ext => mimes
    'zip' => [
        'application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/octet-stream',
        'application/x-compress', 'application/x-compressed', 'multipart/x-zip',
    ],
    'csv' => [
        'text/comma-separated-values', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.ms-excel',
        'application/vnd.msexcel', 'text/anytext', 'text/plain',
    ],
    'txt' => ['application/text', 'text/plain'],
    'jpg' => ['image/jpg', 'image/jpeg'],
    'png' => ['image/png'],
    'gif' => ['image/gif'],
    'pdf' => ['application/pdf', 'application/x-pdf', 'application/acrobat', 'applications/vnd.pdf', 'text/pdf', 'text/x-pdf'],
    'doc' => [
        'application/msword', 'application/doc', 'appl/text', 'application/vnd.msword', 'application/vnd.ms-word', 'application/winword',
        'application/word', 'application/x-msw6', 'application/x-msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],
    'docx'=> [
        'application/msword', 'application/doc', 'appl/text', 'application/vnd.msword', 'application/vnd.ms-word', 'application/winword',
        'application/word', 'application/x-msw6', 'application/x-msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],
    'xls' => [
        'application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/vnd.ms-excel', 'application/x-excel',
        'application/x-dos_ms_excel', 'application/xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],
    'xlsx' => [
        'application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/vnd.ms-excel', 'application/x-excel',
        'application/x-dos_ms_excel', 'application/xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],
    'ppt' => [
        'application/vnd.ms-powerpoint', 'application/mspowerpoint', 'application/ms-powerpoint', 'application/mspowerpnt', 'application/vnd-mspowerpoint',
        'application/powerpoint', 'application/x-powerpoint', 'application/x-m', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ],
    'pptx' => [
        'application/vnd.ms-powerpoint', 'application/mspowerpoint', 'application/ms-powerpoint', 'application/mspowerpnt', 'application/vnd-mspowerpoint',
        'application/powerpoint', 'application/x-powerpoint', 'application/x-m', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ],
];
