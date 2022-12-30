<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SearchExt
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class SearchExtSearch extends FormModel
{
    /**
     * @var string
     */
    public $term = '';

    /**
     * @var array
     */
    protected $commonKeywords = ['index', 'status', 'date added', 'last updated'];

    /**
     * @return array
     * @throws ReflectionException
     */
    public function getResults(): array
    {
        return $this->getItems();
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    protected function getItems(): array
    {
        $term = trim((string)$this->term);
        if (strlen($term) < 3 || strlen($term) > 100) {
            return [];
        }

        /** @var SearchExtSearchItem[] $items */
        $items   = $this->prepareItemsFromParsedFiles();
        $results = [];

        foreach ($items as $item) {
            if (stripos($item->title, $term) !== false) {
                $item->score += 2;
            }

            foreach ($item->keywords as $keyword) {
                if (stripos($keyword, $term) !== false) {
                    $item->score++;
                }
            }

            if (!empty($item->childrenGenerator) && is_callable($item->childrenGenerator, true)) {
                if ($item->children = (array)call_user_func($item->childrenGenerator, $term, $item)) {
                    $item->score++;
                }
            }

            if ($item->score) {
                $results[] = $item->getFields();
            }
        }

        $sort = [
            'score' => [],
            'title' => [],
        ];

        foreach ($results as $index => $result) {
            $sort['title'][$index] = $result['title'];
            $sort['score'][$index] = $result['score'];
        }

        array_multisort($sort['score'], SORT_DESC, $sort['title'], SORT_ASC, $results);

        return $results;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    protected function prepareItemsFromParsedFiles(): array
    {
        /** @var SearchExtSearchItem[] $items */
        $items = $this->getItemsFromParsedFiles();
        $items = (array)hooks()->applyFilters('search_searchable_items_list', (array)$items);
        foreach ($items as $index => $item) {
            if (!($item instanceof SearchExtSearchItem)) {
                unset($items[$index]);
            }
        }

        /** @var SearchExtSearchItem[] $items */
        $items = array_values($items);

        foreach ($items as $index => $item) {
            if ($item->skip === null) {
                $item->skip = [$this, '_defaultSkipLogic'];
            }

            if (!empty($item->skip) && is_callable($item->skip, true) && call_user_func($item->skip, $item)) {
                unset($items[$index]);
                continue;
            }

            if (!empty($item->keywordsGenerator) && is_callable($item->keywordsGenerator, true)) {
                $item->keywords = CMap::mergeArray($item->keywords, (array)call_user_func($item->keywordsGenerator));
            }

            $item->keywords = array_map('strtolower', $item->keywords);
            $item->keywords = array_filter(array_unique($item->keywords));
            $item->keywords = array_values(array_diff($item->keywords, $this->commonKeywords));
        }

        return array_values($items);
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    protected function getItemsFromParsedFiles(): array
    {
        $paths = [
            (string)Yii::getPathOfAlias('root.apps.' . MW_APP_NAME . '.controllers'),
        ];

        $excludeFiles  = $this->getExcludedFilesFromIndexing();
        /** @var ExtensionInit $extension */
        $extension     = extensionsManager()->getExtensionInstance('search');
        $behaviorPaths = [
            $extension->getPathOfAlias(MW_APP_NAME . '.behaviors.SearchExtBehavior'),
            $extension->getPathOfAlias('common.behaviors.SearchExtBehavior'),
        ];

        $items = [];
        foreach ($paths as $path) {
            $controllerFiles = (array)glob($path . '/*Controller.php');
            foreach ($controllerFiles as $controllerFile) {
                if (in_array($controllerFile, $excludeFiles)) {
                    continue;
                }

                $className = basename((string)$controllerFile, '.php');
                if (class_exists($className, false)) {
                    continue;
                }

                require_once $controllerFile;

                /** @var string $controllerId */
                $controllerId = strtolower(substr($className, 0, -10));

                /** @var Controller $instance */
                $instance   = new $className($controllerId);
                $reflection = new ReflectionClass($instance);

                $searchableActions = [];
                if (method_exists($instance, 'actionIndex')) {
                    $searchableActions['index'] = [];
                }

                if (method_exists($instance, 'actionCreate')) {
                    $searchableActions['create'] = [];
                }

                foreach ($behaviorPaths as $behaviorPath) {
                    $behaviorFile = $behaviorPath . $className . '.php';
                    if (is_file($behaviorFile)) {
                        require_once $behaviorFile;
                        $behaviorClassName = basename($behaviorFile, '.php');
                        $instance->attachBehavior($behaviorClassName, [
                            'class' => $behaviorClassName,
                        ]);
                        /** @var SearchExtBaseBehavior $behaviorInstance */
                        $behaviorInstance  = $instance->asa($behaviorClassName);
                        $searchableActions = CMap::mergeArray($searchableActions, $behaviorInstance->searchableActions());
                        break;
                    }
                }

                if (empty($searchableActions)) {
                    continue;
                }

                foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    if (strpos($method->name, 'action') !== 0 || $method->name == 'actions') {
                        continue;
                    }

                    $actionId = strtolower(substr($method->name, 6));
                    if (!isset($searchableActions[$actionId]) || !is_array($searchableActions[$actionId])) {
                        continue;
                    }

                    $skipActions        = ['index'];
                    $route              = $controllerId . '/' . $actionId;
                    $controllerIdParsed = $controllerId;
                    $controllerIdParsed = (string)str_replace('_', ' ', $controllerIdParsed);
                    $actionIdParsed     = (string)str_replace('_', ' ', $actionId);
                    $item               = new SearchExtSearchItem();

                    $item->title = ucfirst(strtolower((string)$controllerIdParsed));
                    if (!in_array($actionId, $skipActions)) {
                        $item->title .= ' / ' . ucfirst(strtolower((string)$actionIdParsed));
                    }

                    $item->url   = createUrl($route);
                    $item->route = $route;

                    if (!in_array($actionId, $skipActions)) {
                        $item->keywords[] = $controllerIdParsed . ' ' . $actionIdParsed;
                        $item->keywords[] = $actionIdParsed . ' ' . $controllerIdParsed;
                    } else {
                        $item->keywords[] = $controllerIdParsed;
                    }

                    $item->mergeAttributes($searchableActions[$actionId]);

                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    /**
     * @return array
     */
    protected function getExcludedFilesFromIndexing(): array
    {
        $backendControllers  = (string)Yii::getPathOfAlias('root.apps.backend.controllers') . DIRECTORY_SEPARATOR;
        $customerControllers = (string)Yii::getPathOfAlias('root.apps.customer.controllers') . DIRECTORY_SEPARATOR;
        $excludeFiles = [
            $backendControllers . 'UpdateController.php',
            $customerControllers . 'List_exportController.php',
            $customerControllers . 'List_fieldsController.php',
            $customerControllers . 'List_formsController.php',
            $customerControllers . 'List_importController.php',
            $customerControllers . 'List_pageController.php',
            $customerControllers . 'List_segments_exportController.php',
            $customerControllers . 'List_segmentsController.php',
            $customerControllers . 'List_subscribersController.php',
            $customerControllers . 'List_toolsController.php',
            $customerControllers . 'Survey_fieldsController.php',
            $customerControllers . 'Survey_segmentsController.php',
            $customerControllers . 'Survey_respondersController.php',
            $customerControllers . 'Survey_segments_exportController.php',
            $customerControllers . 'Suppression_list_emailsController.php',
            $customerControllers . 'Campaign_reportsController.php',
            $customerControllers . 'Campaign_exportController.php',
        ];

        return (array)hooks()->applyFilters('search_exclude_files_from_indexing', $excludeFiles);
    }

    /**
     * @param SearchExtSearchItem $item
     *
     * @return bool
     */
    protected function _defaultSkipLogic(SearchExtSearchItem $item): bool
    {
        if (apps()->isAppName('backend')) {

            /** @var User $user */
            $user = user()->getModel();

            return !$user->hasRouteAccess($item->route);
        }

        return false;
    }
}
