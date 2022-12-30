<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * IOFilter
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

use voku\helper\AntiXSS;

class IOFilter extends CApplicationComponent
{
    /**
     * @var MHtmlPurifier
     */
    private $_purifier;

    /**
     * @var AntiXSS
     */
    private $_antiXSS;

    /**
     * @param mixed $content
     *
     * @return mixed
     */
    public function encode($content)
    {
        if (is_array($content)) {
            $content = array_map([$this, 'encode'], $content);
        } else {
            $content = html_encode((string)$this->decode($content));
        }
        return $content;
    }

    /**
     * @param mixed $content
     *
     * @return mixed
     */
    public function decode($content)
    {
        if (is_array($content)) {
            $content = array_map([$this, 'decode'], $content);
        } else {
            $content = html_decode((string)$content);
        }
        return $content;
    }

    /**
     * @param mixed $content
     *
     * @return mixed
     */
    public function stripClean($content)
    {
        return $this->stripTags($this->xssClean($this->stripTags($this->trim($content))));
    }

    /**
     * @param mixed $content
     *
     * @return mixed
     */
    public function stripPurify($content)
    {
        return $this->stripTags($this->purify($this->stripTags($this->trim($content))));
    }

    /**
     * @param mixed $content
     *
     * @return mixed
     */
    public function stripTags($content)
    {
        if (is_array($content)) {
            $content = array_map([$this, 'stripTags'], $content);
        } else {
            // since 1.4.0
            if (!$this->mustFilter($content)) {
                return $content;
            }
            $content = $this->decode($content); // 1.3.8.8
            $content = strip_tags((string)$content);
        }
        return $content;
    }

    /**
     * @param mixed $content
     *
     * @return mixed
     */
    public function xssClean($content)
    {
        if (is_array($content)) {
            $content = array_map([$this, 'xssClean'], $content);
        } else {
            // since 1.4.0
            if (!$this->mustFilter($content)) {
                return $content;
            }
            $content = $this->getAntiXSS()->xss_clean($content);
        }

        return $content;
    }

    /**
     * @param mixed $content
     *
     * @return mixed
     */
    public function purify($content)
    {
        if (is_array($content)) {
            $content = array_map([$this, 'purify'], $content);
        } else {
            // since 1.4.0
            if (!$this->mustFilter($content)) {
                return $content;
            }
            $content = $this->getPurifier()->purify($content);
        }
        return $content;
    }

    /**
     * @return MHtmlPurifier
     */
    public function getPurifier(): MHtmlPurifier
    {
        if ($this->_purifier === null) {
            return $this->_purifier = new MHtmlPurifier();
        }
        return $this->_purifier;
    }

    /**
     * @return AntiXSS
     */
    public function getAntiXSS(): AntiXSS
    {
        if ($this->_antiXSS === null) {
            $this->_antiXSS = new AntiXSS();
        }
        return $this->_antiXSS;
    }

    /**
     * @param mixed $content
     *
     * @return mixed
     */
    public function trim($content)
    {
        if (is_array($content)) {
            $content = array_map([$this, 'trim'], $content);
        } else {
            // since 1.4.0
            if (!$this->mustFilter($content)) {
                return $content;
            }
            $content = trim((string)$content);
        }
        return $content;
    }

    /**
     * This exists to keep the variable type unchanged between the filters if
     * the variable value is not a possible issue.
     * We need this since we have places where we do a strict comparison, i.e:
     * ioFilter->stripClean(true) === true
     * which otherwise would return false
     *
     * @param mixed $content
     * @return bool
     */
    public function mustFilter($content)
    {
        if (empty($content) || is_bool($content) || is_numeric($content)) {
            return false;
        }

        return true;
    }

    /**
     * @return void
     * @throws CException
     */
    public function cleanGlobals()
    {
        if (request()->globalsCleaned) {
            return;
        }

        app_param_set([
            'POST'      => new CMap($_POST),
            'GET'       => new CMap($_GET),
            'COOKIE'    => new CMap($_COOKIE),
            'REQUEST'   => new CMap($_REQUEST),
            'SERVER'    => new CMap($_SERVER),
        ]);

        $_POST      = $this->stripPurify($_POST);
        $_GET       = $this->stripClean($_GET);
        $_COOKIE    = $this->stripClean($_COOKIE);
        $_REQUEST   = $this->stripClean($_REQUEST);
        $_SERVER    = $this->stripClean($_SERVER);

        request()->globalsCleaned = true;
    }
}
