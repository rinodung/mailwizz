<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionMonetizationInvoices
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.8
 */

class OptionMonetizationInvoices extends OptionBase
{
    /**
     * @var string
     */
    public $prefix = 'MW-IN ';

    /**
     * @var string
     */
    public $logo = '';

    /**
     * @var string
     */
    public $notes = '';

    /**
     * @var string
     */
    public $email_subject = '';

    /**
     * @var string
     */
    public $email_content = '';

    /**
     * @var string
     */
    public $color_code = '3c8dbc';

    /**
     * @var string
     */
    protected $_categoryName = 'system.monetization.invoices';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $logoMimes = null;
        if (CommonHelper::functionExists('finfo_open')) {

            /** @var FileExtensionMimes $extensionMimes */
            $extensionMimes = app()->getComponent('extensionMimes');

            /** @var array $logoMimes */
            $logoMimes = $extensionMimes->get(['png', 'jpg', 'jpeg', 'gif'])->toArray();
        }

        $rules = [
            ['prefix', 'length', 'min' => 2, 'max' => 255],
            ['logo', 'file', 'types' => ['png', 'jpg', 'jpeg', 'gif'], 'mimeTypes' => $logoMimes, 'allowEmpty' => true],
            ['notes, email_content', 'length', 'min' => 2, 'max' => 10000],
            ['email_subject', 'length', 'min' => 2, 'max' => 255],
            ['color_code', 'match', 'pattern' => '/([a-z0-9]{6})/'],
            ['color_code', 'length', 'is' => 6],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = [
            'prefix'        => $this->t('Prefix'),
            'logo'          => $this->t('Logo'),
            'notes'         => $this->t('Notes'),
            'email_content' => $this->t('Email content'),
            'email_subject' => $this->t('Email subject'),
            'color_code'    => $this->t('Color code'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'prefix'     => 'MW-IN ',
            'color_code' => '3c8dbc',
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'prefix'        => $this->t('The prefix for generated invoices'),
            'logo'          => $this->t('The invoices logo'),
            'notes'         => $this->t('Additional notes shown in the invoice footer'),
            'email_content' => $this->t('When the invoice is emailed, this will be the content that will appear in the email body. Leave it empty to use defaults'),
            'email_subject' => $this->t('When the invoice is emailed, this will be the subject of the email. Leave it empty to use defaults'),
            'color_code'    => $this->t('6 characters length hex color code to be used in the invoice'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @param int $width
     * @param int $height
     * @param bool $forceSize
     *
     * @return string
     */
    public function getLogoUrl(int $width = 100, int $height = 100, bool $forceSize = false): string
    {
        if (empty($this->logo)) {
            return '';
        }
        return ImageHelper::resize((string)$this->logo, $width, $height, $forceSize);
    }

    /**
     * @return string
     */
    public function getLogoPath(): string
    {
        if (empty($this->logo)) {
            return '';
        }
        $logoImage = (string)Yii::getPathOfAlias('root') . urldecode($this->getLogoUrl());

        if (!is_file($logoImage)) {
            return '';
        }

        return $logoImage;
    }

    /**
     * @return string
     */
    public function getLogoPathBase64Encoded(): string
    {
        $logoPath = $this->getLogoPath();
        if (empty($logoPath)) {
            return '';
        }

        $allowedExtensions = ['png', 'jpg', 'jpeg'];
        $extension = strtolower((string)pathinfo($logoPath, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return '';
        }

        $data = FileSystemHelper::getFileContents($logoPath);
        if (empty($data)) {
            return '';
        }

        return 'data:image/' . html_encode($extension) . ';base64,' . base64_encode($data);
    }

    /**
     * @return void
     */
    protected function afterValidate()
    {
        parent::afterValidate();
        $this->handleUploadedLogo();
    }

    /**
     * @return void
     */
    protected function handleUploadedLogo(): void
    {
        if ($this->hasErrors()) {
            return;
        }

        /** @var CUploadedFile|null $logo */
        $logo = CUploadedFile::getInstance($this, 'logo');

        if (!$logo) {
            return;
        }

        $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.files.invoices');
        if (!file_exists($storagePath) || !is_dir($storagePath)) {
            if (!mkdir($storagePath, 0777, true)) {
                $this->addError('logo', $this->t('The invoices storage directory({path}) does not exists and cannot be created!', [
                    '{path}' => $storagePath,
                ]));
                return;
            }
        }

        $newLogoName = StringHelper::random(8, true) . '-' . $logo->getName();
        if (!$logo->saveAs($storagePath . '/' . $newLogoName)) {
            $this->addError('logo', $this->t('Cannot move the avatar into the correct storage folder!'));
            return;
        }

        $this->logo = '/frontend/assets/files/invoices/' . $newLogoName;
    }
}
