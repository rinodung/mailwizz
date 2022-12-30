<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * HttpRequest
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class BaseHttpRequest extends CHttpRequest
{
    /**
     * @var bool
     */
    public $globalsCleaned = false;

    /**
     * @var array
     */
    public $noCsrfValidationRoutes = [];

    /**
     * @param string $name
     * @param mixed $defaultValue
     *
     * @return mixed
     * @throws CException
     */
    public function getPost($name, $defaultValue = null)
    {
        if (!$this->globalsCleaned) {
            ioFilter()->cleanGlobals();
        }

        if (empty($name)) {
            return $_POST;
        }

        return parent::getPost($name, $defaultValue);
    }

    /**
     * @param string $name
     * @param mixed $defaultValue
     *
     * @return mixed
     * @throws CException
     */
    public function getQuery($name, $defaultValue = null)
    {
        if (!$this->globalsCleaned) {
            ioFilter()->cleanGlobals();
        }

        if (empty($name)) {
            return $_GET;
        }

        return parent::getQuery($name, $defaultValue);
    }

    /**
     * @param string $name
     * @param mixed $defaultValue
     *
     * @return mixed
     * @throws CException
     */
    public function getPostPut($name, $defaultValue = null)
    {
        return $this->getPost($name, $this->getPut($name, $defaultValue));
    }

    /**
     * @param string $name
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getPut($name, $defaultValue = null)
    {
        if (empty($name)) {
            return ioFilter()->stripClean($this->getRestParams());
        }

        return ioFilter()->stripClean(parent::getPut($name, $defaultValue));
    }

    /**
     * @param string $name
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getDelete($name, $defaultValue = null)
    {
        if (empty($name)) {
            return ioFilter()->stripClean($this->getRestParams());
        }

        return ioFilter()->stripClean(parent::getDelete($name, $defaultValue));
    }

    /**
     * @param string $name
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getPatch($name, $defaultValue = null)
    {
        if (empty($name)) {
            return ioFilter()->stripClean($this->getRestParams());
        }

        return ioFilter()->stripClean(parent::getPatch($name, $defaultValue));
    }

    /**
     * @param string $name
     * @param mixed $defaultValue
     *
     * @return mixed
     * @throws CException
     */
    public function getServer($name, $defaultValue = null)
    {
        if (!$this->globalsCleaned) {
            ioFilter()->cleanGlobals();
        }

        if (empty($name)) {
            return $_SERVER;
        }

        $name = strtoupper((string)$name);
        return $_SERVER[$name] ?? $defaultValue;
    }

    /**
     * @param string $name
     * @param mixed $defaultValue
     *
     * @return mixed
     * @throws CException
     */
    public function getOriginalPost($name, $defaultValue = null)
    {
        if (!$this->globalsCleaned) {
            ioFilter()->cleanGlobals();
        }

        $map = app_param('POST', new CMap());
        if (!($map instanceof CMap)) {
            $map = new CMap();
        }

        if (empty($name)) {
            return $map->toArray();
        }

        return $map->contains($name) ? $map->itemAt($name) : $defaultValue;
    }

    /**
     * @param string $name
     * @param mixed $defaultValue
     *
     * @return mixed
     * @throws CException
     */
    public function getOriginalQuery($name, $defaultValue = null)
    {
        if (!$this->globalsCleaned) {
            ioFilter()->cleanGlobals();
        }

        $map = app_param('GET', new CMap());
        if (!($map instanceof CMap)) {
            $map = new CMap();
        }

        if (empty($name)) {
            return $map->toArray();
        }

        return $map->contains($name) ? $map->itemAt($name) : $defaultValue;
    }

    /**
     * @return void
     * @throws CException
     */
    protected function normalizeRequest()
    {
        parent::normalizeRequest();
        if ($this->getIsPostRequest() && $this->enableCsrfValidation && !$this->checkCurrentRoute()) {
            app()->detachEventHandler('onBeginRequest', [$this, 'validateCsrfToken']);
        }
    }

    /**
     * @return bool
     * @throws CException
     */
    protected function checkCurrentRoute()
    {
        foreach ($this->noCsrfValidationRoutes as $route) {
            if (($pos = strpos($route, '*')) !== false) {
                $route = substr($route, 0, $pos - 1);
                if (strpos($this->getPathInfo(), $route) === 0) {
                    return false;
                }
            } elseif ($this->getPathInfo() === $route) {
                return false;
            }
        }
        return true;
    }
}
