<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * JsonResponse
 *
 * This class is inspired a bit from Sympfony's HttpFoundation package.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class JsonResponse extends Response
{
    /**
     * @var CMap $_data
     */
    private $_data;

    /**
     * @var string $_callback - the callback for jsonp, if any
     */
    private $_callback;

    /**
     * @var bool $_prettyPrint - if the json should be prety printed
     */
    private $_prettyPrint = false;

    /**
     * @param CMap|array $data
     *
     * @return $this
     * @throws CException
     */
    public function setData($data = [])
    {
        if (!($this->_data instanceof CMap)) {
            $this->_data = new CMap($data);
        } else {
            $this->_data->mergeWith($data);
        }
        return $this;
    }

    /**
     * @return CMap
     * @throws CException
     */
    public function getData()
    {
        if (!($this->_data) instanceof CMap) {
            $this->_data = new CMap();
        }
        return $this->_data;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return void
     * @throws CException
     */
    public function addData($key, $value)
    {
        $this->getData()->add($key, $value);
    }

    /**
     * @param string $key
     *
     * @return mixed
     * @throws CException
     */
    public function removeData($key)
    {
        return $this->getData()->remove($key);
    }

    /**
     * @param mixed $callback
     *
     * @return $this
     */
    public function setCallback($callback = null)
    {
        if ($callback !== null) {
            // taken from http://www.geekality.net/2011/08/03/valid-javascript-identifier/
            $pattern = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';
            $parts = explode('.', (string)$callback);
            foreach ($parts as $part) {
                if (!preg_match($pattern, $part)) {
                    throw new InvalidArgumentException('The callback name is not valid.');
                }
            }
        }

        $this->_callback = (string)$callback;

        return $this;
    }

    /**
     * @return string
     */
    public function getCallback()
    {
        return $this->_callback;
    }

    /**
     * @param bool $bool
     *
     * @return $this
     */
    public function setPrettyPrint($bool)
    {
        $this->_prettyPrint = $bool;
        return $this;
    }

    /**
     * @return bool
     */
    public function getPrettyPrint()
    {
        return $this->_prettyPrint;
    }

    /**
     * @return Response
     * @throws CException
     */
    public function send()
    {
        $callback = $this->getCallback();
        if (empty($callback)) {
            $this->getHeaders()->add('Content-Type', 'application/json');
            $json = (string)json_encode($this->getData()->toArray());
            if ($this->getPrettyPrint()) {
                $json = $this->pretty($json);
            }
            $this->setContent((string)$json);
        } else {
            $this->getHeaders()->add('Content-Type', 'text/javascript');
            $this->setContent(sprintf('%s(%s);', $this->getCallback(), json_encode($this->getData()->toArray())));
        }

        return parent::send();
    }

    /**
     * Indents a flat JSON string to make it more human-readable.
     *
     * @param string $json
     *
     * @return string Indented version of the original JSON string.
     * @author http://www.daveperrett.com/articles/2008/03/11/format-json-with-php/
     * @link http://www.daveperrett.com/articles/2008/03/11/format-json-with-php/
     *
     */
    public function pretty($json)
    {
        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = '  ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i=0; $i<=$strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

            // If this character is the end of an element,
            // output a new line and indent the next line.
            } elseif (($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos--;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
    }
}
