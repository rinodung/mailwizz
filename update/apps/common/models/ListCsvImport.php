<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListCsvImport
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class ListCsvImport extends ListImportAbstract
{
    /**
     * @return array
     */
    public function rules()
    {
        /** @var OptionImporter $optionImporter */
        $optionImporter = container()->get(OptionImporter::class);

        $mimes = null;
        if ($optionImporter->getCanCheckMimeType()) {

            /** @var FileExtensionMimes $extensionMimes */
            $extensionMimes = app()->getComponent('extensionMimes');

            /** @var array $mimes */
            $mimes = $extensionMimes->get('csv')->toArray();
        }

        $rules = [
            ['file', 'required', 'on' => 'upload'],
            ['file', 'file', 'types' => ['csv'], 'mimeTypes' => $mimes, 'maxSize' => $this->file_size_limit, 'allowEmpty' => true],
            ['file_name', 'length', 'is' => 44],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }
}
