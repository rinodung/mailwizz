<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SearchExtSearchItem
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class SearchExtSearchItem extends FormModel
{
    /**
     * @var string
     */
    public $title = '';

    /**
     * @var string
     */
    public $url = '';

    /**
     * @var string
     */
    public $route = '';

    /**
     * @var array
     */
    public $keywords = [];

    /**
     * @var array
     */
    public $children = [];

    /**
     * @var int
     */
    public $score = 0;

    /**
     * @var callable|null
     */
    public $skip;

    /**
     * @var array
     */
    public $buttons = [];

    /**
     * @var callable|null
     */
    public $childrenGenerator;

    /**
     * @var callable|null
     */
    public $keywordsGenerator;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['title, url, route, skip, keywords, buttons, keywordsGenerator, childrenGenerator', 'safe'],
        ];
    }

    /**
     * @param array $newAttributes
     *
     * @return $this
     */
    public function mergeAttributes(array $newAttributes = []): self
    {
        if (empty($newAttributes)) {
            return $this;
        }
        $this->setAttributes(CMap::mergeArray($this->attributes, $newAttributes));
        return $this;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return [
            'title'     => $this->title,
            'url'       => $this->url,
            'score'     => $this->score,
            'keywords'  => $this->keywords,
            'buttons'   => $this->buttons,
            'children'  => $this->children,
        ];
    }
}
