<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ModelMetaDataBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property ActiveRecord $owner
 */
class ModelMetaDataBehavior extends CActiveRecordBehavior
{
    /**
     * @var CMap|null
     */
    private $_modelMetaData;

    /**
     * @return CMap
     * @throws CException
     */
    public function getModelMetaData(): CMap
    {
        if (empty($this->_modelMetaData) || !($this->_modelMetaData instanceof CMap)) {
            $this->_modelMetaData = new CMap();
        }

        if ($this->owner instanceof ActiveRecord && $this->owner->hasAttribute('meta_data') && !empty($this->owner->meta_data) && $this->_modelMetaData->getCount() == 0) {
            $this->_modelMetaData->mergeWith((array)(unserialize($this->owner->meta_data)));
        }

        return $this->_modelMetaData;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return ModelMetaDataBehavior
     * @throws CException
     */
    public function setModelMetaData($key, $value): self
    {
        $this->getModelMetaData()->add($key, $value);
        return $this;
    }

    /**
     * @return ModelMetaDataBehavior
     * @throws CDbException
     * @throws CException
     */
    public function saveModelMetaData(): self
    {
        if ($this->owner instanceof ActiveRecord && $this->owner->hasAttribute('meta_data')) {
            $metaData = @serialize($this->getModelMetaData()->toArray());
            $this->owner->setAttribute('meta_data', $metaData);
            $this->owner->saveAttributes([
                'meta_data' => $metaData,
            ]);
        }
        return $this;
    }

    /**
     * @param CModelEvent $event
     *
     * @return void
     * @throws CException
     */
    public function beforeSave($event)
    {
        if ($this->owner instanceof ActiveRecord && $this->owner->hasAttribute('meta_data')) {
            $this->owner->setAttribute('meta_data', @serialize($this->getModelMetaData()->toArray()));
        }
    }
}
