<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailTemplateTagFilter
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class EmailTemplateTagFilter extends CApplicationComponent
{
    /**
     * @return array
     */
    public function getFiltersMap(): array
    {
        return [
            // name            // callback
            'urlencode'     => 'urlencode',
            'rawurlencode'  => 'rawurlencode',
            'htmlencode'    => ['CHtml', 'encode'],
            'trim'          => 'trim',
            'uppercase'     => 'strtoupper',
            'lowercase'     => 'strtolower',
            'ucwords'       => 'ucwords',
            'ucfirst'       => 'ucfirst',
            'reverse'       => 'strrev',
            'defaultvalue'  => [$this, 'setDefaultValueIfEmpty'],
            'defaultValue'  => [$this, 'setDefaultValueIfEmpty'],
            'md5'           => 'md5',
            'sha1'          => 'sha1',
            'base64encode'  => 'base64_encode',
        ];
    }

    /**
     * @param string $content
     * @param array $registeredTags
     *
     * @return string
     */
    public function apply(string $content, array $registeredTags = []): string
    {
        $filtersMap = $this->getFiltersMap();

        $searchReplace = [];
        foreach ($registeredTags as $tagName => $tagValue) {

            //if (empty($tagValue)) {
            //    continue;
            //}

            $tagName = (string)str_replace(['[', ']'], '', $tagName);
            if (strpos($content, '[' . $tagName . ':filter:') === false) {
                continue;
            }

            // do we really need preg_quote ?
            if (preg_match_all('/\[' . preg_quote($tagName, '/') . ':filter:([a-z0-9|,\(\)\s\p{L}&;#]+)\]/iu', $content, $matches)) {
                if (empty($matches[1])) {
                    continue;
                }

                $filterTags     = array_unique($matches[0]);
                $filterStrings  = array_unique($matches[1]);

                if (count($filterStrings) != count($filterTags)) {
                    continue;
                }

                $tagToFilters = (array)array_combine($filterTags, $filterStrings);
                unset($filterTags, $filterStrings);

                foreach ($tagToFilters as $tag => $filtersString) {
                    $filters = (array)explode('|', $filtersString);
                    if (empty($filters)) {
                        continue;
                    }

                    $filters    = array_map('trim', $filters);
                    $filtered   = false;
                    foreach ($filters as $filterName) {
                        $filterArgs = [];
                        if (($startPos = strpos($filterName, '(')) !== false && ($endPos = strpos($filterName, ')')) !== false) {
                            $name = substr($filterName, 0, $startPos);
                            $args = trim(substr($filterName, $startPos + 1), ')');
                            $filterArgs = array_map('trim', explode(',', $args));
                            $filterName = $name;
                            unset($name, $args);
                        }

                        if (!isset($filtersMap[$filterName]) || !is_callable($filtersMap[$filterName])) {
                            continue;
                        }
                        array_unshift($filterArgs, $tagValue);
                        $filtered   = true;
                        $tagValue   = call_user_func_array($filtersMap[$filterName], $filterArgs);
                    }

                    if ($filtered) {
                        $searchReplace[$tag] = $tagValue;
                    }
                }
            }
        }

        if (empty($searchReplace)) {
            return $content;
        }
        return (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $content);
    }

    /**
     * @param mixed $tagValue
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function setDefaultValueIfEmpty($tagValue, $defaultValue = null)
    {
        return !empty($tagValue) ? $tagValue : $defaultValue;
    }
}
