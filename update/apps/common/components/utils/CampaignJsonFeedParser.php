<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignJsonFeedParser
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3
 */

class CampaignJsonFeedParser
{
    /**
     * @var int
     */
    public static $maxItemsCount = 100;

    /**
     * @var int
     */
    public static $itemsCount = 10;

    /**
     * @var int
     */
    public static $itemsOffset = 0;

    /**
     * @var int
     */
    public static $daysBack = -1;

    /**
     * @var string
     */
    public static $noItemAction = '';

    /**
     * @param string $content
     * @param Campaign $campaign
     * @param ListSubscriber|null $subscriber
     * @param bool $cache
     * @param string $cacheKeySuffix
     * @param DeliveryServer|null $server
     *
     * @return string
     * @throws CException
     */
    public static function parseContent(
        string $content,
        Campaign $campaign,
        ?ListSubscriber $subscriber = null,
        bool $cache = false,
        string $cacheKeySuffix = '',
        ?DeliveryServer $server = null
    ): string {
        if (!$cacheKeySuffix) {
            $cacheKeySuffix = $content;
        }
        $cacheKey = sha1(__METHOD__ . $campaign->campaign_uid . sha1($cacheKeySuffix));
        if ($cache && ($cachedContent = cache()->get($cacheKey))) {
            return $cachedContent;
        }

        $content = StringHelper::decodeSurroundingTags($content);
        if (!CampaignHelper::contentHasJsonFeed($content)) {
            return $content;
        }

        // https://stackoverflow.com/questions/13299471/regex-matching-square-brackets-followed-by-parenthesis-where-the-square-brackets
        $feedStartPattern   = '(\[(JSON_FEED_BEGIN)((?:[^[\]]*\[[^[\]]*])*[^[\]]*)](?:\(([a-z]+)\))?)';
        $feedContentPattern = '(((?!\[JSON_FEED_END\]).)*)';
        $feedEndPattern     = '(\[JSON_FEED_END\])';
        $fullFeedPattern    = sprintf('~%s%s%s~six', $feedStartPattern, $feedContentPattern, $feedEndPattern);
        if (!preg_match_all($fullFeedPattern, $content, $multiMatches)) {
            return $content;
        }

        if (!isset($multiMatches[0], $multiMatches[0][0])) {
            return $content;
        }

        $doCache = false;
        foreach ($multiMatches[0] as $fullFeedHtml) {
            $_fullFeedHtml = html_decode($fullFeedHtml);

            if (!preg_match($fullFeedPattern, $_fullFeedHtml, $matches)) {
                continue;
            }

            if (!isset($matches[1], $matches[5])) {
                continue;
            }

            $feedStartTag     = $matches[1];
            $feedItemTemplate = $matches[5];

            // in case the JSON_FEED_BEGIN tag contains dynamic tags
            $searchReplace = CampaignHelper::getCommonTagsSearchReplace($feedStartTag, $campaign, $subscriber, $server);
            $feedStartTag  = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $feedStartTag);

            $attributesPattern  = '/([a-z0-9\-\_]+) *= *(?:([\'"])(.*?)\\2|([^ "\'>]+))/';
            preg_match_all($attributesPattern, $feedStartTag, $matches, PREG_SET_ORDER);
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

            $attributes['url'] = isset($attributes['url']) ? (string)str_replace('&amp;', '&', $attributes['url']) : null;
            if (!$attributes['url'] || !FilterVarHelper::url($attributes['url'])) {
                continue;
            }

            $count = self::$itemsCount;
            if (isset($attributes['count']) && (int)$attributes['count'] > 0 && (int)$attributes['count'] <= self::$maxItemsCount) {
                $count = (int)$attributes['count'];
            }

            // 1.5.1
            $offset = self::$itemsOffset;
            if (isset($attributes['offset']) && (int)$attributes['offset'] > 0) {
                $offset = (int)$attributes['offset'];
            }

            // 1.5.1
            $daysBack = self::$daysBack;
            if (isset($attributes['days-back']) && (int)$attributes['days-back'] >= 0) {
                $daysBack = (int)$attributes['days-back'];
            }

            // 1.5.1
            $noItemAction    = !isset($attributes['no-item-action']) ? self::$noItemAction : $attributes['no-item-action'];

            // 1.7.4
            $sendOnlyUniqueItems = !empty($attributes['send-only-unique-items']) && strtolower((string)$attributes['send-only-unique-items']) == 'yes';

            $doCache   = !$campaign->getIsDraft() && $cache;
            $feedItems = self::getRemoteFeedItems($attributes['url'], $count, $campaign, $doCache, $offset, $daysBack, $noItemAction, $sendOnlyUniqueItems);

            $feedItemsMap = [
                '[JSON_FEED_ITEM_TITLE]'         => 'title',
                '[JSON_FEED_ITEM_DESCRIPTION]'   => 'description',
                '[JSON_FEED_ITEM_CONTENT]'       => 'content',
                '[JSON_FEED_ITEM_IMAGE]'         => 'image',
                '[JSON_FEED_ITEM_LINK]'          => 'link',
                '[JSON_FEED_ITEM_PUBDATE]'       => 'pubDate',
                '[JSON_FEED_ITEM_GUID]'          => 'guid',
            ];

            $html = '';
            foreach ($feedItems as $feedItem) {
                $itemHtml = $feedItemTemplate;
                foreach ($feedItemsMap as $tag => $mapValue) {
                    if (!isset($feedItem[$mapValue]) || !is_string($feedItem[$mapValue])) {
                        continue;
                    }
                    $itemHtml = (string)str_replace($tag, $feedItem[$mapValue], $itemHtml);
                }

                if (sha1($itemHtml) != sha1($feedItemTemplate)) {
                    $html .= $itemHtml;
                }
            }

            // since 1.9.2
            foreach ($feedItems as $index => $feedItem) {
                $itemHtml = $feedItemTemplate;
                foreach ($feedItemsMap as $tag => $mapValue) {
                    if (!isset($feedItem[$mapValue]) || !is_string($feedItem[$mapValue])) {
                        continue;
                    }
                    $tag = (string)str_replace('_FEED_ITEM_', '_FEED_ITEM_' . ($index + 1) . '_', $tag);
                    $itemHtml = (string)str_replace($tag, $feedItem[$mapValue], $itemHtml);
                }

                if (sha1($itemHtml) != sha1($feedItemTemplate)) {
                    $html .= $itemHtml;
                }
            }
            //

            // since 1.5.1
            foreach ($feedItems as $index => $feedItem) {
                foreach ($feedItemsMap as $tag => $mapValue) {
                    if (!isset($feedItem[$mapValue]) || !is_string($feedItem[$mapValue])) {
                        continue;
                    }
                    $tagNum = (string)str_replace('_FEED_ITEM_', '_FEED_ITEM_' . ($index + 1) . '_', $tag);
                    $html   = (string)str_replace($tagNum, $feedItem[$mapValue], $html);
                }
            }
            //

            $content = (string)str_replace($fullFeedHtml, $html, $content);
        }

        if ($doCache) {
            $cacheTTL = !$campaign->getIsAutoresponder() && defined('MW_CACHE_TTL') ? MW_CACHE_TTL : 3600 * 12;
            cache()->set($cacheKey, $content, $cacheTTL);
        }

        return $content;
    }

    /**
     * @param string $url
     * @param int $count
     * @param Campaign $campaign
     * @param bool $cache
     * @param int $offset
     * @param int $daysBack
     * @param string $noItemAction
     * @param bool $sendOnlyUniqueItems
     *
     * @return array
     * @throws Exception
     */
    public static function getRemoteFeedItems(
        string $url,
        int $count,
        Campaign $campaign,
        bool $cache = false,
        int $offset = 0,
        int $daysBack = -1,
        string $noItemAction = '',
        bool $sendOnlyUniqueItems = false
    ): array {
        $accessKey = sha1(sprintf('m:%s.c:%s.u:%s.c:%s.o:%s.d:%s.s:%s', __METHOD__, $campaign->campaign_uid, $url, $count, $offset, $daysBack, (string)$campaign->send_at));

        // 1.8.1 - mutex addition
        if (!mutex()->acquire($accessKey, 30)) {
            return [];
        }

        if ($cache && ($items = cache()->get($accessKey)) !== false) {
            mutex()->release($accessKey);
            return $items;
        }

        $items = [];
        if ($cache) {
            $cacheTTL = !$campaign->getIsAutoresponder() && defined('MW_CACHE_TTL') ? MW_CACHE_TTL : 3600;
            cache()->set($accessKey, $items, $cacheTTL);
        }

        try {
            $json = (array)json_decode((string)(new GuzzleHttp\Client())->get($url)->getBody());
        } catch (Exception $e) {
            $json = [];
        }

        if (empty($json)) {
            mutex()->release($accessKey);
            return $items;
        }

        $offset = (int)$offset;
        $index  = 0;

        /** @var stdClass $item */
        foreach ($json as $item) {

            // 1.5.1
            if ($daysBack >= 0 && (int)strtotime((string)$item->pubDate) < (int)strtotime('-' . $daysBack . ' days')) {
                continue;
            }

            $index++;

            // 1.5.1
            if ($offset > 0 && $offset >= $index) {
                continue;
            }

            if (count($items) >= $count) {
                break;
            }

            $itemMap = [
                'title'         => null,
                'description'   => null,
                'content'       => null,
                'image'         => null,
                'link'          => null,
                'pubDate'       => null,
                'guid'          => null,
            ];

            if (!empty($item->title)) {
                $itemMap['title'] = (string)$item->title;
            }

            if (!empty($item->description)) {
                $itemMap['description'] = (string)$item->description;
            }

            if (!empty($item->content)) {
                $itemMap['content'] = (string)$item->content;
            }

            if (!empty($item->image)) {
                $itemMap['image'] = (string)$item->image;
            }

            if (!empty($item->link)) {
                $itemMap['link'] = (string)$item->link;
            }

            if (!empty($item->pubDate)) {
                $itemMap['pubDate'] = (string)$item->pubDate;
            }

            if (!empty($item->guid)) {
                $itemMap['guid'] = (string)$item->guid;
            }

            $itemMap = array_map(['CHtml', 'decode'], $itemMap);
            // $itemMap = array_map(array('CHtml', 'encode'), $itemMap);

            // 1.7.4
            if ($sendOnlyUniqueItems) {
                // make sure the item hasn't been served. If it has, skip it
                $itemCacheKey = (int)$campaign->list_id . ':' . (int)$campaign->segment_id . ':' . sha1(serialize($itemMap));
                if (!$campaign->getIsRecurring()) {
                    $itemCacheKey .= ':' . $campaign->campaign_id;
                }
                if (cache()->get($itemCacheKey)) {
                    continue;
                }
                cache()->set($itemCacheKey, true);
            }

            $items[] = $itemMap;
        }

        // 1.5.1
        if (empty($items) && $noItemAction == 'postpone-campaign' && $campaign->getIsProcessing()) {
            $campaign->saveSendAt(new CDbExpression('DATE_ADD(NOW(), INTERVAL 1 DAY)'));
            mutex()->release($accessKey);
            throw new Exception('Rescheduling the campaign because no feed item was found!', 95);
        }
        //

        if ($cache) {
            $cacheTTL = !$campaign->getIsAutoresponder() && defined('MW_CACHE_TTL') ? MW_CACHE_TTL : 3600;
            cache()->set($accessKey, $items, $cacheTTL);
        }

        mutex()->release($accessKey);

        return $items;
    }
}
