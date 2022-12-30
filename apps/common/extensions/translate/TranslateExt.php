<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Translate Extension
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class TranslateExt extends ExtensionInit
{
    /**
     * @var string
     */
    public $name = 'Translate';

    /**
     * @var string
     */
    public $description = 'Will create and update translation files for your language.';

    /**
     * @var string
     */
    public $version = '2.0.0';

    /**
     * @var string
     */
    public $minAppVersion = '2.0.0';

    /**
     * @var string
     */
    public $author = 'MailWizz Development Team';

    /**
     * @var string
     */
    public $website = 'https://www.mailwizz.com/';

    /**
     * @var string
     */
    public $email = 'support@mailwizz.com';

    /**
     * @var bool
     */
    public $cliEnabled = true;

    /**
     * @var array
     */
    public $allowedApps = ['*'];

    /**
     * @var int
     */
    public $priority = -1000;

    /**
     * @var bool
     */
    protected $_canBeDeleted = false;

    /**
     * @var bool
     */
    protected $_canBeDisabled = true;

    /**
     * @inheritDoc
     */
    public function run()
    {
        $this->importClasses('common.models.*');

        // register the common model in container for singleton access
        container()->add(TranslateExtModel::class, TranslateExtModel::class);

        /** @var TranslateExtModel $model */
        $model = container()->get(TranslateExtModel::class);

        if ($this->isAppName('backend')) {
            $this->addUrlRules([
                ['settings/index', 'pattern' => 'extensions/translate/settings'],
                ['settings/<action>', 'pattern' => 'extensions/translate/settings/*'],
            ]);

            $this->addControllerMap([
                'settings' => [
                    'class' => 'backend.controllers.TranslateExtBackendSettingsController',
                ],
            ]);
        }

        // run the worker only if the user specifically enabled it.
        if ($model->getIsEnabled()) {
            $this->checkAndEnableTranslations();
        }
    }

    /**
     * Add the landing page for this extension (settings/general info/etc)
     *
     * @return string
     */
    public function getPageUrl()
    {
        return $this->createUrl('settings/index');
    }

    /**
     * @param CMissingTranslationEvent $event
     *
     * @return void
     */
    public function _handleMissingTranslationsFile(CMissingTranslationEvent $event)
    {
        $sender = $event->sender;
        if (!($sender instanceof CPhpMessageSource)) {
            return;
        }

        /** @var TranslateExtModel $model */
        $model = container()->get(TranslateExtModel::class);

        // do not translate extensions.
        if (
            !$model->getTranslateExtensions() &&
            (stripos($event->category, 'ext_') !== false || stripos($event->category, '_ext') !== false)
        ) {
            return;
        }

        static $checkedFiles = [];

        $languageDir  = $sender->basePath . '/' . $event->language;
        $languageFile = $languageDir . '/' . $event->category . '.php';

        if (isset($checkedFiles[$languageFile]) && !is_array($checkedFiles[$languageFile])) {
            return;
        }

        if (!isset($checkedFiles[$languageFile])) {
            if (!file_exists($languageDir) || !is_dir($languageDir)) {
                if (!is_writable($sender->basePath) && !chmod($sender->basePath, 0777)) {
                    $checkedFiles[$languageFile] = false;
                    return;
                }
                if (!mkdir($languageDir, 0777, true)) {
                    $checkedFiles[$languageFile] = false;
                    return;
                }
            }

            if (!is_file($languageFile) && (!touch($languageFile) || !chmod($languageFile, 0666))) {
                $checkedFiles[$languageFile] = false;
                return;
            }

            $checkedFiles[$languageFile] = [];
        }

        if (empty($checkedFiles[$languageFile])) {
            $checkedFiles[$languageFile] = require $languageFile;
        }

        if (!is_array($checkedFiles[$languageFile])) {
            $checkedFiles[$languageFile] = [];
        }

        $checkedFiles[$languageFile][$event->message] = $event->message;

        static $stub;
        if (empty($stub)) {
            $stub = file_get_contents(dirname(__FILE__) . '/common/stub.php');
        }

        $newStub = str_replace('[[category]]', $event->category, $stub);
        $newStub .= 'return ' . var_export($checkedFiles[$languageFile], true) . ';' . "\n";
        file_put_contents($languageFile, $newStub);
    }

    /**
     * @param CMissingTranslationEvent $event
     *
     * @return void
     */
    public function _handleMissingTranslationsDatabase(CMissingTranslationEvent $event)
    {
        $sender = $event->sender;
        if (!($sender instanceof CDbMessageSource)) {
            return;
        }

        /** @var TranslateExtModel $model */
        $model = container()->get(TranslateExtModel::class);

        // do not translate extensions.
        if (
            !$model->getTranslateExtensions() &&
            (stripos($event->category, 'ext_') !== false || stripos($event->category, '_ext') !== false)
        ) {
            return;
        }

        $source = TranslationSourceMessage::model()->findByAttributes([
            'category'  => $event->category,
            'message'   => $event->message,
        ]);

        if (!empty($source)) {
            return;
        }

        // insert into database
        $source = new TranslationSourceMessage();
        $source->category   = $event->category;
        $source->message    = $event->message;
        $source->save();

        // write to file when in development mode
        if (MW_DEBUG && getenv('MW_DEVELOPMENT') && is_writable(MW_APPS_PATH . '/common/data/translations')) {
            $jsonMessages = [];

            $path = MW_APPS_PATH . '/common/data/translations/' . MW_VERSION . '.json';
            if (is_file($path)) {
                $jsonMessages = json_decode((string)file_get_contents($path));
                if (empty($jsonMessages) || !is_array($jsonMessages)) {
                    $jsonMessages = [];
                }
            }

            $mustAdd = true;
            foreach ($jsonMessages as $jsonMessage) {
                if ($jsonMessage->message == $event->message && $jsonMessage->category == $event->category) {
                    $mustAdd = false;
                    break;
                }
            }

            if ($mustAdd) {
                $jsonMessages[] = [
                    'category'  => $event->category,
                    'message'   => $event->message,
                ];
                file_put_contents($path, json_encode($jsonMessages));
            }
        }
    }

    /**
     * @return void
     * @throws CException
     */
    protected function checkAndEnableTranslations()
    {
        /** @var CWebApplication $app */
        $app = app();

        if (!$app->hasComponent('messages') || !$app->getComponent('messages')) {
            return;
        }

        if (!$app->getLocale()) {
            return;
        }

        $messages = $app->getMessages();

        if ($messages instanceof CPhpMessageSource) {
            $messages->attachEventHandler('onMissingTranslation', [$this, '_handleMissingTranslationsFile']);
            return;
        }

        if ($messages instanceof CDbMessageSource) {
            $messages->attachEventHandler('onMissingTranslation', [$this, '_handleMissingTranslationsDatabase']);
        }
    }
}
