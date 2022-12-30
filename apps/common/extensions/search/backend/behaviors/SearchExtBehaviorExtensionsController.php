<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class SearchExtBehaviorExtensionsController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['extend', 'extension'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
        ];
    }

    /**
     * @param string $term
     * @param SearchExtSearchItem|null $parent
     *
     * @return array
     */
    public function _indexChildrenGenerator(string $term, ?SearchExtSearchItem $parent = null): array
    {
        /** @var ExtensionInit[] $extensions */
        $extensions = extensionsManager()->getAllExtensions();
        $items      = [];

        foreach ($extensions as $extension) {
            if ((stripos($extension->name, $term) !== false) || (stripos($extension->description, $term) !== false)) {
                $url = createUrl('extensions/index');
                if ($extension->getIsEnabled() && $extension->getPageUrl()) {
                    $url = (string)$extension->getPageUrl();
                }

                $item        = new SearchExtSearchItem();
                $item->title = $extension->name;
                $item->url   = $url;
                $item->score++;
                $items[] = $item->getFields();
            }
        }
        return $items;
    }
}
