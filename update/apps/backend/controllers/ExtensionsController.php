<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ExtensionsController
 *
 * Handles the actions for extensions related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class ExtensionsController extends Controller
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
     * List all available extensions
     *
     * @return void
     */
    public function actionIndex()
    {
        $model = new ExtensionHandlerForm('upload');

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('extensions', 'View extensions'),
            'pageHeading'     => t('extensions', 'View extensions'),
            'pageBreadcrumbs' => [
                t('extensions', 'Extensions') => createUrl('extensions/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('index', compact('model'));
    }

    /**
     * Upload a new extensions
     *
     * @return void
     */
    public function actionUpload()
    {
        $model = new ExtensionHandlerForm('upload');

        if (request()->getIsPostRequest() && request()->getPost($model->getModelName())) {
            $model->archive = CUploadedFile::getInstance($model, 'archive');
            if (!$model->upload()) {
                notify()->addError($model->shortErrors->getAllAsString());
            } else {
                notify()->addSuccess(t('extensions', 'Your extension has been successfully uploaded!'));
            }
            $this->redirect(['extensions/index']);
        }

        notify()->addError(t('extensions', 'Please select an extension archive for upload!'));
        $this->redirect(['extensions/index']);
    }

    /**
     * Enable extension
     *
     * @param string $id
     *
     * @return void
     * @throws CException
     */
    public function actionEnable($id)
    {
        notify()->clearAll();

        /** @var ExtensionInit|null $extension */
        $extension = extensionsManager()->getExtensionInstance($id);
        if (empty($extension)) {
            throw new CHttpException(404, t('app', 'Page not found.'));
        }

        if (!extensionsManager()->enableExtension($id)) {
            notify()->addError(extensionsManager()->getErrors());
        } else {
            $message = t('extensions', 'The extension "{name}" has been successfully enabled!', [
                '{name}' => html_encode((string)$extension->name),
            ]);
            notify()->addSuccess($message);
        }

        // since 1.5.3
        hooks()->doAction('backend_controller_extensions_action_enable', $collection = new CAttributeCollection([
            'controller'  => $this,
            'extension'   => $extension,
            'redirect'    => ['extensions/index'],
            'success'     => notify()->getHasSuccess(),
        ]));

        $this->redirect($collection->itemAt('redirect'));
    }

    /**
     * Disable extension
     *
     * @param string $id
     *
     * @return void
     * @throws CException
     */
    public function actionDisable($id)
    {
        notify()->clearAll();

        /** @var ExtensionInit|null $extension */
        $extension = extensionsManager()->getExtensionInstance($id);
        if (empty($extension)) {
            throw new CHttpException(404, t('app', 'Page not found.'));
        }

        if (!extensionsManager()->disableExtension($id)) {
            notify()->addError(extensionsManager()->getErrors());
        } else {
            $message = t('extensions', 'The extension "{name}" has been successfully disabled!', [
                '{name}' => html_encode((string)$extension->name),
            ]);
            notify()->addSuccess($message);
        }

        // since 1.5.3
        hooks()->doAction('backend_controller_extensions_action_disable', $collection = new CAttributeCollection([
            'controller'  => $this,
            'extension'   => $extension,
            'redirect'    => ['extensions/index'],
            'success'     => notify()->getHasSuccess(),
        ]));

        $this->redirect($collection->itemAt('redirect'));
    }

    /**
     * Update extension
     *
     * @param string $id
     *
     * @return void
     * @throws CException
     */
    public function actionUpdate($id)
    {
        notify()->clearAll();

        /** @var ExtensionInit|null $extension */
        $extension = extensionsManager()->getExtensionInstance($id);
        if (empty($extension)) {
            throw new CHttpException(404, t('app', 'Page not found.'));
        }

        if (!extensionsManager()->updateExtension($id)) {
            notify()->addError(extensionsManager()->getErrors());
        } else {
            $message = t('extensions', 'The extension "{name}" has been successfully updated!', [
                '{name}' => html_encode((string)$extension->name),
            ]);
            notify()->addSuccess($message);
        }

        // since 1.5.3
        hooks()->doAction('backend_controller_extensions_action_update', $collection = new CAttributeCollection([
            'controller'  => $this,
            'extension'   => $extension,
            'redirect'    => ['extensions/index'],
            'success'     => notify()->getHasSuccess(),
        ]));

        $this->redirect($collection->itemAt('redirect'));
    }

    /**
     * Delete extension
     *
     * @param string $id
     *
     * @return void
     * @throws CException
     */
    public function actionDelete($id)
    {
        /** @var ExtensionInit|null $extension */
        $extension = extensionsManager()->getExtensionInstance($id);
        if (empty($extension)) {
            throw new CHttpException(404, t('app', 'Page not found.'));
        }

        if (!extensionsManager()->deleteExtension($id)) {
            notify()->addError(extensionsManager()->getErrors());
        } else {
            $message = t('extensions', 'The extension "{name}" has been successfully deleted!', [
                '{name}' => html_encode((string)$extension->name),
            ]);
            notify()->addSuccess($message);
        }

        $redirect = null;
        if (!request()->getIsAjaxRequest()) {
            $redirect = ['extensions/index'];
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
}
