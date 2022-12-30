<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ThemeController
 *
 * Handles the actions for themes related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class ThemeController extends Controller
{
    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete', // we only allow deletion via POST request
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all available themes in selected application
     *
     * @param string $app
     *
     * @return void
     * @throws CHttpException
     */
    public function actionIndex($app = 'backend')
    {
        $this->checkAppName($app);

        $model = new ThemeHandlerForm('upload');

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('themes', 'View themes'),
            'pageHeading'     => t('themes', 'View themes'),
            'pageBreadcrumbs' => [
                t('themes', 'Themes') => createUrl('theme/index'),
                t('app', 'View all'),
            ],
        ]);

        $apps = $this->getAllowedApps();
        $this->render('index', compact('model', 'apps', 'app'));
    }

    /**
     * Settings page for theme
     *
     * @param string $app
     * @param string $theme
     *
     * @return void
     * @throws CHttpException
     */
    public function actionSettings($app, $theme)
    {
        $this->checkAppName($app);

        /** @var CWebApplication $appInstance */
        $appInstance = app();

        /** @var ThemeManager $themeManager */
        $themeManager = $appInstance->getThemeManager();

        /** @var ThemeInit|null $themeInstance */
        $themeInstance = $themeManager->getThemeInstance($theme, $app);

        if (empty($themeInstance)) {
            throw new CHttpException(400, t('app', 'Invalid request. Please do not repeat this request again.'));
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('themes', 'Theme settings'),
            'pageHeading'     => t('themes', 'Theme settings'),
            'pageBreadcrumbs' => [
                t('themes', 'Themes') => createUrl('theme/index'),
                t('app', 'Settings'),
            ],
        ]);

        $themeInstance->settingsPage();
    }

    /**
     * Upload a new themes
     *
     * @param string $app
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpload($app)
    {
        $this->checkAppName($app);

        $model = new ThemeHandlerForm('upload');

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->archive = CUploadedFile::getInstance($model, 'archive');
            if (!$model->upload($app)) {
                notify()->addError($model->shortErrors->getAllAsString());
            } else {
                notify()->addSuccess(t('themes', 'Your theme has been successfully uploaded!'));
            }
            $this->redirect(['theme/index', 'app' => $app]);
        }

        notify()->addError(t('themes', 'Please select a theme archive for upload!'));
        $this->redirect(['theme/index']);
    }

    /**
     * Enable theme
     *
     * @param string $app
     * @param string $name
     *
     * @return void
     * @throws CHttpException
     */
    public function actionEnable($app, $name)
    {
        $this->checkAppName($app);

        /** @var CWebApplication $appInstance */
        $appInstance = app();

        /** @var ThemeManager $themeManager */
        $themeManager = $appInstance->getThemeManager();

        if (!$themeManager->enableTheme($name, $app)) {
            notify()->clearAll()->addError($themeManager->getErrors());
        } else {
            /** @var ThemeInit $theme */
            $theme = $themeManager->getThemeInstance($name, $app);
            $message = t('themes', 'The theme "{name}" has been successfully enabled!', [
                '{name}' => html_encode((string)$theme->name),
            ]);
            notify()->clearAll()->addSuccess($message);
        }

        $this->redirect(['theme/index', 'app' => $app]);
    }

    /**
     * Disable theme
     *
     * @param string $app
     * @param string $name
     *
     * @return void
     * @throws CHttpException
     */
    public function actionDisable($app, $name)
    {
        $this->checkAppName($app);

        /** @var CWebApplication $appInstance */
        $appInstance = app();

        /** @var ThemeManager $themeManager */
        $themeManager = $appInstance->getThemeManager();

        if (!$themeManager->disableTheme($name, $app)) {
            notify()->clearAll()->addError($themeManager->getErrors());
        } else {
            /** @var ThemeInit $theme */
            $theme = $themeManager->getThemeInstance($name, $app);
            $message = t('themes', 'The theme "{name}" has been successfully disabled!', [
                '{name}' => html_encode((string)$theme->name),
            ]);
            notify()->clearAll()->addSuccess($message);
        }

        $this->redirect(['theme/index', 'app' => $app]);
    }

    /**
     * Delete theme
     *
     * @param string $app
     * @param string $name
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     * @throws ReflectionException
     */
    public function actionDelete($app, $name)
    {
        $this->checkAppName($app);

        /** @var CWebApplication $appInstance */
        $appInstance = app();

        /** @var ThemeManager $themeManager */
        $themeManager = $appInstance->getThemeManager();

        if (!$themeManager->deleteTheme($name, $app)) {
            notify()->clearAll()->addError($themeManager->getErrors());
        } else {
            /** @var ThemeInit $theme */
            $theme   = $themeManager->getThemeInstance($name, $app);
            $message = t('themes', 'The theme "{name}" has been successfully deleted!', [
                '{name}' => html_encode((string)$theme->name),
            ]);
            notify()->clearAll()->addSuccess($message);
        }

        $redirect = null;
        if (!request()->getIsAjaxRequest()) {
            $redirect = ['theme/index', 'app' => $app];
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * @return array
     */
    public function getAllowedApps(): array
    {
        static $allowedApps;
        if ($allowedApps) {
            return $allowedApps;
        }

        $allowedApps = apps()->getWebApps();

        if (($index = array_search('api', $allowedApps)) !== false) {
            unset($allowedApps[$index]);
        }

        sort($allowedApps);

        return $allowedApps;
    }

    /**
     * @param string $appName
     *
     * @return void
     * @throws CHttpException
     */
    public function checkAppName(string $appName)
    {
        $allowedApps = $this->getAllowedApps();
        if (!in_array($appName, $allowedApps)) {
            throw new CHttpException(400, t('app', 'Invalid request. Please do not repeat this request again.'));
        }
    }
}
