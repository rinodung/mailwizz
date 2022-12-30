<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * BaseController
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * Class BaseController
 * @property CMap $data
 * @property mixed $onBeforeAction
 */
class BaseController extends CController
{

    /**
     * @var bool
     */
    protected $_pageAssetsCListInitialized = false;

    /**
     * @var CMap the data to be passed from view to view
     */
    private $_data;

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        // data passed in each view.
        $this->setData([
            'pageMetaTitle'         => $common->getSiteName(),
            'pageMetaDescription'   => $common->getSiteDescription(),
            'pageMetaKeywords'      => $common->getSiteKeywords(),
            'pageHeading'           => '',
            'pageBreadcrumbs'       => [],
            'hooks'                 => hooks(),
        ]);

        $appName = apps()->getCurrentAppName();
        hooks()->doAction($appName . '_controller_init');
        hooks()->doAction($appName . '_controller_' . $this->getId() . '_init');

        try {
            $this->onControllerInit(new CEvent($this));
        } catch (Exception $e) {
        }
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onControllerInit(CEvent $event)
    {
        $this->raiseEvent('onControllerInit', $event);
    }

    /**
     * @return array
     * @throws CException
     */
    public function actions()
    {
        $actions = new CMap();

        $appName = apps()->getCurrentAppName();

        /** @var CMap $actions */
        $actions = hooks()->applyFilters($appName . '_controller_' . $this->getId() . '_actions', $actions);

        $this->onActions(new CEvent($this, [
            'actions' => $actions,
        ]));

        return $actions->toArray();
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onActions(CEvent $event)
    {
        $this->raiseEvent('onActions', $event);
    }

    /**
     * @return array
     * @throws CException
     */
    public function behaviors()
    {
        $behaviors = new CMap();

        $appName = apps()->getCurrentAppName();

        /** @var CMap $behaviors */
        $behaviors = hooks()->applyFilters($appName . '_controller_behaviors', $behaviors);

        /** @var CMap $behaviors */
        $behaviors = hooks()->applyFilters($appName . '_controller_' . $this->getId() . '_behaviors', $behaviors);

        $this->onBehaviors(new CEvent($this, [
            'behaviors' => $behaviors,
        ]));

        return $behaviors->toArray();
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onBehaviors(CEvent $event)
    {
        $this->raiseEvent('onBehaviors', $event);
    }

    /**
     * @return array
     * @throws CException
     */
    public function filters()
    {
        $filters = new CMap();

        $appName = apps()->getCurrentAppName();

        /** @var CMap $filters */
        $filters = hooks()->applyFilters($appName . '_controller_filters', $filters);
        /** @var CMap $filters */
        $filters = hooks()->applyFilters($appName . '_controller_' . $this->getId() . '_filters', $filters);

        $this->onFilters(new CEvent($this, [
            'filters' => $filters,
        ]));

        return $filters->toArray();
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onFilters(CEvent $event)
    {
        $this->raiseEvent('onFilters', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onBeforeAction(CEvent $event)
    {
        $this->raiseEvent('onBeforeAction', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onAfterAction(CEvent $event)
    {
        $this->raiseEvent('onAfterAction', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onBeforeRender(CEvent $event)
    {
        $this->raiseEvent('onBeforeRender', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onAfterRender(CEvent $event)
    {
        $this->raiseEvent('onAfterRender', $event);
    }

    /**
     * @param string $_viewFile_
     * @param mixed $_data_
     * @param bool $_return_
     *
     * @return string
     */
    public function renderInternal($_viewFile_, $_data_=null, $_return_=false)
    {
        if ($_data_ === null) {
            $_data_ = [];
        }

        /** @var CAttributeCollection $_dataCollection_ */
        $_dataCollection_ = $this->getData();

        $_dataCollection_->mergeWith($_data_, false);
        $_data_ = $_dataCollection_->toArray();

        return parent::renderInternal($_viewFile_, $_data_, $_return_);
    }

    /**
     * Render JSON instead of HTML
     *
     * @param array $data the data to be JSON encoded
     * @param int $statusCode the status code
     * @param array $headers list of headers to send in the response
     * @param string $callback the callback for the jsonp calls
     *
     * @return void
     * @throws CException
     */
    public function renderJson($data = [], $statusCode = 200, array $headers = [], $callback = null)
    {
        $response = new JsonResponse();

        $response
            ->setHeaders($headers)
            ->setStatusCode($statusCode)
            ->setData($data)
            ->setCallback($callback)
            ->send();

        app()->end();
    }

    /**
     * Set data available in all views and sub views.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return $this
     */
    final public function setData($key, $value = null): self
    {
        try {
            if (is_string($key) && $value !== null) {
                /** @var CAttributeCollection $data */
                $data = $this->getData();
                $data->mergeWith([$key => $value], false);
            } elseif (is_array($key)) {
                /** @var CAttributeCollection $data */
                $data = $this->getData();
                $data->mergeWith($key, false);
            }
        } catch (Exception $e) {
        }
        return $this;
    }

    /**
     * @param mixed $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    final public function getData($key = null, $defaultValue = null)
    {
        if (!($this->_data instanceof CAttributeCollection)) {
            /** @var CAttributeCollection $data */
            $data = null;

            try {
                $data = new CAttributeCollection((array)$this->_data);
                $data->caseSensitive = true;
            } catch (Exception $e) {
            }

            $this->_data = $data;
        }

        // special case when clist is not initialized for the keys
        if (!$this->_pageAssetsCListInitialized) {
            $cList = ['pageScripts', 'pageStyles', 'bodyClasses'];
            foreach ($cList as $name) {
                if ((!$this->_data->contains($name) || !($this->_data->itemAt($name) instanceof CList))) {
                    try {
                        $this->_data->add($name, new CList());
                    } catch (Exception $e) {
                    }
                }
            }
            $this->_pageAssetsCListInitialized = true;
        }

        // 2.0.0
        if (!$this->_data->contains('controller')) {
            try {
                $this->_data->add('controller', $this);
            } catch (Exception $e) {
            }
        }

        if ($key !== null) {
            return $this->_data->contains($key) ? $this->_data->itemAt($key) : $defaultValue;
        }

        return $this->_data;
    }

    /**
     * @return CList
     * @since 2.0.0
     *
     */
    final public function getPageScripts(): CList
    {
        /** @var CList $list */
        $list = $this->getData('pageScripts', new CList());

        return $list;
    }

    /**
     * @param array $item
     *
     * @return BaseController
     * @since 2.0.0
     */
    final public function addPageScript(array $item): self
    {
        try {
            $this->getPageScripts()->add($item);
        } catch (Exception $e) {
        }
        return $this;
    }

    /**
     * @since 2.0.0
     * @param array $items
     *
     * @return BaseController
     */
    final public function addPageScripts(array $items): self
    {
        try {
            $this->getPageScripts()->mergeWith($items);
        } catch (Exception $e) {
        }
        return $this;
    }

    /**
     * @return CList
     * @since 2.0.0
     */
    final public function getPageStyles(): CList
    {
        /** @var CList $list */
        $list = $this->getData('pageStyles', new CList());

        return $list;
    }

    /**
     * @param array $item
     *
     * @return BaseController
     * @since 2.0.0
     */
    final public function addPageStyle(array $item): self
    {
        try {
            $this->getPageStyles()->add($item);
        } catch (Exception $e) {
        }
        return $this;
    }

    /**
     * @since 2.0.0
     * @param array $items
     *
     * @return BaseController
     */
    final public function addPageStyles(array $items): self
    {
        try {
            $this->getPageStyles()->mergeWith($items);
        } catch (Exception $e) {
        }
        return $this;
    }

    /**
     * since 1.3.5.4 - body classes filter hook
     *
     * @return string
     */
    public function getBodyClasses()
    {
        try {
            /** @var CList $bodyClasses */
            $bodyClasses = $this->getData('bodyClasses', new CList());
            $bodyClasses = $bodyClasses->toArray();
            $bodyClasses = array_merge($bodyClasses, ['ctrl-' . $this->getId(), 'act-' . $this->getAction()->getId()]);
            /** @var array $bodyClasses */
            $bodyClasses = (array)hooks()->applyFilters('body_classes', $bodyClasses);
        } catch (Exception $e) {
            $bodyClasses = [];
        }
        return implode(' ', array_map('trim', array_unique($bodyClasses)));
    }

    /**
     * @since 1.3.5.6
     *
     * @return void
     */
    public function getAfterOpeningBodyTag()
    {
        hooks()->addAction('after_opening_body_tag', [$this, '_gaTrackingCode'], 1000);
        hooks()->doAction('after_opening_body_tag', $this);
    }

    /**
     * @since 1.3.5.6
     * @param Controller $controller
     *
     * @return void
     */
    public function _gaTrackingCode($controller)
    {
        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        if (!($trackingCode = $common->getGaTrackingCodeId())) {
            return;
        }
        echo sprintf("<script>
          (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
          (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
          m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
          })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
          ga('create', '%s', 'auto');
          ga('send', 'pageview');
        </script>", $trackingCode);
    }

    /**
     * @since 1.3.5.7
     * @return mixed
     */
    public function getHtmlOrientation()
    {
        // $orientation = app()->locale->orientation;
        $orientation = defined('MW_HTML_ORIENTATION') ? MW_HTML_ORIENTATION : 'ltr';
        return hooks()->applyFilters('html_orientation', $orientation);
    }

    /**
     * @param CAction $action
     *
     * @return bool
     * @throws CException
     */
    protected function beforeAction($action)
    {
        $appName = apps()->getCurrentAppName();
        hooks()->doAction($appName . '_controller_before_action', $action);
        hooks()->doAction($appName . '_controller_' . $this->getId() . '_before_action', $action);

        $this->onBeforeAction(new CEvent($this, [
            'action' => $action,
        ]));

        // 1.3.7.3
        if (!headers_sent()) {
            header('X-XSS-Protection: 1; mode=block');
        }

        return parent::beforeAction($action);
    }

    /**
     * @param CAction $action
     *
     * @return void
     * @throws CException
     */
    protected function afterAction($action)
    {
        $appName = apps()->getCurrentAppName();
        hooks()->doAction($appName . '_controller_after_action', $action);
        hooks()->doAction($appName . '_controller_' . $this->getId() . '_after_action', $action);

        $this->onAfterAction(new CEvent($this, [
            'action' => $action,
        ]));

        parent::afterAction($action);
    }

    /**
     * @param string $view
     *
     * @return bool
     * @throws CException
     */
    protected function beforeRender($view)
    {
        if (request()->enableCsrfValidation) {
            clientScript()->registerMetaTag(request()->csrfTokenName, 'csrf-token-name');
            clientScript()->registerMetaTag(request()->getCsrfToken(), 'csrf-token-value');
        }

        $appName = apps()->getCurrentAppName();
        hooks()->doAction($appName . '_controller_before_render', $view);
        hooks()->doAction($appName . '_controller_' . $this->getId() . '_before_render', $view);

        $this->onBeforeRender(new CEvent($this, [
            'view' => $view,
        ]));

        // register assets
        $this->_registerAssets();

        return parent::beforeRender($view);
    }

    /**
     * @param string $view
     * @param string $output
     *
     * @return void
     * @throws CException
     */
    protected function afterRender($view, &$output)
    {
        $appName = apps()->getCurrentAppName();
        $output  = hooks()->applyFilters($appName . '_controller_after_render', $output, $view);
        $output  = hooks()->applyFilters($appName . '_controller_' . $this->getId() . '_after_render', $output, $view);

        $this->onAfterRender(new CEvent($this, [
            'view'      => $view,
            'output'    => &$output,
        ]));

        parent::afterRender($view, $output);
    }

    /**
     * @return void
     * @throws CException
     */
    protected function _registerAssets()
    {
        /** @var CList $pageScriptsFilters */
        $pageScriptsFilters = hooks()->applyFilters('register_scripts', new CList());

        /** @var CList $pageStylesFilters */
        $pageStylesFilters = hooks()->applyFilters('register_styles', new CList());

        // enqueue all custom scripts and styles registered so far
        $this->addPageScripts($pageScriptsFilters->toArray());
        $this->addPageStyles($pageStylesFilters->toArray());

        // register jquery-migrate and jquery
        $this->getPageScripts()->insertAt(0, ['src' => 'jquery-migrate', 'core-script' => true]);
        $this->getPageScripts()->insertAt(0, ['src' => 'jquery', 'core-script' => true]);

        // register scripts
        $pageScripts  = [];
        $sort         = [];
        $_pageScripts = $this->getPageScripts()->toArray();

        foreach ($_pageScripts as $index => $item) {
            if (empty($item['src'])) {
                $this->getPageScripts()->removeAt($index);
                continue;
            }
            $priority       = !empty($item['priority']) ? (int)$item['priority'] : 0;
            $sort[]         = $priority + $index;
            $pageScripts[]  = $item;
        }
        array_multisort($sort, $pageScripts);

        foreach ($pageScripts as $item) {
            $htmlOptions = !empty($item['htmlOptions']) ? (array)$item['htmlOptions'] : [];
            $position    = isset($item['position']) ? (int)$item['position'] : null;
            if (!empty($item['core-script'])) {
                clientScript()->registerCoreScript($item['src']);
            } else {
                $version = substr(sha1(MW_VERSION), -8);
                $src     = $item['src'];
                $src    .= strpos($src, '?') !== false ? '&av=' . $version : '?av=' . $version;

                clientScript()->registerScriptFile($src, $position, $htmlOptions);
            }
        }

        // register styles
        $pageStyles   = [];
        $sort         = [];
        $_pageStyles  = $this->getPageStyles()->toArray();

        foreach ($_pageStyles as $index => $item) {
            if (empty($item['src'])) {
                $this->getPageStyles()->removeAt($index);
                continue;
            }
            $priority      = !empty($item['priority']) ? (int)$item['priority'] : 0;
            $sort[]        = $priority + $index;
            $pageStyles[]  = $item;
        }
        array_multisort($sort, $pageStyles);

        foreach ($pageStyles as $item) {
            $media = $item['media'] ?? null;

            $version = substr(sha1(MW_VERSION), -8);
            $src     = $item['src'];
            $src    .= strpos($src, '?') !== false ? '&av=' . $version : '?av=' . $version;

            clientScript()->registerCssFile($src, $media);
        }
    }
}
