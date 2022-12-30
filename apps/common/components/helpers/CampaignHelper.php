<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class CampaignHelper
{
    /**
     * @param string $content
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     * @param bool $appendBeacon
     * @param DeliveryServer|null $server
     *
     * @return array
     * @throws CException
     */
    public static function parseContent(string $content, Campaign $campaign, ListSubscriber $subscriber, bool $appendBeacon = false, ?DeliveryServer $server = null): array
    {
        $content = StringHelper::decodeSurroundingTags($content);
        $content = HtmlHelper::fixDragAndDropBuilderMarkup($content);

        $searchReplace = self::getCommonTagsSearchReplace($content, $campaign, $subscriber, $server);
        $content       = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $content);
        $content       = self::getTagFilter()->apply($content, $searchReplace);

        $to      = $searchReplace['[CAMPAIGN_TO_NAME]'] ?? '';
        $subject = $searchReplace['[CAMPAIGN_SUBJECT]'] ?? '';

        // tags with params, if any...
        $searchReplace  = [];
        if (preg_match_all('/\[([a-z_]+)([^\]]+)?\]/i', $content, $matches)) {
            $matches = array_unique($matches[0]);
            foreach ($matches as $tag) {
                if (strpos($tag, '[DATETIME') === 0) {
                    $searchReplace[$tag] = self::parseDateTimeTag($tag);
                } elseif (strpos($tag, '[DATE') === 0) {
                    $searchReplace[$tag] = self::parseDateTag($tag);
                }
            }
        }

        /**
         * This is where we replace the markers from CampaignHelper::transformLinksForTracking()
         * This is the only place to replace the markers that won't affect the performance
         *
         * @since 1.4.3
         * @see CampaignHelper::transformLinksForTracking()
         */
        if (!empty($server)) {
            if ($server->type == 'elasticemail-web-api' || preg_match('/smtp(\d+)?\.elasticemail\.com/i', $server->hostname)) {
                $unsubscribeTags = ['_UNSUBSCRIBE_URL_', '_DIRECT_UNSUBSCRIBE_URL_', '_UNSUBSCRIBE_FROM_CUSTOMER_URL_'];
                foreach ($unsubscribeTags as $unsubscribeTag) {
                    $pattern = sprintf('/data-unsubtag="%s" href(\s+)?=(\s+)?(\042|\047)((\s+)?(.*?)(\s+)?)(\042|\047)/i', $unsubscribeTag);
                    if (!preg_match_all($pattern, $content, $matches)) {
                        continue;
                    }
                    $pattern = '/href(\s+)?=(\s+)?(\042|\047)((\s+)?(.*?)(\s+)?)(\042|\047)/i';
                    $markup  = array_unique($matches[0]);
                    foreach ($markup as $mkp) {
                        $_mkp = (string)str_replace(sprintf('data-unsubtag="%s"', $unsubscribeTag), '', $mkp);
                        $_mkp = trim((string)$_mkp);
                        $_mkp = preg_replace($pattern, 'href="{unsubscribe:$6}"', $_mkp);
                        $searchReplace[$mkp] = $_mkp;
                    }
                }
            }
        }
        //

        if (!empty($searchReplace)) {
            $content = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $content);
        }

        // 1.4.4
        if (!empty($subject)) {
            $searchReplace = self::getCommonTagsSearchReplace($subject, $campaign, $subscriber, $server);
            if (!empty($searchReplace)) {
                $subject = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $subject);
            }
        }
        //

        unset($searchReplace);

        if ($appendBeacon && !empty($subscriber->subscriber_id) && !self::contentHasOpenTrackingBeacon($content, $campaign, $subscriber)) {
            $beaconImage = self::getOpenTrackingBeacon($campaign, $subscriber);
            $content     = str_ireplace('</body>', $beaconImage . "\n" . '</body>', $content);
        }

        return [$to, $subject, $content];
    }

    /**
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     *
     * @return string
     */
    public static function getOpenTrackingBeaconUrl(Campaign $campaign, ListSubscriber $subscriber): string
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        return $optionUrl->getFrontendUrl('campaigns/' . $campaign->campaign_uid . '/track-opening/' . $subscriber->subscriber_uid);
    }

    /**
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     *
     * @return string
     */
    public static function getOpenTrackingBeacon(Campaign $campaign, ListSubscriber $subscriber): string
    {
        return CHtml::image(self::getOpenTrackingBeaconUrl($campaign, $subscriber), '', ['width' => 1, 'height' => 1]);
    }

    /**
     * @param string $content
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     *
     * @return bool
     */
    public static function contentHasOpenTrackingBeacon(string $content, Campaign $campaign, ListSubscriber $subscriber): bool
    {
        return stripos($content, self::getOpenTrackingBeacon($campaign, $subscriber)) !== false;
    }

    /**
     * @param string $content
     * @param array $templateVariables
     *
     * @return string
     */
    public static function parseByTemplateEngine(string $content, array $templateVariables = []): string
    {
        try {
            $data = [];
            foreach ($templateVariables as $key => $value) {
                $data[str_replace(['[', ']'], '', $key)] = $value;
            }

            // 1.6.9 - hidden chars cleanup
            $specialCharsMap = [
                chr(194) . chr(160) => ' ', // hidden \u00a0
            ];
            $twigContent = (string)str_replace(array_keys($specialCharsMap), array_values($specialCharsMap), $content);
            //

            $template = TwigHelper::getInstance()->createTemplate($twigContent);
            $_content = $template->render($data);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            $_content = null;
        }

        return $_content ? $_content : $content;
    }

    /**
     * @return bool
     */
    public static function isTemplateEngineEnabled(): bool
    {
        static $enabled;
        if ($enabled !== null) {
            return $enabled;
        }

        /** @var OptionCampaignTemplateEngine $optionCampaignTemplateEngine */
        $optionCampaignTemplateEngine = container()->get(OptionCampaignTemplateEngine::class);

        return $enabled = $optionCampaignTemplateEngine->getIsEnabled();
    }

    /**
     * @param string $content
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     * @param bool $canSave
     * @param bool $isPlainText
     *
     * @return string
     * @throws Exception
     */
    public static function transformLinksForTracking(string $content, Campaign $campaign, ListSubscriber $subscriber, bool $canSave = false, bool $isPlainText = false): string
    {
        static $trackingUrls = [];
        static $trackingUrlsSaved = [];

        $content = StringHelper::decodeSurroundingTags($content);
        $content = StringHelper::normalizeUrlsInContent($content);

        // since 2.1.4
        $content = self::contentHandleLinkTagsMapping($content);

        $list     = $campaign->list;
        $cacheKey = sha1('tracking_urls_for_' . $campaign->campaign_uid . '_' . $content);

        // first try
        if (($_content = cache()->get($cacheKey)) !== false) {
            return $_content;
        }

        // this can take a while
        if (!mutex()->acquire($cacheKey, 120)) {

            // in case it has been written in these 120 seconds interval by a parallel process
            if (($_content = cache()->get($cacheKey)) !== false) {
                return $_content;
            }

            return $content;
        }

        // meanwhile, might have been set by a parallel process
        if (($_content = cache()->get($cacheKey)) !== false) {

            // release mutex
            mutex()->release($cacheKey);

            return $_content;
        }

        // since 1.3.5.9
        hooks()->doAction('campaign_content_before_transform_links_for_tracking', $collection = new CAttributeCollection([
            'content'       => $content,
            'campaign'      => $campaign,
            'subscriber'    => $subscriber,
            'list'          => $list,
            'trackingUrls'  => $trackingUrls,
            'cacheKey'      => $cacheKey,
        ]));

        /** @var string $content */
        $content = (string)$collection->itemAt('content');

        // since 2.1.4
        $content = self::contentHandleLinkTagsMapping($content);

        /** @var array $trackingUrls */
        $trackingUrls = (array)$collection->itemAt('trackingUrls');

        if (!isset($trackingUrls[$cacheKey])) {
            /** @var OptionUrl $optionUrl */
            $optionUrl = container()->get(OptionUrl::class);

            $trackingUrls[$cacheKey] = [];
            $baseUrl                 = $optionUrl->getFrontendUrl();
            $trackingUrl             = $baseUrl . 'campaigns/[CAMPAIGN_UID]/track-url/[SUBSCRIBER_UID]';

            /**
             * Since the template is cached, we need to add some markers that we will recognize later
             * as the unubscribe urls to replace them with proper tags.
             * These markers will be later replaced in CampaignHelper::parseContent()
             *
             * We're doing this mainly for ElasticEmail because otherwise it inserts it's own {unsubscribe} tag
             * where it wants.
             *
             * @since 1.4.3
             * @see CampaignHelper::parseContent()
             */
            if (!$isPlainText) {
                $unsubscribeTags = ['UNSUBSCRIBE_URL', 'DIRECT_UNSUBSCRIBE_URL', 'UNSUBSCRIBE_FROM_CUSTOMER_URL'];
                foreach ($unsubscribeTags as $unsubscribeTag) {
                    $unsubSearchReplace = [
                        sprintf('href="[%s]"', $unsubscribeTag) => sprintf('data-unsubtag="_%1$s_" href="[%1$s]"', $unsubscribeTag),
                        sprintf("href='[%s]'", $unsubscribeTag) => sprintf('data-unsubtag="_%1$s_" href="[%1$s]"', $unsubscribeTag),
                    ];
                    $content = (string)str_replace(array_keys($unsubSearchReplace), array_values($unsubSearchReplace), $content);
                }
            }
            //

            if (!$isPlainText) {
                // Previous pattern
                // href(\s+)?=(\s+)?(\042|\047)(\s+)?(.*?)(\s+)?(\042|\047)
                // since 2.1.11 - We get only the <a> tags
                // (\042|\047) are octal quotes.
                $pattern = '/<a((?!href).*)?(href(\s+)?=(\s+)?(\042|\047)(\s+)?(.*?)(\s+)?(\042|\047))/i';
            } else {
                $pattern = '/https?:\/\/([^\s]+)/im';
            }

            if (!preg_match_all($pattern, $content, $matches)) {

                // cache content
                cache()->set($cacheKey, $content);

                // release mutex
                mutex()->release($cacheKey);

                return $content;
            }

            if (!$isPlainText) {
                $urls = $matches[7];
            } else {
                $urls = $matches[0];
            }
            $urls = array_map('trim', $urls);

            // combine url with markup
            $urls = (array)array_combine($urls, $matches[2]);
            $foundUrls = [];

            foreach ($urls as $url => $markup) {

                // since 1.3.6.3
                $url = StringHelper::normalizeUrl((string)$url);

                // since 2.0.32 - if this url is already transformed for this campaign and subscriber, skip it
                $patternUrl = sprintf(
                    '%s/%s/track-url/%s/',
                    $baseUrl . 'campaigns',
                    $campaign->campaign_uid,
                    $subscriber->subscriber_uid
                );
                $pattern = '/^(' . preg_quote($patternUrl, '/') . ')([a-f0-9]{40})/i';
                if (preg_match($pattern, $url)) {
                    continue;
                }
                //

                // external url which may contain one or more tags(sharing maybe?)
                if (preg_match('/https?.*/i', $url, $matches) && FilterVarHelper::url($url)) {
                    $_url = trim((string)$matches[0]);
                    $foundUrls[$_url] = $markup;
                    continue;
                }

                // since 1.7.8
                if (preg_match('/tel:(.*)/i', $url, $matches) && FilterVarHelper::phoneUrl($url)) {
                    $_url = trim((string)$matches[0]);
                    $foundUrls[$_url] = $markup;
                    continue;
                }

                // since 1.7.8
                if (preg_match('/mailto:(.*)/i', $url, $matches) && FilterVarHelper::mailtoUrl($url)) {
                    $_url = trim((string)$matches[0]);
                    $foundUrls[$_url] = $markup;
                    continue;
                }

                /**
                 * Local tag to be transformed
                 *
                 * @since 2.1.4 - the pattern has been changed from  "/^\[([A-Z0-9:_]+)_URL\]$/i" to "^\[(.*)?\]$".
                 * This way, we catch any tag in the href attribute, which might actually contain a URL.
                 * The issue with this approach is that if the TAG value is empty (or not a URL) when the tracking link is clicked,
                 * there will be no place to redirect, so the subscriber will end up on a 404 page.
                 * The advantage is that we don't store a different link for each TAG value, but we store only the tag
                 * itself, and we parse it at redirect time.
                 * The pattern will allow things like: [TAG] but also things like: [TAG_1]/[TAG_2]/[ETC].
                 * Not sure yet if this is a good idea or not, we can limit it to allow only a tag by using: "^\[[^\]]+\]$"
                 *
                 * @since 2.1.6 - we added the ability to switch between above behaviors from configuration
                 */
                $pattern = '/^\[(.*)?\]$/';
                // since 2.1.6
                if ((bool)app_param('campaign.transform_links_for_tracking.parser.url.tag.url_suffix_only', false) === true) {
                    $pattern = '/^\[([A-Z0-9:_]+)_URL\]$/i';
                }

                if (preg_match($pattern, $url, $matches)) {
                    $_url = trim((string)$matches[0]);
                    $foundUrls[$_url] = $markup;
                    continue;
                }
            }

            if (empty($foundUrls)) {

                // since 1.3.5.9
                hooks()->doAction('campaign_content_after_transform_links_for_tracking', $collection = new CAttributeCollection([
                    'content'      => $content,
                    'campaign'     => $campaign,
                    'subscriber'   => $subscriber,
                    'list'         => $list,
                    'trackingUrls' => $trackingUrls,
                    'cacheKey'     => $cacheKey,
                ]));
                $content      = (string)$collection->itemAt('content');
                $trackingUrls = (array)$collection->itemAt('trackingUrls');

                // cache content
                cache()->set($cacheKey, $content);

                // release mutex
                mutex()->release($cacheKey);

                return $content;
            }

            $prefix = (string)$campaign->campaign_uid;
            $sort   = [];

            foreach ($foundUrls as $url => $markup) {
                $urlHash = sha1($prefix . $url);
                $track   = $trackingUrl . '/' . $urlHash;
                $length  = strlen($url);

                $trackingUrls[$cacheKey][] = [
                    'url'       => $url,
                    'hash'      => $urlHash,
                    'track'     => $track,
                    'length'    => $length,
                    'markup'    => $markup,
                ];

                $sort[] = $length;
            }

            unset($foundUrls);

            // make sure we order by the longest url to the shortest
            array_multisort($sort, SORT_DESC, SORT_NUMERIC, $trackingUrls[$cacheKey]);
        }

        if (!empty($trackingUrls[$cacheKey])) {
            $searchReplace = [];
            foreach ($trackingUrls[$cacheKey] as $urlData) {
                if (!$isPlainText) {
                    $searchReplace[$urlData['markup']] = 'href="' . $urlData['track'] . '"';
                } else {
                    $searchReplace[$urlData['markup']] = $urlData['track'];
                }
            }

            $content = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $content);

            // put back link hrefs
            $searchReplace = [];
            foreach ($trackingUrls[$cacheKey] as $urlData) {
                $searchReplace['link href="' . $urlData['track'] . '"'] = 'link href="' . $urlData['url'] . '"';
            }
            $content = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $content);

            unset($searchReplace);

            // save the url tags.
            $insertModels = [];
            foreach ($trackingUrls[$cacheKey] as $urlData) {
                $hash = $urlData['hash'];
                $key  = sha1($cacheKey . $hash);
                if (isset($trackingUrlsSaved[$key])) {
                    continue;
                }
                $trackingUrlsSaved[$key] = true;

                if (isset($insertModels[$hash])) {
                    continue;
                }

                $urlModel = CampaignUrl::model()->countByAttributes([
                    'campaign_id' => (int)$campaign->campaign_id,
                    'hash'        => $hash,
                ]);

                if (!empty($urlModel)) {
                    continue;
                }

                $insertModels[$hash] = [
                    'campaign_id' => $campaign->campaign_id,
                    'destination' => $urlData['url'],
                    'hash'        => $hash,
                    'date_added'  => MW_DATETIME_NOW,
                ];
            }

            if (!empty($insertModels)) {
                try {

                    // drop the keys
                    $insertModels = array_values($insertModels);

                    $schema    = db()->getSchema();
                    $tableName = CampaignUrl::model()->tableName();
                    $schema->getCommandBuilder()->createMultipleInsertCommand($tableName, $insertModels)->execute();
                } catch (Exception $e) {

                    // delete the cache, if any
                    cache()->delete($cacheKey);

                    // release mutex
                    mutex()->release($cacheKey);

                    throw new Exception('Unable to save the tracking urls!');
                }
            }
        }

        // since 1.3.5.9
        hooks()->doAction('campaign_content_after_transform_links_for_tracking', $collection = new CAttributeCollection([
            'content'      => $content,
            'campaign'     => $campaign,
            'subscriber'   => $subscriber,
            'list'         => $list,
            'trackingUrls' => $trackingUrls,
            'cacheKey'     => $cacheKey,
        ]));
        $content      = (string)$collection->itemAt('content');
        $trackingUrls = (array)$collection->itemAt('trackingUrls');

        // cache content
        cache()->set($cacheKey, $content);

        // release mutex
        mutex()->release($cacheKey);

        // return transformed
        return $content;
    }

    /**
     * @since 2.1.4
     * Make sure to keep this up to date with all the *_LINK tags
     *
     * @param string $content
     * @return string
     */
    public static function contentHandleLinkTagsMapping(string $content): string
    {
        $linkTagsMapping = [
            '[SUBSCRIBE_LINK]'                  => CHtml::link(t('campaigns', 'Subscribe'), '[SUBSCRIBE_URL]'),
            '[UNSUBSCRIBE_LINK]'                => CHtml::link(t('campaigns', 'Unsubscribe'), '[UNSUBSCRIBE_URL]'),
            '[DIRECT_UNSUBSCRIBE_LINK]'         => CHtml::link(t('campaigns', 'Unsubscribe'), '[DIRECT_UNSUBSCRIBE_URL]'),
            '[UNSUBSCRIBE_FROM_CUSTOMER_LINK]'  => CHtml::link(t('campaigns', 'Unsubscribe from this customer'), '[UNSUBSCRIBE_FROM_CUSTOMER_URL]'),
        ];
        return (string)str_replace(array_keys($linkTagsMapping), array_values($linkTagsMapping), $content);
    }

    /**
     * @param string $content
     * @param Campaign $campaign
     *
     * @return array
     */
    public static function embedContentImages(string $content, Campaign $campaign): array
    {
        if (empty($content)) {
            return [$content, []];
        }

        static $parsed = [];
        $key = sha1($campaign->campaign_uid . $content);

        if (isset($parsed[$key]) || array_key_exists($key, $parsed)) {
            return $parsed[$key];
        }

        $embedImages = [];
        $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets');
        $extensions  = (array)app_param('files.images.extensions', []);

        libxml_use_internal_errors(true);

        try {
            $query = qp($content, 'body', [
                'ignore_parser_warnings'    => true,
                'convert_to_encoding'       => app()->charset,
                'convert_from_encoding'     => app()->charset,
                'use_parser'                => 'html',
            ]);

            $images = $query->top()->find('img');

            if ($images->length == 0) {
                throw new Exception('No images found!');
            }

            foreach ($images as $image) {
                $src = urldecode($image->attr('src'));
                $src = (string)str_replace(['../', './', '..\\', '.\\', '..'], '', trim((string)$src));

                if (empty($src)) {
                    continue;
                }

                $ext = pathinfo($src, PATHINFO_EXTENSION);
                if (empty($ext) || !in_array(strtolower((string)$ext), $extensions)) {
                    continue;
                }
                unset($ext);

                if (preg_match('/\/frontend\/assets(\/gallery\/([a-zA-Z0-9]{13,})\/.*)/', $src, $matches)) {
                    $src = $matches[1];
                } elseif (preg_match('/\/frontend\/assets(\/files\/(customer|user)\/([a-zA-Z0-9]{13,})\/.*)/', $src, $matches)) {
                    $src = $matches[1];
                }

                if (preg_match('/^https?/i', $src)) {
                    continue;
                }

                $fullFilePath = $storagePath . '/' . $src;
                if (!is_file($fullFilePath)) {
                    continue;
                }

                $imageInfo = ImageHelper::getImageSize($fullFilePath);
                if (!is_array($imageInfo) || empty($imageInfo[0]) || empty($imageInfo[1]) || empty($imageInfo['mime'])) {
                    continue;
                }

                $cid = sha1($fullFilePath);
                $embedImages[] = [
                    'name'  => basename($fullFilePath),
                    'path'  => $fullFilePath,
                    'cid'   => $cid,
                    'mime'  => $imageInfo['mime'],
                ];

                $image->attr('src', 'cid:' . $cid);
                unset($fullFilePath, $cid, $imageInfo);
            }

            $content = (string)$query->top()->html();
            unset($query, $images);
        } catch (Exception $e) {
        }

        libxml_use_internal_errors(false);
        return $parsed[$key] = [$content, $embedImages];
    }

    /**
     * @param string $content
     *
     * @return string
     */
    public static function htmlToText(string $content): string
    {
        static $html2text;

        if ($html2text === null) {
            $html2text = new Html2Text\Html2Text();

            if (!is_cli()) {
                /** @var OptionUrl $optionUrl */
                $optionUrl = container()->get(OptionUrl::class);

                $html2text->setBaseUrl($optionUrl->getCurrentAppUrl());
            }
        }

        $html2text->setHtml($content);

        $text = $html2text->getText();

        $lines = explode("\n", $text);
        $lines = array_filter(array_map('trim', $lines));

        return implode("\n", $lines);
    }

    /**
     * @param string $content
     * @return array
     */
    public static function extractTemplateUrls(string $content): array
    {
        if (empty($content)) {
            return [];
        }

        static $urls = [];
        $hash = sha1($content);

        if (array_key_exists($hash, $urls)) {
            return $urls[$hash];
        }

        $urls[$hash] = [];

        // Previous pattern
        // href(\s+)?=(\s+)?(\042|\047)(\s+)?(.*?)(\s+)?(\042|\047)
        // since 2.1.11 - We consider only the <a> tags
        // (\042|\047) are octal quotes.
        $pattern = '/<a((?!href).*)?(href(\s+)?=(\s+)?(\042|\047)(\s+)?(.*?)(\s+)?(\042|\047))/i';
        if (!preg_match_all($pattern, $content, $matches)) {
            return $urls[$hash];
        }

        if (empty($matches[7])) {
            return $urls[$hash];
        }

        $urls[$hash] = array_unique(array_map(['CHtml', 'decode'], array_map('trim', $matches[7])));

        // remove tag urls
        foreach ($urls[$hash] as $index => $url) {
            if (empty($url) || (strpos($url, '[') !== 0 && !FilterVarHelper::url($url))) {
                unset($urls[$hash][$index]);
            }
        }

        sort($urls[$hash]);

        return $urls[$hash];
    }

    /**
     * @since 2.0.30
     *
     * @param string $content
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     *
     * @return bool
     */
    public static function contentHasUntransformedLinksForTracking(string $content, Campaign $campaign, ListSubscriber $subscriber): bool
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        /** @var string $baseUrl */
        $baseUrl = $optionUrl->getFrontendUrl();

        $callback = function (string $url) use ($campaign, $subscriber, $baseUrl): bool {
            if (!FilterVarHelper::url($url)) {
                return false;
            }

            $patternUrl = sprintf(
                '%s/%s/track-url/%s/',
                $baseUrl . 'campaigns',
                $campaign->campaign_uid,
                $subscriber->subscriber_uid
            );
            $pattern = '/^(' . preg_quote($patternUrl, '/') . ')([a-f0-9]{40})/i';

            return !preg_match($pattern, $url);
        };

        return count(array_filter(self::extractTemplateUrls($content), $callback)) > 0;
    }

    /**
     * @param mixed $tag
     * @param Campaign $campaign
     * @param string $content
     *
     * @return bool
     */
    public static function getIsTagUsedInCampaign($tag, Campaign $campaign, string $content = ''): bool
    {
        if (!is_array($tag)) {
            $tag = [$tag];
        }

        $tag = array_filter(array_unique($tag));
        foreach ($tag as $t) {
            $t = (string)str_replace(['[', ']'], '', $t);

            if (empty($t)) {
                continue;
            }

            if (
                (!empty($content) && strpos($content, $t) !== false) ||
                ($campaign->getCurrentSubject() && strpos($campaign->getCurrentSubject(), $t) !== false) ||
                (!empty($campaign->to_name) && strpos($campaign->to_name, $t) !== false) ||
                (!empty($campaign->from_name) && strpos($campaign->from_name, $t) !== false) ||
                (!empty($campaign->from_email) && strpos($campaign->from_email, $t) !== false)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $content
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     *
     * @return array
     * @throws CException
     */
    public static function getSubscriberFieldsSearchReplace(string $content, Campaign $campaign, ListSubscriber $subscriber): array
    {
        // since 1.3.6.2
        if (
            (defined('MW_PERF_LVL') && MW_PERF_LVL) &&
            defined('MW_PERF_LVL_ENABLE_SUBSCRIBER_FIELD_CACHE') &&
            MW_PERF_LVL & MW_PERF_LVL_ENABLE_SUBSCRIBER_FIELD_CACHE
        ) {
            return $subscriber->getAllCustomFieldsWithValues();
        }

        $searchReplace = [];
        $list = $campaign->list;
        foreach (ListField::getAllByListId((int)$list->list_id) as $field) {
            $tag = $field['tag'];
            if (empty($tag) || !self::getIsTagUsedInCampaign($tag, $campaign, $content)) {
                continue;
            }
            $tag = '[' . $tag . ']';

            $values = db()->createCommand()
                ->select('value')
                ->from('{{list_field_value}}')
                ->where('subscriber_id = :sid AND field_id = :fid', [
                    ':sid' => (int)$subscriber->subscriber_id,
                    ':fid' => (int)$field['field_id'],
                ])
                ->queryAll();

            $value = [];
            foreach ($values as $val) {
                $value[] = $val['value'];
            }
            $searchReplace[$tag] = implode(', ', $value);
        }

        return $searchReplace;
    }

    /**
     * @param string $content
     * @param Campaign $campaign
     * @param ListSubscriber|null $subscriber
     * @param DeliveryServer|null $server
     *
     * @return array
     * @throws CException
     */
    public static function getCommonTagsSearchReplace(string $content, Campaign $campaign, ?ListSubscriber $subscriber = null, ?DeliveryServer $server = null): array
    {
        $list            = $campaign->list;
        $searchReplace   = [];
        $ccSearchReplace = [];
        $ceSearchReplace = [];

        // since 1.3.5.9
        static $customerCampaignTags = [];
        if (!empty($campaign->customer_id) && strpos($content, CustomerCampaignTag::getTagPrefix()) !== false) {
            if (!isset($customerCampaignTags[$campaign->customer_id])) {
                $customerCampaignTags[$campaign->customer_id] = [];
                $criteria = new CDbCriteria();
                $criteria->select = 'tag, content, random';
                $criteria->compare('customer_id', (int)$campaign->customer_id);
                $criteria->limit = 100;
                $models = CustomerCampaignTag::model()->findAll($criteria);
                foreach ($models as $model) {
                    $customerCampaignTags[$campaign->customer_id][] = $model->getAttributes(['tag', 'content', 'random']);
                }
                unset($models);
            }

            foreach ($customerCampaignTags[$campaign->customer_id] as $ccTag) {
                $ccTagName  = '[' . CustomerCampaignTag::getTagPrefix() . $ccTag['tag'] . ']';
                $tagContent = StringHelper::decodeSurroundingTags($ccTag['content']);
                if ($ccTag['random'] == CustomerCampaignTag::TEXT_YES) {
                    $contentRandom = explode("\n", $tagContent);
                    $contentRandom = array_filter(array_unique(array_map('trim', $contentRandom)));
                    // remove any trailing tags, such as <br />
                    $contentRandom = array_map(function (string $str): string {
                        return (string)preg_replace('#(<([^>]+)>)+$#i', '', (string)$str);
                    }, $contentRandom);
                    $tagContent = $contentRandom[array_rand($contentRandom)];
                    unset($contentRandom);
                }

                // this still might contain unparsed campaign tags
                $ccSearchReplace[$ccTagName] = $tagContent;

                if (strpos($tagContent, '[') !== false && strpos($tagContent, ']') !== false) {
                    // cheap trick to add to the content so that the tags are found later...
                    $content .= $tagContent;
                }
            }
        }
        //

        // since 1.5.3
        static $extraCampaignTags = [];
        if (strpos($content, CampaignExtraTag::getTagPrefix()) !== false) {
            if (!isset($extraCampaignTags[$campaign->campaign_id])) {
                $extraCampaignTags[$campaign->campaign_id] = [];
                $criteria = new CDbCriteria();
                $criteria->select = 'tag, content';
                $criteria->compare('campaign_id', (int)$campaign->campaign_id);
                $criteria->limit = 100;
                $models = CampaignExtraTag::model()->findAll($criteria);
                foreach ($models as $model) {
                    $extraCampaignTags[$campaign->campaign_id][] = $model->getAttributes(['tag', 'content']);
                }
                unset($models);
            }

            foreach ($extraCampaignTags[$campaign->campaign_id] as $ceTag) {
                $ceTagName  = '[' . CampaignExtraTag::getTagPrefix() . $ceTag['tag'] . ']';
                $tagContent = StringHelper::decodeSurroundingTags($ceTag['content']);

                // this still might contain unparsed campaign tags
                $ceSearchReplace[$ceTagName] = $tagContent;

                if (strpos($tagContent, '[') !== false && strpos($tagContent, ']') !== false) {
                    // cheap trick to add to the content so that the tags are found later...
                    $content .= $tagContent;
                }
            }
        }
        //

        // 1.3.9.5
        $randomContentBlock = [];
        if (strpos($content, '[RANDOM_CONTENT') !== false && preg_match_all('/\[RANDOM_CONTENT:([^\]]+)\]/', $content, $matches)) {
            foreach ($matches[0] as $index => $tag) {
                if (!isset($matches[1]) || !isset($matches[1][$index])) {
                    continue;
                }
                $tagValue = explode('|', $matches[1][$index]);
                $randKey  = array_rand($tagValue);
                $tagValue = trim((string)$tagValue[$randKey]);

                if (stripos($tagValue, 'BLOCK') !== false && strpos($tagValue, ':') !== false) {
                    $blockName = explode(':', $tagValue);
                    $blockName = end($blockName);
                    $blockName = trim((string)$blockName);

                    $rndModel = CampaignRandomContent::model()->findByAttributes([
                        'campaign_id' => $campaign->campaign_id,
                        'name'        => $blockName,
                    ]);

                    if (!empty($rndModel)) {
                        $tagValue = $rndModel->content;

                        // since 1.9.5
                        if (CampaignHelper::contentHasXmlFeed($tagValue)) {
                            $tagValue = CampaignXmlFeedParser::parseContent($tagValue, $campaign, $subscriber, true, '', $server);
                        }
                        if (CampaignHelper::contentHasJsonFeed($tagValue)) {
                            $tagValue = CampaignJsonFeedParser::parseContent($tagValue, $campaign, $subscriber, true, '', $server);
                        }
                        if (CampaignHelper::hasRemoteContentTag($tagValue)) {
                            $tagValue = CampaignHelper::fetchContentForRemoteContentTag($tagValue, $campaign, $subscriber);
                        }
                        //

                        // since 1.9.22
                        if (!empty($campaign->customer_id) && strpos($tagValue, CustomerCampaignTag::getTagPrefix()) !== false) {
                            if (!isset($customerCampaignTags[$campaign->customer_id])) {
                                $customerCampaignTags[$campaign->customer_id] = [];
                                $criteria = new CDbCriteria();
                                $criteria->select = 'tag, content, random';
                                $criteria->compare('customer_id', (int)$campaign->customer_id);
                                $criteria->limit = 100;
                                $models = CustomerCampaignTag::model()->findAll($criteria);
                                foreach ($models as $model) {
                                    $customerCampaignTags[$campaign->customer_id][] = $model->getAttributes(['tag', 'content', 'random']);
                                }
                                unset($models);
                            }

                            foreach ($customerCampaignTags[$campaign->customer_id] as $ccTag) {
                                $ccTagName  = '[' . CustomerCampaignTag::getTagPrefix() . $ccTag['tag'] . ']';
                                $tagContent = StringHelper::decodeSurroundingTags($ccTag['content']);
                                if ($ccTag['random'] == CustomerCampaignTag::TEXT_YES) {
                                    $contentRandom = explode("\n", $tagContent);
                                    $contentRandom = array_filter(array_unique(array_map('trim', $contentRandom)));
                                    // remove any trailing tags, such as <br />
                                    $contentRandom = array_map(function (string $str): string {
                                        return (string)preg_replace('#(<([^>]+)>)+$#i', '', (string)$str);
                                    }, $contentRandom);
                                    $tagContent = $contentRandom[array_rand($contentRandom)];
                                    unset($contentRandom);
                                }

                                // this still might contain unparsed campaign tags
                                $ccSearchReplace[$ccTagName] = $tagContent;
                            }
                        }
                        //

                        // since 1.9.24 - track only if enabled
                        if (!empty($campaign->option) && $campaign->option->url_tracking == CampaignOption::TEXT_YES) {
                            // Previous pattern
                            // href(\s+)?=(\s+)?(\042|\047)(\s+)?(.*?)(\s+)?(\042|\047)
                            // since 2.1.11 - We get only the <a> tags
                            // (\042|\047) are octal quotes.
                            $pattern = '/<a((?!href).*)?(href(\s+)?=(\s+)?(\042|\047)(\s+)?(.*?)(\s+)?(\042|\047))/i';
                            if (!empty($subscriber) && preg_match_all($pattern, $tagValue, $__matches)) {
                                $tagValue = self::transformLinksForTracking($tagValue, $campaign, $subscriber, true);
                            }
                        }
                    }
                }
                //

                $randomContentBlock[$tag] = $tagValue;
                if (strpos($tagValue, '[') !== false && strpos($tagValue, ']') !== false) {
                    // cheap trick to add to the content so that the tags are found later...
                    $content .= $tagValue;
                }
            }
        }

        // subscriber
        if (!empty($subscriber) && !empty($subscriber->subscriber_id)) {
            $searchReplace = self::getSubscriberFieldsSearchReplace($content, $campaign, $subscriber);
        }

        // list
        if (self::getIsTagUsedInCampaign('LIST_', $campaign, $content)) {
            $searchReplace['[LIST_ID]']             = (int)$list->list_id;
            $searchReplace['[LIST_UID]']            = (string)$list->list_uid;
            $searchReplace['[LIST_NAME]']           = $list->display_name;
            $searchReplace['[LIST_DISPLAY_NAME]']   = $list->display_name;
            $searchReplace['[LIST_DESCRIPTION]']    = $list->description;
            $searchReplace['[LIST_FROM_NAME]']      = $list->default->from_name;
            $searchReplace['[LIST_FROM_EMAIL]']     = $list->default->from_email;
            $searchReplace['[LIST_SUBJECT]']        = $list->default->subject;
        }

        // date
        if (self::getIsTagUsedInCampaign('CURRENT_', $campaign, $content)) {
            $searchReplace['[CURRENT_YEAR]']            = date('Y');
            $searchReplace['[CURRENT_MONTH]']           = date('m');
            $searchReplace['[CURRENT_DAY]']             = date('d');
            $searchReplace['[CURRENT_DATE]']            = date('m/d/Y');
            $searchReplace['[CURRENT_MONTH_FULL_NAME]'] = t('campaigns', date('F'));
        }

        // signs
        if (self::getIsTagUsedInCampaign('SIGN_', $campaign, $content)) {
            $searchReplace['[SIGN_LT]']   = '<';
            $searchReplace['[SIGN_LTE]']  = '<=';
            $searchReplace['[SIGN_GT]']   = '>';
            $searchReplace['[SIGN_GTE]']  = '>=';
        }

        // company
        if (self::getIsTagUsedInCampaign('COMPANY_', $campaign, $content)) {
            $company = !empty($list->company) ? $list->company : null;
            $searchReplace['[COMPANY_FULL_ADDRESS]'] = $company ? nl2br($company->getFormattedAddress()) : '';
            $searchReplace['[COMPANY_NAME]']         = $company ? $company->name : '';
            $searchReplace['[COMPANY_WEBSITE]']      = $company ? $company->website : '';
            $searchReplace['[COMPANY_ADDRESS_1]']    = $company ? $company->address_1 : '';
            $searchReplace['[COMPANY_ADDRESS_2]']    = $company ? $company->address_2 : '';
            $searchReplace['[COMPANY_CITY]']         = $company ? $company->city : '';
            $searchReplace['[COMPANY_ZIP]']          = $company ? $company->zip_code : '';
            $searchReplace['[COMPANY_PHONE]']        = $company ? $company->phone : '';

            if (self::getIsTagUsedInCampaign('COMPANY_ZONE', $campaign, $content)) {
                $searchReplace['[COMPANY_ZONE]']        = $company && !empty($company->zone) ? $company->zone->name : '';
                $searchReplace['[COMPANY_ZONE_CODE]']   = $company && !empty($company->zone) ? $company->zone->code : '';

                // 1.9.2
                if (empty($searchReplace['[COMPANY_ZONE]']) && !empty($company) && !empty($company->zone_name)) {
                    $searchReplace['[COMPANY_ZONE]'] = $company->zone_name;
                }
            }

            if (self::getIsTagUsedInCampaign('COMPANY_COUNTRY', $campaign, $content)) {
                $searchReplace['[COMPANY_COUNTRY]']         = $company && !empty($company->country) ? $company->country->name : '';
                $searchReplace['[COMPANY_COUNTRY_CODE]']    = $company && !empty($company->country) ? $company->country->code : '';
            }
        }

        // campaign
        if (self::getIsTagUsedInCampaign('CAMPAIGN_', $campaign, $content)) {
            $searchReplace['[CAMPAIGN_NAME]']             = $campaign->name;
            $searchReplace['[CAMPAIGN_TYPE]']             = $campaign->type;
            $searchReplace['[CAMPAIGN_FROM_NAME]']        = $campaign->from_name;
            $searchReplace['[CAMPAIGN_FROM_EMAIL]']       = $campaign->from_email;
            $searchReplace['[CAMPAIGN_REPLY_TO]']         = $campaign->reply_to;
            $searchReplace['[CAMPAIGN_ID]']               = (int)$campaign->campaign_id;
            $searchReplace['[CAMPAIGN_UID]']              = (string)$campaign->campaign_uid;
            $searchReplace['[CAMPAIGN_REPORT_ABUSE_URL]'] = '';
            $searchReplace['[CAMPAIGN_SEND_AT]']          = $campaign->send_at;
            $searchReplace['[CAMPAIGN_STARTED_AT]']       = !is_string($campaign->started_at) ? date('Y-m-d H:i:s') : $campaign->started_at;
            $searchReplace['[CAMPAIGN_DATETIME_ADDED]']   = $campaign->date_added;
            $searchReplace['[CAMPAIGN_DATE_ADDED]']       = date('Y-m-d', (int)strtotime((string)$campaign->date_added));
            $searchReplace['[CAMPAIGN_SEGMENT_NAME]']     = !empty($campaign->segment_id) ? $campaign->segment->name : '';
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $campaignUrl                = $optionUrl->getFrontendUrl('campaigns/' . $campaign->campaign_uid);
        $unsubscribeUrl             = $optionUrl->getFrontendUrl('lists/' . $list->list_uid . '/unsubscribe');
        $unsubscribeFromCustomerUrl = $optionUrl->getFrontendUrl('lists/unsubscribe-from-customer/' . $campaign->customer->customer_uid);
        $subscribeUrl               = $optionUrl->getFrontendUrl('lists/' . $list->list_uid . '/subscribe');
        $forwardFriendUrl           = $optionUrl->getFrontendUrl('campaigns/' . $campaign->campaign_uid . '/forward-friend');
        $updateProfileUrl           = null;
        $webVersionUrl              = null;

        if (!empty($subscriber) && !empty($subscriber->subscriber_id)) {
            $unsubscribeUrl             .= '/' . $subscriber->subscriber_uid . '/' . $campaign->campaign_uid;
            $unsubscribeFromCustomerUrl .= '/' . $subscriber->subscriber_uid . '/' . $campaign->campaign_uid;

            $forwardFriendUrl           .= '/' . $subscriber->subscriber_uid;
            $updateProfileUrl = $optionUrl->getFrontendUrl('lists/' . $list->list_uid . '/update-profile/' . $subscriber->subscriber_uid);
            $webVersionUrl    = $optionUrl->getFrontendUrl('campaigns/' . $campaign->campaign_uid . '/web-version/' . $subscriber->subscriber_uid);

            if (self::getIsTagUsedInCampaign('SUBSCRIBER_', $campaign, $content)) {
                $searchReplace['[SUBSCRIBER_ID]']                   = (int)$subscriber->subscriber_id;
                $searchReplace['[SUBSCRIBER_UID]']                  = (string)$subscriber->subscriber_uid;
                $searchReplace['[SUBSCRIBER_IP]']                   = $subscriber->ip_address;
                $searchReplace['[SUBSCRIBER_DATE_ADDED]']           = $subscriber->date_added;
                $searchReplace['[SUBSCRIBER_DATE_ADDED_LOCALIZED]'] = $subscriber->dateTimeFormatter->getDateAdded();

                // 1.5.0
                $searchReplace['[SUBSCRIBER_EMAIL]']        = '';
                $searchReplace['[SUBSCRIBER_EMAIL_NAME]']   = '';
                $searchReplace['[SUBSCRIBER_EMAIL_DOMAIN]'] = '';
                $searchReplace['[EMAIL_NAME]']              = '';
                $searchReplace['[EMAIL_DOMAIN]']            = '';
                if (!empty($subscriber->email)) {
                    [$_emailName, $_emailDomain] = explode('@', $subscriber->email);
                    $searchReplace['[SUBSCRIBER_EMAIL]']        = $subscriber->email;
                    $searchReplace['[SUBSCRIBER_EMAIL_NAME]']   = $_emailName;
                    $searchReplace['[SUBSCRIBER_EMAIL_DOMAIN]'] = $_emailDomain;
                    $searchReplace['[EMAIL_NAME]']              = $_emailName;
                    $searchReplace['[EMAIL_DOMAIN]']            = $_emailDomain;
                }
                //
            }

            if (self::getIsTagUsedInCampaign('CAMPAIGN_REPORT_ABUSE_URL', $campaign, $content)) {
                $searchReplace['[CAMPAIGN_REPORT_ABUSE_URL]'] = $campaignUrl . '/report-abuse/' . $list->list_uid . '/' . $subscriber->subscriber_uid;
            }

            // 1.3.8.8
            if (self::getIsTagUsedInCampaign('SUBSCRIBER_OPTIN_', $campaign, $content)) {
                $searchReplace['[SUBSCRIBER_OPTIN_IP]']   = !empty($subscriber->optinHistory) ? $subscriber->optinHistory->optin_ip : '';
                $searchReplace['[SUBSCRIBER_OPTIN_DATE]'] = !empty($subscriber->optinHistory) ? $subscriber->optinHistory->optin_date : '';
            }

            // 1.3.8.8
            if (self::getIsTagUsedInCampaign('SUBSCRIBER_CONFIRM_', $campaign, $content)) {
                $searchReplace['[SUBSCRIBER_CONFIRM_IP]']   = !empty($subscriber->optinHistory) ? $subscriber->optinHistory->confirm_ip : '';
                $searchReplace['[SUBSCRIBER_CONFIRM_DATE]'] = !empty($subscriber->optinHistory) ? $subscriber->optinHistory->confirm_date : '';
            }

            // 1.3.9.3
            if (self::getIsTagUsedInCampaign('SUBSCRIBER_LAST_SENT_DATE', $campaign, $content)) {
                $criteria = new CDbCriteria();
                $criteria->select = 'date_added';
                $criteria->compare('subscriber_id', (int)$subscriber->subscriber_id);
                $criteria->order = 'date_added DESC';
                $criteria->limit = 1;
                $model = CampaignDeliveryLog::model()->find($criteria);
                $searchReplace['[SUBSCRIBER_LAST_SENT_DATE]']           = $model ? $model->date_added : '';
                $searchReplace['[SUBSCRIBER_LAST_SENT_DATE_LOCALIZED]'] = $model ? $model->dateAdded : '';
            }
        }

        if (self::getIsTagUsedInCampaign('CURRENT_DOMAIN', $campaign, $content)) {
            $searchReplace['[CURRENT_DOMAIN]']     = parse_url($optionUrl->getFrontendUrl(), PHP_URL_HOST);
            $searchReplace['[CURRENT_DOMAIN_URL]'] = $optionUrl->getFrontendUrl();
        }

        // server - since 1.3.6.6
        if (self::getIsTagUsedInCampaign('DS_', $campaign, $content)) {
            $searchReplace['[DS_NAME]']          = !empty($server) && !empty($server->name) ? $server->name : '';
            $searchReplace['[DS_HOST]']          = !empty($server) && !empty($server->hostname) ? $server->hostname : '';
            $searchReplace['[DS_ID]']            = !empty($server) && !empty($server->server_id) ? $server->server_id : '';
            $searchReplace['[DS_TYPE]']          = !empty($server) && !empty($server->type) ? $server->type : '';
            $searchReplace['[DS_FROM_NAME]']     = !empty($server) && !empty($server->from_name) ? $server->from_name : '';
            $searchReplace['[DS_FROM_EMAIL]']    = !empty($server) && !empty($server->from_email) ? $server->from_email : '';
            $searchReplace['[DS_REPLYTO_EMAIL]'] = !empty($server) && !empty($server->reply_to_email) ? $server->reply_to_email : '';
        }

        // other urls
        if (self::getIsTagUsedInCampaign('SUBSCRIBE_', $campaign, $content)) {
            $searchReplace['[SUBSCRIBE_URL]']                   = $subscribeUrl;
            $searchReplace['[SUBSCRIBE_LINK]']                  = CHtml::link(t('campaigns', 'Subscribe'), $subscribeUrl);
            $searchReplace['[UNSUBSCRIBE_URL]']                 = $unsubscribeUrl;
            $searchReplace['[UNSUBSCRIBE_LINK]']                = CHtml::link(t('campaigns', 'Unsubscribe'), $unsubscribeUrl);
            $searchReplace['[DIRECT_UNSUBSCRIBE_URL]']          = $unsubscribeUrl . (!empty($subscriber) ? '/unsubscribe-direct' : '');
            $searchReplace['[DIRECT_UNSUBSCRIBE_LINK]']         = CHtml::link(t('campaigns', 'Unsubscribe'), $unsubscribeUrl . (!empty($subscriber) ? '/unsubscribe-direct' : ''));
            $searchReplace['[UNSUBSCRIBE_FROM_CUSTOMER_URL]']   = $unsubscribeFromCustomerUrl;
            $searchReplace['[UNSUBSCRIBE_FROM_CUSTOMER_LINK]']  = CHtml::link(t('campaigns', 'Unsubscribe from this customer'), $unsubscribeFromCustomerUrl);
        }

        // vcards - 1.7.6
        if (self::getIsTagUsedInCampaign('_VCARD_URL', $campaign, $content)) {
            $searchReplace['[CAMPAIGN_VCARD_URL]'] = $optionUrl->getFrontendUrl('campaigns/' . $campaign->campaign_uid . '/vcard');
            $searchReplace['[LIST_VCARD_URL]']     = $optionUrl->getFrontendUrl('lists/' . $list->list_uid . '/vcard');
        }

        // 1.3.8.0
        if (!empty($server)) {
            if ($server->type == 'elasticemail-web-api' || preg_match('/smtp(\d+)?\.elasticemail\.com/i', $server->hostname)) {
                if (self::getIsTagUsedInCampaign('UNSUBSCRIBE_', $campaign, $content)) {
                    $searchReplace['[UNSUBSCRIBE_URL]']                 = sprintf('{unsubscribe:%s}', $unsubscribeUrl);
                    $searchReplace['[UNSUBSCRIBE_LINK]']                = CHtml::link(t('campaigns', 'Unsubscribe'), sprintf('{unsubscribe:%s}', $unsubscribeUrl));
                    $searchReplace['[DIRECT_UNSUBSCRIBE_URL]']          = sprintf('{unsubscribe:%s}', $unsubscribeUrl . (!empty($subscriber) ? '/unsubscribe-direct' : ''));
                    $searchReplace['[DIRECT_UNSUBSCRIBE_LINK]']         = CHtml::link(t('campaigns', 'Unsubscribe'), sprintf('{unsubscribe:%s}', $unsubscribeUrl . (!empty($subscriber) ? '/unsubscribe-direct' : '')));
                    $searchReplace['[UNSUBSCRIBE_FROM_CUSTOMER_URL]']   = sprintf('{unsubscribe:%s}', $unsubscribeFromCustomerUrl);
                    $searchReplace['[UNSUBSCRIBE_FROM_CUSTOMER_LINK]']  = CHtml::link(t('campaigns', 'Unsubscribe from this customer'), sprintf('{unsubscribe:%s}', $unsubscribeFromCustomerUrl));
                }
            }
        }

        if (self::getIsTagUsedInCampaign('UPDATE_PROFILE_URL', $campaign, $content)) {
            $searchReplace['[UPDATE_PROFILE_URL]'] = $updateProfileUrl;
        }

        if (self::getIsTagUsedInCampaign('WEB_VERSION_URL', $campaign, $content)) {
            $searchReplace['[WEB_VERSION_URL]'] = $webVersionUrl;
        }

        if (self::getIsTagUsedInCampaign('CAMPAIGN_URL', $campaign, $content)) {
            $searchReplace['[CAMPAIGN_URL]'] = $campaignUrl;
        }

        if (self::getIsTagUsedInCampaign('FORWARD_FRIEND_URL', $campaign, $content)) {
            $searchReplace['[FORWARD_FRIEND_URL]'] = $forwardFriendUrl;
        }

        // 1.8.1
        if (!empty($subscriber) && self::getIsTagUsedInCampaign('SURVEY', $campaign, $content)) {
            if (preg_match_all('/\[SURVEY:([a-z0-9]{13}):VIEW_URL\]/i', $content, $surveyMatches)) {
                if (isset($surveyMatches[0], $surveyMatches[1])) {
                    foreach ($surveyMatches[0] as $index => $surveyMatch) {
                        $surveyUid = $surveyMatches[1][$index];
                        if (!empty($subscriber->subscriber_uid) && !empty($campaign->campaign_uid)) {
                            $url = sprintf(
                                '%ssurveys/%s/%s/%s',
                                $optionUrl->getFrontendUrl(),
                                $surveyUid,
                                $subscriber->subscriber_uid,
                                $campaign->campaign_uid
                            );
                        } else {
                            $url = sprintf(
                                '%ssurveys/%s',
                                $optionUrl->getFrontendUrl(),
                                $surveyUid
                            );
                        }
                        $searchReplace[$surveyMatch] = $url;
                    }
                }
            }
        }
        //

        $to  = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $campaign->to_name);
        $to  = self::getTagFilter()->apply($to, $searchReplace);
        if (empty($to) && !empty($subscriber) && !empty($subscriber->subscriber_id)) {
            $to = $subscriber->email;
        }
        if (empty($to)) {
            $to = 'unknown';
        }

        // since 1.9.12
        $to = self::applyRandomContentTag($to);

        $searchReplace['[CAMPAIGN_TO_NAME]'] = $to;

        $subject = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $campaign->getCurrentSubject());
        $subject = self::getTagFilter()->apply($subject, $searchReplace);
        if (empty($subject)) {
            $subject = 'unknown';
        }

        // since 1.3.5, rotate content randomly
        $subject = self::applyRandomContentTag($subject);
        //

        $searchReplace['[CAMPAIGN_SUBJECT]'] = $subject;

        // 1.3.9.3
        foreach ($ccSearchReplace as $tag => $tagContent) {
            if (strpos($tagContent, '[') !== false && strpos($tagContent, ']') !== false) {
                $tagContent = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $tagContent);
            }
            $searchReplace[$tag] = $tagContent;
        }
        unset($ccSearchReplace);
        //

        // 1.5.3
        foreach ($ceSearchReplace as $tag => $tagContent) {
            if (strpos($tagContent, '[') !== false && strpos($tagContent, ']') !== false) {
                $tagContent = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $tagContent);
            }
            $searchReplace[$tag] = $tagContent;
        }
        unset($ceSearchReplace);
        //

        // 1.3.9.5
        foreach ($randomContentBlock as $tag => $tagContent) {
            if (strpos($tagContent, '[') !== false && strpos($tagContent, ']') !== false) {
                $tagContent = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $tagContent);
            }

            $searchReplace[$tag] = $tagContent;

            // 1.8.4
            if (strpos((string)$tag, '[') !== false && strpos((string)$tag, ']') !== false) {
                $tag = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), (string)$tag);
            }

            $searchReplace[(string)$tag] = $tagContent;
            //
        }
        unset($randomContentBlock);
        //

        $searchReplace = (array)hooks()->applyFilters('campaigns_get_common_tags_search_replace', $searchReplace, $campaign, $subscriber, $server);

        return $searchReplace;
    }

    /**
     * @return EmailTemplateTagFilter
     */
    public static function getTagFilter(): EmailTemplateTagFilter
    {
        static $tagFilter;
        if ($tagFilter === null) {
            $tagFilter = new EmailTemplateTagFilter();
        }
        return $tagFilter;
    }

    /**
     * @param string $emailContent
     * @param string $emailHeader
     * @param Campaign $campaign
     *
     * @return string
     */
    public static function injectEmailHeader(string $emailContent, string $emailHeader, Campaign $campaign): string
    {
        return (string)preg_replace('/<body([^>]+)?>/i', '$0' . $emailHeader, $emailContent);
    }

    /**
     * @param string $emailContent
     * @param string $emailFooter
     * @param Campaign $campaign
     *
     * @return string
     */
    public static function injectEmailFooter(string $emailContent, string $emailFooter, Campaign $campaign): string
    {
        return str_ireplace('</body>', $emailFooter . "\n" . '</body>', $emailContent);
    }

    /**
     * @param string $emailContent
     * @param string $preheader
     * @param Campaign $campaign
     *
     * @return string
     */
    public static function injectPreheader(string $emailContent, string $preheader, Campaign $campaign): string
    {
        $hideCss      = 'display:none!important;mso-hide:all;';
        $style        = sprintf('<style type="text/css">span.preheader{%s}</style>', $hideCss);
        $emailContent = (string)str_ireplace('</head>', $style . '</head>', $emailContent);
        $preheader    = sprintf('<span class="preheader" style="%s">%s</span>', $hideCss, $preheader);
        $preheader    = (string)str_replace('$', '\$', $preheader);
        return (string)preg_replace('/<body([^>]+)?>/six', '$0' . $preheader, $emailContent);
    }

    /**
     * @param string $tag
     *
     * @return string
     */
    public static function parseDateTag(string $tag): string
    {
        $params = array_merge([
            'FORMAT' => 'Y-m-d',
        ], StringHelper::getTagParams($tag));
        $date = @date($params['FORMAT']);
        return $date ? $date : '';
    }

    /**
     * @param string $tag
     *
     * @return string
     */
    public static function parseDateTimeTag(string $tag): string
    {
        $params = array_merge([
            'FORMAT' => 'Y-m-d H:i:s',
        ], StringHelper::getTagParams($tag));
        $dateTime = @date($params['FORMAT']);
        return $dateTime ? $dateTime : '';
    }

    /**
     * @param string $content
     * @param string $pattern
     *
     * @return string
     */
    public static function injectGoogleUtmTagsIntoTemplate(string $content, string $pattern): string
    {
        $pattern = trim((string)$pattern, '?&/');
        $pattern = (string)str_replace(['&utm;', '&amp;', ';'], ['&utm', '&', ''], $pattern);

        $patternArray = [];
        parse_str($pattern, $patternArray);
        if (empty($patternArray)) {
            return $content;
        }

        libxml_use_internal_errors(true);

        $urlSearchReplace = [];

        try {
            $ioFilter = ioFilter();
            $content  = StringHelper::normalizeUrlsInContent($content);

            $query = qp($ioFilter->purify(html_decode(urldecode($content))), 'body', [
                'ignore_parser_warnings'    => true,
                'convert_to_encoding'       => app()->charset,
                'convert_from_encoding'     => app()->charset,
                'use_parser'                => 'html',
            ]);

            $anchors = $query->top()->find('a');

            if ($anchors->length == 0) {
                throw new Exception('No anchor found!');
            }

            foreach ($anchors as $anchor) {
                if (!($href = $anchor->attr('href'))) {
                    continue;
                }
                $ohref = $href;
                $title = trim((string)$anchor->attr('title'));

                //skip url tags
                if (preg_match('/^\[([A-Z0-9\:_]+)_URL\]$/i', $href)) {
                    continue;
                }

                if (!($parsedQueryString = parse_url($href, PHP_URL_QUERY))) {
                    $queryString = urldecode(http_build_query($patternArray));
                    if (!empty($title)) {
                        $queryString = (string)str_replace('[TITLE_ATTR]', $title, $queryString);
                    }
                    $glue = '?';
                    // in case this is just the domain name
                    // transforms: https://domain.com?url=pattern into https://domain.com/?url=pattern
                    if (!parse_url($href, PHP_URL_PATH)) {
                        $glue = '/?';
                    }
                    $urlSearchReplace[$ohref] = (string)$href . $glue . $queryString;
                    continue;
                }

                $parsedUrlQueryArray = [];
                parse_str($parsedQueryString, $parsedUrlQueryArray);
                if (empty($parsedUrlQueryArray)) {
                    continue;
                }

                $href = (string)str_replace($parsedQueryString, '[QS]', $href);
                $_patternArray = CMap::mergeArray($parsedUrlQueryArray, $patternArray);
                $queryString   = urldecode(http_build_query($_patternArray));
                if (!empty($title)) {
                    $queryString = (string)str_replace('[TITLE_ATTR]', $title, $queryString);
                }
                $urlSearchReplace[$ohref] = (string)str_replace('[QS]', $queryString, $href);
            }

            $sort = [];
            foreach ($urlSearchReplace as $k => $v) {
                $sort[] = strlen((string)$k);
            }
            array_multisort($urlSearchReplace, $sort, SORT_NUMERIC, SORT_DESC);

            foreach ($urlSearchReplace as $url => $replacement) {
                $decodedUrl = urldecode((string)$url);
                $searchFor  = [$url];
                if ($decodedUrl != $url) {
                    $searchFor[] = $decodedUrl;
                }
                foreach ($searchFor as $item) {
                    $pattern = sprintf('#href=(\042|\047)(%s)(\042|\047)#i', preg_quote((string)$item, '#'));
                    $content = (string)preg_replace($pattern, sprintf('href="%s"', $replacement), $content);
                }
            }

            unset($anchors, $query);
        } catch (Exception $e) {
        }

        libxml_use_internal_errors(false);

        return (string)$content;
    }

    /**
     * @return array
     */
    public static function getParsedFieldValueByListFieldValueTagInfo(): array
    {
        /** @var array $data */
        $data = hooks()->applyFilters('common_helper_parsed_field_value_by_list_field_value_tag_info', [
            '[INCREMENT_BY_X]'          => t('campaigns', 'Increment the value by X where X is an integer'),
            '[INCREMENT_ONCE_BY_X]'     => t('campaigns', 'Increment the value by X where X is an integer. Only once'),
            '[DECREMENT_BY_X]'          => t('campaigns', 'Decrement the value by X where X is an integer'),
            '[DECREMENT_ONCE_BY_X]'     => t('campaigns', 'Decrement the value by X where X is an integer. Only once'),
            '[MULTIPLY_BY_X]'           => t('campaigns', 'Multiply the value by X where X is an integer'),
            '[MULTIPLY_ONCE_BY_X]'      => t('campaigns', 'Multiply the value by X where X is an integer. Only once'),
            '[DATETIME]'                => t('campaigns', 'Set current date and time, in Y-m-d H:i:s format'),
            '[DATE]'                    => t('campaigns', 'Set current date, in Y-m-d format'),
            '[IP_ADDRESS]'              => t('campaigns', 'Set the current IP address'),
            '[GEO_COUNTRY]'             => t('campaigns', 'Set the current user country based on IP address'),
            '[GEO_STATE]'               => t('campaigns', 'Set the current user state/zone based on IP address'),
            '[GEO_CITY]'                => t('campaigns', 'Set the current user city based on IP address'),
            '[USER_AGENT]'              => t('campaigns', 'Set the current User Agent string'),
            '[UA_BROWSER]'              => t('campaigns', 'Set the current user browser based on the User Agent string'),
            '[UA_OS]'                   => t('campaigns', 'Set the current user operating system based on the User Agent string'),
            '[UA_ENGINE]'               => t('campaigns', 'Set the current user browser engine based on the User Agent string'),
            '[UA_DEVICE]'               => t('campaigns', 'Set the current user browser device based on the User Agent string'),
            '[CAMPAIGN_NAME]'           => t('campaigns', 'Set the current campaign name for which this action is taken'),
            '[CAMPAIGN_UID]'            => t('campaigns', 'Set the current campaign unique id for which this action is taken'),
        ]);

        return $data;
    }

    /**
     * @param CAttributeCollection $collection
     *
     * @return string
     * @throws MaxMind\Db\Reader\InvalidDatabaseException
     */
    public static function getParsedFieldValueByListFieldValue(CAttributeCollection $collection): string
    {
        /** @var string $fieldValue */
        $fieldValue = (string)$collection->itemAt('fieldValue');

        /** @var ListFieldValue $valueModel */
        $valueModel = $collection->itemAt('valueModel');

        $processed = false;
        if (preg_match('/\[INCREMENT_BY_(\d+)\]/', $fieldValue, $matches)) {
            $fieldValue = (int)$valueModel->value + (int)$matches[1];
            $processed  = true;
        } elseif (preg_match('/\[DECREMENT_BY_(\d+)\]/', $fieldValue, $matches)) {
            $fieldValue = (int)$valueModel->value - (int)$matches[1];
            $processed  = true;
        } elseif (preg_match('/\[MULTIPLY_BY_(\d+)\]/', $fieldValue, $matches)) {
            $fieldValue = (int)$valueModel->value * (int)$matches[1];
            $processed  = true;
        }

        // since 2.1.11
        /** @var Campaign $campaign */
        $campaign = $collection->itemAt('campaign');

        /** @var ListSubscriber $subscriber */
        $subscriber = $collection->itemAt('subscriber');

        /** @var string $event */
        $event = (string)$collection->itemAt('event');

        $incrementOnce = false;
        $decrementOnce = false;
        $multiplyOnce  = false;
        if (
            !$processed && !empty($event) &&
            (
                ($incrementOnce = preg_match('/\[INCREMENT_ONCE_BY_(\d+)\]/', (string)$fieldValue, $matches)) ||
                ($decrementOnce = preg_match('/\[DECREMENT_ONCE_BY_(\d+)\]/', (string)$fieldValue, $matches)) ||
                ($multiplyOnce  = preg_match('/\[MULTIPLY_ONCE_BY_(\d+)\]/', (string)$fieldValue, $matches))
            )
        ) {
            $count = -1;
            if (
                $event === 'campaign:subscriber:track:open' &&
                $collection->contains('trackOpen') &&
                ($collection->itemAt('trackOpen') instanceof CampaignTrackOpen)
            ) {
                /** @var CampaignTrackOpen $trackOpen */
                $trackOpen = $collection->itemAt('trackOpen');
                $count = (int)CampaignTrackOpen::model()->countByAttributes([
                    'campaign_id'   => $trackOpen->campaign_id,
                    'subscriber_id' => $trackOpen->subscriber_id,
                ]);
                // we're still in the first event
                if ($count === 1) {
                    $count--;
                }
            } elseif (
                $event === 'campaign:subscriber:track:click' &&
                $collection->contains('url') &&
                ($collection->itemAt('url') instanceof CampaignUrl)
            ) {
                /** @var CampaignUrl $url */
                $url = $collection->itemAt('url');
                $count = (int)CampaignTrackUrl::model()->countByAttributes([
                    'subscriber_id' => $subscriber->subscriber_id,
                    'url_id'        => $url->url_id,
                ]);
                // we're still in the first event
                if ($count === 1) {
                    $count--;
                }
            } elseif ($event === 'campaign:subscriber:sent') {
                $count = 0;
            }

            // set existing value, otherwise we end up with the tag itself
            // because every iteration would overwrite the actual value with the tag itself, and we do not want this
            $fieldValue = (int)$valueModel->value;

            if ($count === 0) {
                if ($incrementOnce) {
                    $fieldValue = (int)$valueModel->value + (int)$matches[1];
                } elseif ($decrementOnce) {
                    $fieldValue = (int)$valueModel->value - (int)$matches[1];
                } elseif ($multiplyOnce) {
                    $fieldValue = (int)$valueModel->value * (int)$matches[1];
                }
            }
        }
        // end 2.1.11 changes

        $ipAddress = '';
        $userAgent = '';
        if (!is_cli()) {
            $ipAddress = (string)request()->getUserHostAddress();
            $userAgent = (string)request()->getUserAgent();
        }

        // 1.8.5
        $date     = date('Y-m-d');
        $dateTime = date('Y-m-d H:i:s');
        if (!empty($collection->campaign) && !empty($collection->campaign->customer)) {
            $date     = $collection->campaign->customer->dateTimeFormatter->formatDateTime(null, null, 'yyyy-MM-dd');
            $dateTime = $collection->campaign->customer->dateTimeFormatter->formatDateTime();
        }

        $searchReplace = [
            '[DATETIME]'        => $dateTime,
            '[DATE]'            => $date,
            '[IP_ADDRESS]'      => $ipAddress,
            '[GEO_COUNTRY]'     => '',
            '[GEO_STATE]'       => '',
            '[GEO_CITY]'        => '',
            '[USER_AGENT]'      => StringHelper::truncateLength($userAgent, 250),
            '[UA_BROWSER]'      => '',
            '[UA_OS]'           => '',
            '[UA_ENGINE]'       => '',
            '[UA_DEVICE]'       => '',
            '[CAMPAIGN_NAME]'   => !empty($collection->campaign) ? $collection->campaign->name : '',
            '[CAMPAIGN_UID]'    => !empty($collection->campaign) ? $collection->campaign->campaign_uid : '',
        ];

        if ($ipLocation = IpLocation::findByIp($ipAddress)) {
            $searchReplace = CMap::mergeArray($searchReplace, [
                '[GEO_COUNTRY]' => $ipLocation->country_name,
                '[GEO_STATE]'   => $ipLocation->zone_name,
                '[GEO_CITY]'    => $ipLocation->city_name,
            ]);
        }

        if ($userAgent && version_compare(PHP_VERSION, '5.4', '>=')) {
            $parser = new WhichBrowser\Parser($userAgent, ['detectBots' => false]);
            $searchReplace = CMap::mergeArray($searchReplace, [
                '[UA_BROWSER]'  => !empty($parser->browser->name) ? $parser->browser->name : '',
                '[UA_OS]'       => !empty($parser->os->name) ? $parser->os->name : '',
                '[UA_ENGINE]'   => !empty($parser->engine->name) ? $parser->engine->name : '',
                '[UA_DEVICE]'   => !empty($parser->device->type) ? ucfirst($parser->device->type) : '',
            ]);
        }

        $searchReplace = (array)hooks()->applyFilters('common_helper_parsed_field_value_by_list_field_value_search_replace', $searchReplace, $collection);

        return (string)str_replace(array_keys($searchReplace), array_values($searchReplace), (string)$fieldValue);
    }

    /**
     * @param string $content
     *
     * @return bool
     */
    public static function contentHasXmlFeed(string $content): bool
    {
        $content = StringHelper::decodeSurroundingTags($content);
        return strpos($content, '[XML_FEED_BEGIN ') !== false && strpos($content, '[XML_FEED_END]') !== false;
    }

    /**
     * @param string $content
     *
     * @return bool
     */
    public static function contentHasJsonFeed(string $content): bool
    {
        $content = StringHelper::decodeSurroundingTags($content);
        return strpos($content, '[JSON_FEED_BEGIN ') !== false && strpos($content, '[JSON_FEED_END]') !== false;
    }

    /**
     * @param string $content
     *
     * @return bool
     */
    public static function hasRemoteContentTag(string $content): bool
    {
        $content = StringHelper::decodeSurroundingTags($content);
        return strpos($content, '[REMOTE_CONTENT ') !== false;
    }

    /**
     * @param string $content
     * @param Campaign $campaign
     * @param ListSubscriber|null $subscriber
     *
     * @return string
     * @throws CException
     */
    public static function fetchContentForRemoteContentTag(string $content, Campaign $campaign, ?ListSubscriber $subscriber = null): string
    {
        if (!self::hasRemoteContentTag($content)) {
            return $content;
        }

        // 1.6.4 - replace regular tags to avoid issues in next regex match
        $content = (string)preg_replace('/\[([A-Z0-9\_]+)\]/', '##(##$1##)##', $content);

        $pattern = '/\[REMOTE_CONTENT(.*?)\]/sx';
        $matched = (string)preg_match_all($pattern, $content, $multiMatches);

        // 1.6.4 - restore regular tags
        $content = (string)str_replace(['##(##', '##)##'], ['[', ']'], $content);

        if (!$matched) {
            return $content;
        }

        if (!isset($multiMatches[0], $multiMatches[0][0])) {
            return $content;
        }

        foreach ($multiMatches[0] as $fullHtml) {

            // 1.6.4 - put back the tags
            $fullHtml = (string)str_replace(['##(##', '##)##'], ['[', ']'], $fullHtml);

            // when it has been replaced already in the loop
            if (strpos($content, $fullHtml) === false) {
                continue;
            }

            // 1.6.4
            $searchReplace  = self::getCommonTagsSearchReplace($fullHtml, $campaign, $subscriber);
            $fullHtmlParsed = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $fullHtml);
            //

            $cacheKey      = sha1(__METHOD__ . $fullHtmlParsed . $campaign->campaign_uid);
            $remoteContent = cache()->get($cacheKey);

            if ($remoteContent === false) {
                $attributesPattern  = '/([a-z0-9\-\_]+) *= *(?:([\'"])(.*?)\\2|([^ "\'>]+))/';
                preg_match_all($attributesPattern, $fullHtmlParsed, $matches, PREG_SET_ORDER);
                if (empty($matches)) {
                    continue;
                }

                $attributes = [];
                foreach ($matches as $match) {
                    if (!isset($match[1], $match[3])) {
                        continue;
                    }
                    $attributes[strtolower((string)$match[1])] = $match[3];
                }

                $attributes['url'] = isset($attributes['url']) ? (string)str_replace('&amp;', '&', $attributes['url']) : '';
                if (!$attributes['url'] || !FilterVarHelper::url($attributes['url'])) {
                    continue;
                }

                try {
                    $remoteContent = (string)(new GuzzleHttp\Client())->get($attributes['url'])->getBody();
                } catch (Exception $e) {
                    $remoteContent = '';
                }
            }

            cache()->set($cacheKey, $remoteContent);

            $content = (string)str_replace($fullHtml, $remoteContent, $content);
        }

        return $content;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    public static function applyRandomContentTag(string $content): string
    {
        if (strpos($content, '[RANDOM_CONTENT') !== false && preg_match_all('/\[RANDOM_CONTENT:([^\]]+)\]/', $content, $matches)) {
            foreach ($matches[0] as $index => $tag) {
                if (!isset($matches[1]) || !isset($matches[1][$index])) {
                    continue;
                }
                $tagValue = explode('|', $matches[1][$index]);
                $randKey  = array_rand($tagValue);
                $content  = (string)str_replace($tag, $tagValue[$randKey], $content);
            }
        }
        return $content;
    }

    /**
     * @param Campaign $campaign
     *
     * @return array
     * @throws Exception
     */
    public static function getTimewarpOffsets(Campaign $campaign): array
    {
        $offsets = [];

        $timewarpHour = (int)$campaign->option->timewarp_hour;
        $timewarpMin  = (int)$campaign->option->timewarp_minute;

        foreach (DateTimeHelper::getTimeZones() as $timezone => $name) {
            $remote = Carbon\Carbon::now($timezone);
            if (isset($offsets[$remote->offset])) {
                continue;
            }

            $remoteSet = Carbon\Carbon::now($timezone);
            $remoteSet->hour($timewarpHour)->minute($timewarpMin);

            if ($remote->lte($remoteSet)) {
                continue;
            }

            $offsets[$remote->offset] = true;
        }

        if (empty($offsets)) {
            return [];
        }

        $negative = $positive = [];

        $offsets  = array_keys($offsets);
        foreach ($offsets as $offset) {
            if ($offset >= 0) {
                $positive[] = $offset;
            } else {
                $negative[] = $offset;
            }
        }
        unset($offsets);

        return [$negative, $positive];
    }

    /**
     * @param Campaign $campaign
     *
     * @return CDbCriteria|null
     * @throws Exception
     */
    public static function getTimewarpCriteria(Campaign $campaign): ?CDbCriteria
    {
        $criteria = new CDbCriteria();

        if (!($offsets = self::getTimewarpOffsets($campaign))) {
            return null;
        }

        $criteria->join = ' LEFT JOIN {{ip_location}} ipLocation ON ipLocation.ip_address = t.ip_address ';

        $offsetCondition = [];
        if (!empty($offsets[0])) {
            $offsetCondition[] = '(ipLocation.timezone_offset >= :nmin AND ipLocation.timezone_offset <= :nmax)';
            $criteria->params[':nmin'] = min($offsets[0]);
            $criteria->params[':nmax'] = max($offsets[0]);
        }
        if (!empty($offsets[1])) {
            $offsetCondition[] = '(ipLocation.timezone_offset >= :pmin AND ipLocation.timezone_offset <= :pmax)';
            $criteria->params[':pmin'] = min($offsets[1]);
            $criteria->params[':pmax'] = max($offsets[1]);
        }

        $condition = implode(' OR ', $offsetCondition);
        $condition = sprintf('(ipLocation.timezone_offset IS NULL OR (%s))', $condition);
        $criteria->addCondition($condition);

        return $criteria;
    }
}
