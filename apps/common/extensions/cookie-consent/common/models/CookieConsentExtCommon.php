<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CookieConsentExtCommon
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class CookieConsentExtCommon extends ExtensionModel
{
    const THEME_EDGELESS = 'edgeless';
    const THEME_BLOCK = 'block';
    const THEME_CLASSIC = 'classic';

    const POSITION_BOTTOM = 'bottom';
    const POSITION_BOTTOM_LEFT = 'bottom-left';
    const POSITION_BOTTOM_RIGHT = 'bottom-right';
    const POSITION_TOP = 'top';
    const POSITION_TOP_LEFT = 'top-left';
    const POSITION_TOP_RIGHT = 'top-right';

    const PALETTE_POPUP_BACKGROUND = '#000000';
    const PALETTE_BUTTON_BACKGROUND = '#f1d600';

    /**
     * @var string
     */
    public $enabled = self::TEXT_NO;

    /**
     * @var string
     */
    public $palette_popup_background = self::PALETTE_POPUP_BACKGROUND;

    /**
     * @var string
     */
    public $palette_button_background = self::PALETTE_BUTTON_BACKGROUND;

    /**
     * @var string
     */
    public $theme = self::THEME_EDGELESS;

    /**
     * @var string
     */
    public $position = self::POSITION_BOTTOM;

    /**
     * @var string
     */
    public $message = 'This website uses cookies to improve your experience.';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['message', 'length', 'max' => 255],
            ['palette_popup_background, palette_button_background', 'length', 'max' => 10],
            ['palette_popup_background', 'default', 'value' => self::PALETTE_POPUP_BACKGROUND],
            ['palette_button_background', 'default', 'value' => self::PALETTE_BUTTON_BACKGROUND],
            ['enabled', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['position', 'in', 'range' => array_keys($this->getPositionOptions())],
            ['theme', 'in', 'range' => array_keys($this->getThemeOptions())],
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
            'enabled'                   => t('app', 'Enabled'),
            'message'                   => $this->t('Message'),
            'position'                  => $this->t('Position'),
            'theme'                     => $this->t('Theme'),
            'palette_popup_background'  => $this->t('Banner background color'),
            'palette_button_background' => $this->t('Button color'),
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
            'message' => $this->t('This website uses cookies to improve your experience.'),
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
            'enabled'  => t('app', 'Whether the feature is enabled'),
            'message'  => $this->t('The message that will appear on your cookie consent banner'),
            'position' => $this->t('Where the cookie consent banner will appear'),
            'theme'    => $this->t('The theme of the cookie consent banner'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @inheritDoc
     */
    public function getCategoryName(): string
    {
        return '';
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return $this->enabled === self::TEXT_YES;
    }

    /**
     * @return array
     */
    public function getThemeOptions(): array
    {
        return [
            self::THEME_EDGELESS => ucfirst(self::THEME_EDGELESS),
            self::THEME_BLOCK    => ucfirst(self::THEME_BLOCK),
            self::THEME_CLASSIC  => ucfirst(self::THEME_CLASSIC),
        ];
    }

    /**
     * @return array
     */
    public function getPositionOptions(): array
    {
        return [
            self::POSITION_BOTTOM       => $this->t(ucfirst((string)str_replace('-', ' ', self::POSITION_BOTTOM))),
            self::POSITION_BOTTOM_LEFT  => $this->t(ucfirst((string)str_replace('-', ' ', self::POSITION_BOTTOM_LEFT))),
            self::POSITION_BOTTOM_RIGHT => $this->t(ucfirst((string)str_replace('-', ' ', self::POSITION_BOTTOM_RIGHT))),
            self::POSITION_TOP          => $this->t(ucfirst((string)str_replace('-', ' ', self::POSITION_TOP))),
            self::POSITION_TOP_LEFT     => $this->t(ucfirst((string)str_replace('-', ' ', self::POSITION_TOP_LEFT))),
            self::POSITION_TOP_RIGHT    => $this->t(ucfirst((string)str_replace('-', ' ', self::POSITION_TOP_RIGHT))),
        ];
    }

    /**
     * @return string
     */
    public function getCookieConsentHtml(): string
    {
        return sprintf('<div id="cookie-consent-wrapper" data-options=\'%s\' style="display:none"></div>', json_encode($this->getParsedOptions(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    }

    /**
     * @see https://www.osano.com/cookieconsent/documentation/javascript-api/
     * @return array
     */
    public function getParsedOptions(): array
    {
        return [
            'palette' => [
                'popup' => [
                    'background' => html_encode($this->palette_popup_background),
                ],
                'button' => [
                    'background' => html_encode($this->palette_button_background),
                ],
            ],
            'theme'    => html_encode($this->theme),
            'position' => html_encode($this->position),
            'content'  => [
                'header'  => $this->t('Cookies used on the website!'),
                'message' => html_encode($this->getMessage()),
                'dismiss' => $this->t('Got it!'),
                'allow'   => $this->t('Allow cookies'),
                'deny'    => $this->t('Decline'),
                'link'    => $this->getCookiePolicyPageUrl()['link'],
                'href'    => $this->getCookiePolicyPageUrl()['href'],
                'close'   => '&#x274c;',
                'policy'  => 'Cookie Policy',
                'target'  => '_blank',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getCookiePolicyPageUrl(): array
    {
        $href = 'https://www.cookiesandyou.com';
        $link = $this->t('Learn more');
        if ($page = Page::findBySlug('cookie-policy')) {
            $href = html_encode((string)$page->getPermalink());
            $link = html_encode((string)$page->title);
        }

        return ['link' => $link, 'href' => $href];
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        if ($this->message) {
            return $this->message;
        }

        return $this->t('This website uses cookies to improve your experience.');
    }
}
