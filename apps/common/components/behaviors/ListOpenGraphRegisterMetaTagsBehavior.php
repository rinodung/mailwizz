<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListOpenGraphRegisterMetaTagsBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.10
 */

/**
 * @property ActiveRecord $owner
 */
class ListOpenGraphRegisterMetaTagsBehavior extends CBehavior
{
    /**
     * @param CComponent $owner
     *
     * @return void
     * @throws CException
     */
    public function attach($owner)
    {
        if (!($owner instanceof Lists)) {
            throw new CException(t('customers', 'The {className} behavior can only be attach to a Lists model', [
                '{className}' => get_class($this),
            ]));
        }

        parent::attach($owner);
    }

    /**
     * @return void
     */
    public function registerMetaTags(): void
    {
        /** @var Lists $list */
        $list = $this->getOwner();

        $ogTitle       = !empty($list->openGraph) && !empty($list->openGraph->title) ? $list->openGraph->title : $list->display_name;
        $ogDescription = !empty($list->openGraph) && !empty($list->openGraph->description) ? $list->openGraph->description : $list->description;
        $ogUrl         = createAbsoluteUrl('lists/subscribe', ['list_uid' => $list->list_uid]);
        $ogImage       = !empty($list->openGraph) && !empty($list->openGraph->image) ? apps()->getAppUrl('frontend', $list->openGraph->image, true, true) : '';

        clientScript()->registerMetaTag('summary', 'twitter:card');
        clientScript()->registerMetaTag(parse_url($ogUrl, PHP_URL_HOST), 'twitter:site');
        clientScript()->registerMetaTag(html_encode($ogTitle), 'twitter:title');
        clientScript()->registerMetaTag(html_encode($ogDescription), 'twitter:description');

        if (!empty($ogImage)) {
            clientScript()->registerMetaTag(html_encode($ogImage), 'twitter:image');
            clientScript()->registerMetaTag(html_encode($ogImage), null, null, ['property' => 'og:image']);
        }

        clientScript()->registerMetaTag('website', null, null, ['property' => 'og:type']);
        clientScript()->registerMetaTag(html_encode($ogUrl), null, null, ['property' => 'og:url']);
        clientScript()->registerMetaTag(html_encode($ogTitle), null, null, ['property' => 'og:title']);
        clientScript()->registerMetaTag(html_encode($ogDescription), null, null, ['property' => 'og:description']);
    }
}
