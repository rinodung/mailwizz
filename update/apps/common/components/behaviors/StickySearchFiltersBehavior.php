<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * StickySearchFiltersBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.4
 */

/**
 * @property ActiveRecord $owner
 */
class StickySearchFiltersBehavior extends CActiveRecordBehavior
{
    /**
     * @return $this
     * @throws CException
     */
    public function setStickySearchFilters(): self
    {
        if (is_cli()) {
            return $this;
        }

        $appName           = apps()->getCurrentAppName();
        $session           = session();
        $sessionKey        = sha1('search_' . $appName . '_' . get_class($this) . '_' . get_class($this->owner));
        $sessionAttributes = $session->get($sessionKey);
        $sessionAttributes = is_array($sessionAttributes) ? $sessionAttributes : [];

        $attributes = (array)request()->getQuery($this->owner->getModelName(), []);


        $this->owner->attributes = CMap::mergeArray($sessionAttributes, $attributes);
        $session->add($sessionKey, $this->owner->attributes);

        return $this;
    }
}
