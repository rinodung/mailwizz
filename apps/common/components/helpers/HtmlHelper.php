<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * HtmlHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5
 */

class HtmlHelper extends CHtml
{
    /**
     * @param string $text
     * @param string|array $url
     * @param array $htmlOptions
     *
     * @return string
     */
    public static function accessLink(string $text, $url = '#', array $htmlOptions = []): string
    {
        $fallbackText = false;
        if (isset($htmlOptions['fallbackText'])) {
            $fallbackText = (bool)$htmlOptions['fallbackText'];
            unset($htmlOptions['fallbackText']);
        }

        if (is_array($url) && apps()->isAppName('backend') && app()->hasComponent('user') && user()->getId() && user()->getModel()) {
            if (!user()->getModel()->hasRouteAccess($url[0])) {
                return $fallbackText ? $text : '';
            }
        }

        return self::link($text, $url, $htmlOptions);
    }

    /**
     * @param string $content
     *
     * @return string
     */
    public static function fixDragAndDropBuilderMarkup(string $content): string
    {
        // handle orphaned 'draggable' attribute which blocks text selection
        return (string)preg_replace('/draggable(\s+)?=(\s+)?(\042|\047)?(true)(\042|\047)?/six', '', $content);
    }

    /**
     * @param int $customerId
     * @param string $linkText
     * @param string $resourceRelativeUrl
     * @param array $linkHtmlOptions
     *
     * @return string
     * @throws CException
     */
    public static function backendCreateCustomerResourceLink(int $customerId, string $linkText, string $resourceRelativeUrl, array $linkHtmlOptions = []): string
    {
        if (!apps()->isAppName('backend') || !app()->hasComponent('user') || !user()->getId() || !user()->getModel()) {
            return CHtml::link($linkText, 'javascript:;', $linkHtmlOptions);
        }

        if (!user()->getModel()->hasRouteAccess('customers/impersonate')) {
            return CHtml::link($linkText, 'javascript:;', $linkHtmlOptions);
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $returnUrl = sprintf('%s%s', request()->getHostInfo(), request()->getUrl());

        // Otherwise, it takes the ajax url, which does not help return to the right page
        if (request()->getIsAjaxRequest()) {
            $returnUrl = html_encode((string)request()->getServer('HTTP_REFERER', $returnUrl));
        }

        return CHtml::link(
            $linkText,
            [
                'customers/impersonate',
                'id'             => $customerId,
                'redirectUrl'    => urlencode($optionUrl->getCustomerUrl($resourceRelativeUrl)),
                'returnUrl'      => urlencode($returnUrl),
            ],
            CMap::mergeArray($linkHtmlOptions, [
                'onclick' => new CJavaScriptExpression(
                    sprintf("return confirm('%s');", html_encode(t('app', 'Accessing this resource require to impersonate its owner. Proceed?')))
                ),
            ])
        );
    }
}
