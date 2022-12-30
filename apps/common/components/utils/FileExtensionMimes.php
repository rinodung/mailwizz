<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * FileExtensionMimes
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.2
 */

class FileExtensionMimes extends CApplicationComponent
{
    /**
     * @var string
     */
    public $alias = '%s.config.mimes';

    /**
     * @var CMap
     */
    protected $_mimesMapping;

    /**
     * @var Mimey\MimeTypes
     */
    protected $_mimes;

    /**
     * @param mixed $extension
     *
     * @return CMap
     * @throws CException
     */
    public function get($extension): CMap
    {
        if (!is_array($extension)) {
            $extension = [$extension];
        }

        $mimes = [];
        foreach ($extension as $ext) {
            $mimes = CMap::mergeArray($mimes, $this->getMimesManager()->getAllMimeTypes($ext));
        }
        return new CMap(array_unique($mimes));
    }

    /**
     * @return CMap
     * @throws CException
     */
    public function getMimesMap(): CMap
    {
        if ($this->_mimesMapping !== null) {
            return $this->_mimesMapping;
        }

        $fileData = new CMap((array)require((string)Yii::getPathOfAlias(sprintf($this->alias, 'common')) . '.php'));
        if (is_file($customFile = (string)Yii::getPathOfAlias(sprintf($this->alias, 'common') . '-custom') . '.php')) {
            $fileData->mergeWith((array)require($customFile));
        }

        if (defined('MW_APP_NAME') && is_file($customFile = (string)Yii::getPathOfAlias(sprintf($this->alias, MW_APP_NAME)) . '.php')) {
            $fileData->mergeWith((array)require($customFile));
        }

        if (defined('MW_APP_NAME') && is_file($customFile = (string)Yii::getPathOfAlias(sprintf($this->alias, MW_APP_NAME) . '-custom') . '.php')) {
            $fileData->mergeWith((array)require($customFile));
        }

        try {
            $dirName = dirname((string)(new ReflectionClass(Mimey\MimeMappingBuilder::class))->getFileName(), 2);
            if (is_file($customFile = $dirName . '/mime.types.php')) {
                $fileData->mergeWith((array)(require $customFile)['mimes']);
            }
        } catch (Exception $e) {
        }

        /** @var CMap $fileData */
        $fileData = hooks()->applyFilters('file_extensions_mimes_map', $fileData);

        return $this->_mimesMapping = $fileData;
    }

    /**
     * @return Mimey\MimeTypes
     * @throws CException
     */
    protected function getMimesManager(): Mimey\MimeTypes
    {
        if ($this->_mimes !== null) {
            return $this->_mimes;
        }

        $cacheFile  = (string)Yii::getPathOfAlias('common.runtime') . '/mime.types.compiled.php';
        $saveCache  = true;

        if (is_file($cacheFile)) {
            $saveCache = filemtime($cacheFile) + 3600 < time();
        }

        if (!$saveCache) {
            // if the cache file exists and we should not save it again, then load from cache
            $builder = Mimey\MimeMappingBuilder::load($cacheFile);
        } else {
            // the cache file either does not exists or it does but it is too old and it will be cached anyway
            $builder = Mimey\MimeMappingBuilder::create();
        }

        foreach ($this->getMimesMap()->toArray() as $ext => $mimes) {
            foreach ($mimes as $mime) {
                $builder->add($mime, $ext);
            }
        }

        if ($saveCache) {
            $builder->save($cacheFile);
        }

        return $this->_mimes = new Mimey\MimeTypes($builder->getMapping());
    }
}
