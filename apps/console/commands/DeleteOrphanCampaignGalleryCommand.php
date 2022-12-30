<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeleteOrphanCampaignGalleryCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.6
 */

class DeleteOrphanCampaignGalleryCommand extends ConsoleCommand
{
    /**
     * Start point
     *
     * @return int
     */
    public function actionIndex()
    {
        $finder = (new Symfony\Component\Finder\Finder())
            ->directories()
            ->name('cmp*')
            ->in((string)Yii::getPathOfAlias('root.frontend.assets.gallery'));

        $errorCount     = 0;
        $successCount   = 0;
        $orphanCount    = 0;

        foreach (iterator_to_array($finder, true) as $dir) {
            $galleryName    = (string)$dir->getBasename();
            $galleryPath    = (string)$dir->getRealPath();
            $prefix         = (string)substr($galleryName, 0, 3);
            if ($prefix !== 'cmp') {
                continue;
            }

            $campaignUid = (string)substr($galleryName, 3);
            if (strlen($campaignUid) != 13) {
                continue;
            }

            $campaign = Campaign::model()->findByAttributes([
                'campaign_uid' => $campaignUid,
            ]);

            if (!empty($campaign)) {
                $this->stdout(sprintf('Gallery: "%s" - Campaign(UID: %s) still exists, nothing to do', $galleryName, $campaignUid));
                continue;
            }
            $orphanCount++;

            $this->stdout(sprintf('Gallery: "%s" - Campaign(UID: %s) does not exists anymore, removing the gallery folder: %s...', $galleryName, $campaignUid, $galleryPath));

            if (FileSystemHelper::deleteDirectoryContents($galleryPath, true, 1)) {
                $this->stdout(sprintf('Gallery: "%s" - The gallery folder has been removed successfully', $galleryName));
                $successCount++;
            } else {
                $this->stdout(sprintf('Gallery: "%s" - Unable to remove the gallery folder', $galleryName));
                $errorCount++;
            }
        }

        $this->stdout(sprintf(
            'Done, out of %d orphan galleries, %d were removed successfully and %d could not be removed',
            $orphanCount,
            $successCount,
            $errorCount
        ));

        return 0;
    }
}
