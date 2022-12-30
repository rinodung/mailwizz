<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CkeditorExtCommonCkeditorController
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class CkeditorExtCommonCkeditorController extends ExtensionController
{
    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->getExtension()->getPathOfAlias('common.views.ckeditor');
    }

    /**
     * Action for settings, only admin users can access it.
     *
     * @return void
     * @throws CHttpException
     */
    public function actionSettings()
    {
        if (!user()->getId()) {
            throw new CHttpException(403, t('app', 'Invalid request. Please do not repeat this request again.'));
        }

        /** @var CkeditorExtCommon $model */
        $model = container()->get(CkeditorExtCommon::class);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;
            if ($model->save()) {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('CKeditor options'),
            'pageHeading'     => $this->t('CKeditor options'),
            'pageBreadcrumbs' => [
                t('extensions', 'Extensions') => createUrl('extensions/index'),
                $this->t('CKeditor options'),
            ],
        ]);

        $this->render('settings', compact('model'));
    }

    /**
     * Render the file manager
     * Customers and admin users are allowed to access it.
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionFilemanager()
    {
        /** @var CkeditorExt $extension */
        $extension = $this->getExtension();
        $canAccess = false;

        /** @var CkeditorExtCommon $model */
        $model = container()->get(CkeditorExtCommon::class);

        if ($extension->isAppName('backend') && $model->getIsFilemanagerEnabledForUser() && user()->getId() > 0) {
            $canAccess = true;
        } elseif ($extension->isAppName('customer') && $model->getIsFilemanagerEnabledForCustomer() && customer()->getId() > 0) {
            $canAccess = true;
        }

        if (!$canAccess) {
            throw new CHttpException(403, t('app', 'Invalid request. Please do not repeat this request again.'));
        }

        $assetsUrl    = $extension->getAssetsUrl();
        $language     = $this->getElFinderLanguage();
        $connectorUrl = $extension->createUrl('ckeditor/filemanager_connector');
        $themeInfo    = $extension->getFilemanagerTheme($model->getFilemanagerTheme());
        $theme        = !empty($themeInfo['url']) ? $themeInfo['url'] : '';

        $this->setData([
            'pageMetaTitle' => $this->getData('pageMetaTitle') . ' | ' . $this->t('File manager'),
        ]);

        $this->renderPartial('elfinder', compact('assetsUrl', 'language', 'connectorUrl', 'theme'));
    }

    /**
     * Render the file manager in the application layout
     * Customers and admin users are allowed to access it.
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionFm()
    {
        /** @var CkeditorExt $extension */
        $extension = $this->getExtension();
        $canAccess = false;

        /** @var CkeditorExtCommon $model */
        $model = container()->get(CkeditorExtCommon::class);

        if ($extension->isAppName('backend') && $model->getIsFilemanagerEnabledForUser() && user()->getId() > 0) {
            $canAccess = true;
        } elseif ($extension->isAppName('customer') && $model->getIsFilemanagerEnabledForCustomer() && customer()->getId() > 0) {
            $canAccess = true;
        }

        if (!$canAccess) {
            throw new CHttpException(403, t('app', 'Invalid request. Please do not repeat this request again.'));
        }

        $fileManagerUrl = $extension->getFilemanagerUrl();

        $this->setData([
            'pageMetaTitle'     => $this->getData('pageMetaTitle') . ' | ' . $this->t('File manager'),
            'pageHeading'       => $this->t('File manager'),
            'pageBreadcrumbs'   => [
                $this->t('File manager'),
            ],
        ]);

        $this->render('fm', compact('fileManagerUrl'));
    }

    /**
     * Connector action.
     * Customers and admin users are allowed to access it.
     *
     * @return void
     * @throws CHttpException
     */
    public function actionFilemanager_connector()
    {
        /** @var CkeditorExt $extension */
        $extension = $this->getExtension();
        $canAccess = false;

        /** @var CkeditorExtCommon $model */
        $model = container()->get(CkeditorExtCommon::class);

        $filesPath   = $filesUrl = '';
        $uploadAllow = ['image'];
        $uploadDeny  = ['all'];
        $disabled    = ['archive', 'extract', 'mkfile', 'rename', 'put', 'netmount', 'callback', 'chmod', 'download'];

        if ($extension->isAppName('backend') && $model->getIsFilemanagerEnabledForUser() && user()->getId() > 0) {

            // this is a user requesting files.
            $canAccess = true;
            $filesPath = (string)Yii::getPathOfAlias('root.frontend.assets.files');
            $filesUrl  = apps()->getAppUrl('frontend', 'frontend/assets/files', true, true);
        } elseif ($extension->isAppName('customer') && $model->getIsFilemanagerEnabledForCustomer() && customer()->getId() > 0) {

            /** @var Customer $customer */
            $customer = customer()->getModel();

            // this is a customer requesting files.
            $customerFolderName = $customer->customer_uid;

            $canAccess = true;
            $filesPath = (string)Yii::getPathOfAlias('root.frontend.assets.files');
            $filesUrl  = apps()->getAppUrl('frontend', 'frontend/assets/files/customer/' . $customerFolderName, true, true);

            $filesPath .= '/customer';
            if (!file_exists($filesPath) || !is_dir($filesPath)) {
                mkdir($filesPath, 0777, true);
            }
            $filesPath .= '/' . $customerFolderName;
            if (!file_exists($filesPath) || !is_dir($filesPath)) {
                mkdir($filesPath, 0777, true);
            }
        }

        // no user or customer? deny access!
        if (!$canAccess) {
            throw new CHttpException(403, t('app', 'Invalid request. Please do not repeat this request again.'));
        }

        $fileNameNoChars = ['\\', '/', ':', '*', '?', '"', '<', '>', '|', ' '];

        $elfinderOpts = [
            'debug' => false,
            'bind'  => [
                'mkdir.pre mkfile.pre rename.pre' => [
                    'Plugin.Sanitizer.cmdPreprocess',
                ],
                'upload.presave' => [
                    'Plugin.Sanitizer.onUpLoadPreSave',
                ],
            ],
            'plugin' => [
                'Sanitizer' => [
                    'enable' => true,
                    'targets'  => $fileNameNoChars,
                    'replace'  => '-',
                ],
            ],
            'roots' => [
                [
                    'driver'            => 'LocalFileSystem',
                    'path'              => $filesPath . '/',
                    'URL'               => $filesUrl . '/',
                    'alias'             => t('app', 'Home'),
                    'uploadAllow'       => $uploadAllow,
                    'uploadDeny'        => $uploadDeny,
                    'uploadOverwrite'   => false, // 1.6.6
                    'disabled'          => $disabled,

                    'dateFormat'    => app()->getLocale()->getDateFormat(),
                    'timeFormat'    => app()->getLocale()->getTimeFormat(),
                    'attributes'    => [
                        // hide .tmb and .quarantine folders
                        [
                            'pattern'   => '/.(tmb|quarantine)/i',
                            'read'      => false,
                            'write'     => false,
                            'hidden'    => true,
                            'locked'    => false,
                        ],
                    ],

                    'plugin' => [
                        'Sanitizer' => [
                            'enable'   => true,
                            'targets'  => $fileNameNoChars,
                            'replace'  => '-',
                        ],
                    ],
                ],
            ],
        ];

        // since 1.3.5.9
        $elfinderOpts = (array)hooks()->applyFilters('ext_ckeditor_el_finder_options', $elfinderOpts);

        // run elFinder
        $connector = new elFinderConnector(new elFinder($elfinderOpts));
        $connector->run();
    }

    /**
     * @return string
     */
    protected function getElFinderLanguage(): string
    {
        $extension    = $this->getExtension();
        $language     = app()->getLanguage();
        $languageFile = null;
        $assetsPath   = $extension->getPathOfAlias('assets');

        if (strpos($language, '_') !== false) {
            $languageParts = explode('_', $language);
            $languageParts[1] = strtoupper((string)$languageParts[1]);
            $language = implode('_', $languageParts);
        }

        if (is_file($assetsPath . '/elfinder/js/i18n/elfinder.' . $language . '.js')) {
            return $language;
        }

        if (strpos($language, '_') !== false) {
            $languageParts = explode('_', $language);
            $language = $languageParts[0];
            if (is_file($assetsPath . '/elfinder/js/i18n/elfinder.' . $language . '.js')) {
                return $language;
            }
        }

        return '';
    }
}
