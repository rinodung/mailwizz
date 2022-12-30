<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCustomization
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.4
 */

class OptionCustomization extends OptionBase
{
    /**
     * @var string
     */
    public $backend_logo_text = '';

    /**
     * @var string
     */
    public $customer_logo_text = '';

    /**
     * @var string
     */
    public $frontend_logo_text = '';

    /**
     * @var string
     */
    public $backend_skin = 'blue';

    /**
     * @var string
     */
    public $customer_skin = 'blue';

    /**
     * @var string
     */
    public $frontend_skin = 'blue';

    /**
     * @var string
     */
    public $backend_logo;

    /**
     * @var string
     */
    public $backend_logo_up;

    /**
     * @var string
     */
    public $backend_login_bg;

    /**
     * @var string
     */
    public $backend_login_bg_up;

    /**
     * @var string
     */
    public $customer_logo;

    /**
     * @var string
     */
    public $customer_logo_up;

    /**
     * @var string
     */
    public $customer_login_bg;

    /**
     * @var string
     */
    public $customer_login_bg_up;

    /**
     * @var string
     */
    public $frontend_logo;

    /**
     * @var string
     */
    public $frontend_logo_up;

    /**
     * @var string
     */
    protected $_categoryName = 'system.customization';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $mimes = null;
        if (CommonHelper::functionExists('finfo_open')) {

            /** @var FileExtensionMimes $extensionMimes */
            $extensionMimes = app()->getComponent('extensionMimes');

            /** @var array $mimes */
            $mimes = $extensionMimes->get(['png', 'jpg', 'jpeg', 'gif'])->toArray();
        }

        $rules = [
            ['backend_logo_up, customer_logo_up, frontend_logo_up', 'file', 'types' => ['png', 'jpg', 'jpeg', 'gif'], 'mimeTypes' => $mimes, 'allowEmpty' => true],
            ['backend_logo, customer_logo, frontend_logo', '_validateLogoFile'],

            ['backend_login_bg_up, customer_login_bg_up', 'file', 'types' => ['png', 'jpg', 'jpeg', 'gif'], 'mimeTypes' => $mimes, 'allowEmpty' => true],
            ['backend_login_bg, customer_login_bg', '_validateLoginBgFile'],

            ['backend_logo_text, customer_logo_text, frontend_logo_text', 'length', 'max' => 100],
            ['backend_skin, customer_skin, frontend_skin', 'length', 'max' => 100],
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
            'backend_logo_text'  => $this->t('Backend logo text'),
            'customer_logo_text' => $this->t('Customer logo text'),
            'frontend_logo_text' => $this->t('Frontend logo text'),
            'backend_skin'       => $this->t('Backend skin'),
            'customer_skin'      => $this->t('Customer skin'),
            'frontend_skin'      => $this->t('Frontend skin'),
            'backend_logo'       => $this->t('Backend logo'),
            'customer_logo'      => $this->t('Customer logo'),
            'frontend_logo'      => $this->t('Frontend logo'),
            'backend_login_bg'   => $this->t('Backend login background image'),
            'customer_login_bg'  => $this->t('Customer login background image'),
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
            'backend_logo_text'  => t('app', 'Backend area'),
            'customer_logo_text' => t('app', 'Customer area'),
            'frontend_logo_text' => t('app', 'Frontend area'),
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
            'backend_logo_text'  => $this->t('The text shown in backend area as the logo. Leave empty to use the defaults.'),
            'customer_logo_text' => $this->t('The text shown in customer area as the logo. Leave empty to use the defaults.'),
            'frontend_logo_text' => $this->t('The text shown in frontend as the logo. Leave empty to use the defaults.'),
            'backend_skin'       => $this->t('The CSS skin to be used in backend area.'),
            'customer_skin'      => $this->t('The CSS skin to be used in customer area.'),
            'frontend_skin'      => $this->t('The CSS skin to be used in frontend area.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @param array $options
     *
     * @return string
     */
    public static function buildHeaderLogoHtml(array $options = []): string
    {
        $instance = new self();

        if (empty($options['linkUrl']) && apps()->isAppName('frontend')) {
            $options['linkUrl'] = apps()->getAppBaseUrl('frontend', true, true);
        }

        $options  = array_merge([
            'app'       => apps()->getCurrentAppName(),
            'linkUrl'   => createUrl('dashboard/index'),
            'linkClass' => 'logo icon',
        ], $options);

        if ($url = $instance->getLogoUrlByApp($options['app'], 220)) {
            $text = CHtml::image($url, '', ['width' => 220, 'height' => 50]);
        } elseif ($_text = $instance->getLogoTextByApp($options['app'])) {
            $text = $_text;
        } else {
            $text = t('app', ucfirst($options['app']) . ' area');
        }

        return CHtml::link($text, $options['linkUrl'], ['class' => $options['linkClass']]);
    }

    /**
     * @param string $app
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getLogoUrlByApp(string $app, int $width = 50, int $height = 50): string
    {
        $attribute = $app . '_logo';
        if (!isset($this->$attribute) || empty($this->$attribute)) {
            return '';
        }
        return ImageHelper::resize((string)$this->$attribute, $width, $height);
    }

    /**
     * @param string $app
     *
     * @return string
     */
    public function getLogoTextByApp(string $app): string
    {
        $attribute = $app . '_logo_text';
        if (!isset($this->$attribute) || empty($this->$attribute)) {
            return '';
        }
        return (string)$this->$attribute;
    }

    /**
     * @param int $width
     * @param int $height
     * @param bool $forceSize
     * @return string
     */
    public function getBackendLogoUrl(int $width = 50, int $height = 50, bool $forceSize = false): string
    {
        if (empty($this->backend_logo)) {
            return $this->getDefaultLogoUrl($width, $height);
        }
        return ImageHelper::resize((string)$this->backend_logo, $width, $height, $forceSize);
    }

    /**
     * @param int $width
     * @param int $height
     * @param bool $forceSize
     * @return string
     */
    public function getBackendLoginBgUrl(int $width = 50, int $height = 50, bool $forceSize = false): string
    {
        if (empty($this->backend_login_bg)) {
            return $this->getDefaultLoginBgUrl($width, $height);
        }
        return ImageHelper::resize((string)$this->backend_login_bg, $width, $height, $forceSize);
    }

    /**
     * @param int $width
     * @param int $height
     * @param bool $forceSize
     * @return string
     */
    public function getCustomerLogoUrl(int $width = 50, int $height = 50, bool $forceSize = false): string
    {
        if (empty($this->customer_logo)) {
            return $this->getDefaultLogoUrl($width, $height);
        }
        return ImageHelper::resize((string)$this->customer_logo, $width, $height, $forceSize);
    }

    /**
     * @param int $width
     * @param int $height
     * @param bool $forceSize
     * @return string
     */
    public function getCustomerLoginBgUrl(int $width = 50, int $height = 50, bool $forceSize = false)
    {
        if (empty($this->customer_login_bg)) {
            return $this->getDefaultLoginBgUrl($width, $height);
        }
        return ImageHelper::resize((string)$this->customer_login_bg, $width, $height, $forceSize);
    }

    /**
     * @param int $width
     * @param int $height
     * @param bool $forceSize
     * @return string
     */
    public function getFrontendLogoUrl(int $width = 50, int $height = 50, bool $forceSize = false): string
    {
        if (empty($this->frontend_logo)) {
            return $this->getDefaultLogoUrl($width, $height);
        }
        return ImageHelper::resize((string)$this->frontend_logo, $width, $height, $forceSize);
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return string
     */
    public function getDefaultLogoUrl(int $width, int $height): string
    {
        return sprintf('https://via.placeholder.com/%dx%d?text=...', $width, $height);
    }

    /**
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getDefaultLoginBgUrl(int $width, int $height): string
    {
        return ImageHelper::resize('/assets/img/login-background.jpeg', $width, $height);
    }

    /**
     * @param string $appName
     *
     * @return array
     */
    public function getAppSkins(string $appName): array
    {
        $skins = [''];
        $paths = ['root.assets.css', 'root.' . $appName . '.assets.css'];
        foreach ($paths as $path) {
            foreach ((array)glob((string)Yii::getPathOfAlias($path) . '/skin-*.css') as $file) {
                $fileName = basename((string)$file, '.css');
                if (strpos($fileName, 'skin-') === 0) {
                    $skins[] = $fileName;
                }
            }
        }

        $_skins = array_unique($skins);
        $skins  = [];
        foreach ($_skins as $skin) {
            $skinName = (string)str_replace('skin-', '', $skin);
            $skinName = (string)preg_replace('/[^a-z0-9]/i', ' ', $skinName);
            $skinName = ucwords($skinName);
            $skins[$skin] = (string)str_replace(' Min', ' (Minified)', $skinName);
        }
        return $skins;
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateLogoFile(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute) || empty($this->$attribute)) {
            return;
        }

        $fullPath = (string)Yii::getPathOfAlias('root') . $this->$attribute;
        $extensionName = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (!in_array($extensionName, (array)app_param('files.images.extensions', []))) {
            $this->addError($attribute, $this->t('Seems that "{attr}" is not a valid image!', [
                '{attr}' => $this->getAttributeLabel($attribute),
            ]));
            return;
        }

        if (strpos($this->$attribute, '/frontend/assets/files/logos/') !== 0 || !is_file($fullPath) || !($info = ImageHelper::getImageSize($fullPath))) {
            $this->addError($attribute, $this->t('Seems that "{attr}" is not a valid image!', [
                '{attr}' => $this->getAttributeLabel($attribute),
            ]));
            return;
        }
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateLoginBgFile(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute) || empty($this->$attribute)) {
            return;
        }

        $fullPath = (string)Yii::getPathOfAlias('root') . $this->$attribute;

        $extensionName = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (!in_array($extensionName, (array)app_param('files.images.extensions', []))) {
            $this->addError($attribute, $this->t('Seems that "{attr}" is not a valid image!', [
                '{attr}' => $this->getAttributeLabel($attribute),
            ]));
            return;
        }

        if (strpos($this->$attribute, '/frontend/assets/files/login-bg/') !== 0 || !is_file($fullPath) || !($info = ImageHelper::getImageSize($fullPath))) {
            $this->addError($attribute, $this->t('Seems that "{attr}" is not a valid image!', [
                '{attr}' => $this->getAttributeLabel($attribute),
            ]));
            return;
        }
    }

    /**
     * @return string
     */
    public function getBackendSkin(): string
    {
        return (string)$this->backend_skin;
    }

    /**
     * @return string
     */
    public function getCustomerSkin(): string
    {
        return (string)$this->customer_skin;
    }

    /**
     * @return string
     */
    public function getFrontendSkin(): string
    {
        return (string)$this->frontend_skin;
    }

    /**
     * @return void
     */
    protected function afterValidate()
    {
        parent::afterValidate();

        $this
            ->handleUploadedLogo('backend_logo_up', 'backend_logo')
            ->handleUploadedLogo('customer_logo_up', 'customer_logo')
            ->handleUploadedLogo('frontend_logo_up', 'frontend_logo')

            ->handleUploadedLoginBg('backend_login_bg_up', 'backend_login_bg')
            ->handleUploadedLoginBg('customer_login_bg_up', 'customer_login_bg');
    }

    /**
     * @param string $attribute
     * @param string $targetAttribute
     *
     * @return OptionCustomization
     */
    protected function handleUploadedLogo(string $attribute, string $targetAttribute): self
    {
        if ($this->hasErrors()) {
            return $this;
        }

        /** @var CUploadedFile|null $logo */
        $logo = CUploadedFile::getInstance($this, $attribute);

        if (!$logo) {
            return $this;
        }

        $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.files.logos');
        if (!file_exists($storagePath) || !is_dir($storagePath)) {
            if (!mkdir($storagePath, 0777, true)) {
                $this->addError($attribute, $this->t('The logos storage directory({path}) does not exists and cannot be created!', [
                    '{path}' => $storagePath,
                ]));
                return $this;
            }
        }

        $newAvatarName = uniqid(sprintf('%s', rand(0, time()))) . '-' . $logo->getName();
        if (!$logo->saveAs($storagePath . '/' . $newAvatarName)) {
            $this->addError($attribute, t('customers', 'Cannot move the logo into the correct storage folder!'));
            return $this;
        }

        $this->$targetAttribute = '/frontend/assets/files/logos/' . $newAvatarName;
        return $this;
    }

    /**
     * @param string $attribute
     * @param string $targetAttribute
     *
     * @return OptionCustomization
     */
    protected function handleUploadedLoginBg(string $attribute, string $targetAttribute): self
    {
        if ($this->hasErrors()) {
            return $this;
        }

        /** @var CUploadedFile|null $logo */
        $logo = CUploadedFile::getInstance($this, $attribute);

        if (!$logo) {
            return $this;
        }

        $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.files.login-bg');
        if (!file_exists($storagePath) || !is_dir($storagePath)) {
            if (!mkdir($storagePath, 0777, true)) {
                $this->addError($attribute, $this->t('The logos storage directory({path}) does not exists and cannot be created!', [
                    '{path}' => $storagePath,
                ]));
                return $this;
            }
        }

        $newAvatarName = StringHelper::random(8, true) . '-' . $logo->getName();
        if (!$logo->saveAs($storagePath . '/' . $newAvatarName)) {
            $this->addError($attribute, t('customers', 'Cannot move the logo into the correct storage folder!'));
            return $this;
        }

        $this->$targetAttribute = '/frontend/assets/files/login-bg/' . $newAvatarName;
        return $this;
    }
}
