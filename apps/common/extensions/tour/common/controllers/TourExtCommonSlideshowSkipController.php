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

class TourExtCommonSlideshowSkipController extends ExtensionController
{
    /**
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $appName = apps()->getCurrentAppName();
        $id      = null;

        if ($appName == TourSlideshow::APPLICATION_BACKEND) {
            $id = user()->getId();
        } elseif ($appName == TourSlideshow::APPLICATION_CUSTOMER) {
            $id = customer()->getId();
        }

        if (empty($id)) {
            $this->renderJson();
        }

        $criteria = new CDbCriteria();
        $criteria->compare('slideshow_id', (int)request()->getPost('slideshow'));
        $criteria->compare('application', $appName);
        $criteria->compare('status', TourSlideshow::STATUS_ACTIVE);

        /** @var TourSlideshow|null $slideshow */
        $slideshow = TourSlideshow::model()->find($criteria);

        if (empty($slideshow)) {
            $this->renderJson();
            return;
        }

        $this->getExtension()->setOption('views.' . $appName . '.' . $id . '.viewed', $slideshow->slideshow_id);

        $this->renderJson();
    }
}
