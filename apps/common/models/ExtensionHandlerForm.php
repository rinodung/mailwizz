<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ExtensionHandlerForm
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class ExtensionHandlerForm extends FormModel
{
    /**
     * @var CUploadedFile|null
     */
    public $archive;

    /**
     * @return array
     */
    public function rules()
    {
        $mimes = null;
        if (CommonHelper::functionExists('finfo_open')) {

            /** @var FileExtensionMimes $extensionMimes */
            $extensionMimes = app()->getComponent('extensionMimes');

            /** @var array $mimes */
            $mimes = $extensionMimes->get('zip')->toArray();
        }

        $rules = [
            // array('archive', 'required', 'on' => 'upload'),
            ['archive', 'unsafe'],
            ['archive', 'file', 'types' => ['zip'], 'mimeTypes' => $mimes, 'allowEmpty' => true],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'archive'   => t('app', 'Archive'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return bool
     */
    public function upload(): bool
    {
        // no reason to go further if there are errors.
        if (!$this->validate()) {
            return false;
        }

        // we need the zip archive class, cannot work without.
        if (!class_exists('ZipArchive', false)) {
            $this->addError('archive', t('app', 'ZipArchive class required in order to unzip the file.'));
            return false;
        }

        $zip = new ZipArchive();
        if (empty($this->archive) || !$zip->open($this->archive->tempName)) {
            $this->addError('archive', t('app', 'Cannot open the archive file.'));
            return false;
        }

        $extensionsDir = (string)Yii::getPathOfAlias('extensions');
        if ((!file_exists($extensionsDir) || !is_dir($extensionsDir)) && !mkdir($extensionsDir, 0777, true)) {
            $this->addError('archive', t('app', 'Cannot create directory "{dirPath}". Make sure the parent directory is writable by the webserver!', ['{dirPath}' => $extensionsDir]));
            return false;
        }

        if (!is_writable($extensionsDir)) {
            $this->addError('archive', t('app', 'The directory "{dirPath}" is not writable by the webserver!', ['{dirPath}' => $extensionsDir]));
            return false;
        }

        $zip->extractTo($extensionsDir);
        $zip->close();

        return true;
    }

    /**
     * @param bool $coreExtensions
     *
     * @return CArrayDataProvider
     */
    public function getDataProvider(bool $coreExtensions = false): CArrayDataProvider
    {
        $em = extensionsManager();

        /** @var ExtensionInit[] $extensions */
        $extensions = ($coreExtensions === false) ? $em->getExtensions() : $em->getCoreExtensions();

        /** @var ExtensionInit[] $extensions */
        $exts = [];

        foreach ($extensions as $ext) {
            $description = html_encode($ext->description);
            $name = html_encode($ext->name);
            if ($ext->getIsEnabled() && $ext->getPageUrl()) {
                $name = CHtml::link($name, $ext->getPageUrl());
            }
            $exts[] = [
                'id'            => $ext->getDirName(),
                'name'          => $name,
                'description'   => $description,
                'version'       => $ext->version,
                'author'        => $ext->email ? CHtml::link(html_encode($ext->author), 'mailto:' . html_encode($ext->email)) : html_encode($ext->author),
                'website'       => $ext->website ? CHtml::link(t('extensions', 'Visit website'), html_encode($ext->website), ['target' => '_blank']) : null,
                'enabled'       => $ext->getIsEnabled(),
                'pageUrl'       => $ext->getPageUrl(),
                'canBeDeleted'  => $ext->getCanBeDeleted(),
                'canBeDisabled' => $ext->getCanBeDisabled(),
                'mustUpdate'    => $ext->getMustUpdate(),
            ];
        }

        return new CArrayDataProvider($exts, [
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);
    }
}
