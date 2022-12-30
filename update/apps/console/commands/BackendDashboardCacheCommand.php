<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * BackendDashboardCacheCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.6
 */

class BackendDashboardCacheCommand extends ConsoleCommand
{
    /**
     * @return int
     * @throws Exception
     */
    public function actionIndex()
    {
        $this
            ->rebuildGlanceStatsCache()
            ->rebuildTimelineItemsCache();

        return 0;
    }

    /**
     * @return $this
     */
    protected function rebuildGlanceStatsCache()
    {
        /** @var CConsoleApplication $app */
        $app = app();

        // hold default app language
        $lang = $app->getLanguage();

        foreach ($this->getUsersLanguages() as $languageId => $languageCode) {
            $app->setLanguage($languageCode);

            $cacheKey = sha1('backend.dashboard.glanceStats.' . $languageId);
            cache()->set($cacheKey, BackendDashboardHelper::getGlanceStats(), 600);
        }

        // restore app language
        $app->setLanguage($lang);

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    protected function rebuildTimelineItemsCache()
    {
        // hold default app language
        $lang = app()->getLanguage();

        foreach ($this->getUsersLanguages() as $languageId => $languageCode) {
            app()->setLanguage($languageCode);

            $cacheKey = sha1('backend.dashboard.timelineItems.' . $languageId);
            cache()->set($cacheKey, BackendDashboardHelper::getTimelineItems(), 600);
        }

        // restore app language
        app()->setLanguage($lang);

        return $this;
    }

    /**
     * @return User[]
     */
    protected function getUsers()
    {
        static $users;
        if ($users === null) {
            $users = User::model()->findAll();
        }
        return $users;
    }

    /**
     * @return array
     */
    protected function getUsersLanguages()
    {
        $usersLanguages = [
            // default
            0 => app()->getLanguage(),
        ];

        foreach ($this->getUsers() as $user) {
            if (empty($user->language_id) || empty($user->language)) {
                continue;
            }
            $usersLanguages[$user->language_id] = $user->language->getLanguageAndLocaleCode();
        }

        return $usersLanguages;
    }
}
