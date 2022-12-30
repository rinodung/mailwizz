<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Campaigns_statsController
 *
 * Handles the actions for campaigns Campaigns stats related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.9.5
 */

class Campaigns_statsController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageCampaigns()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        $this->addPageScript(['src' => AssetsUrl::js('campaigns-stats.js')]);
        $this->onBeforeAction = [$this, '_registerJuiBs'];
        parent::init();
    }

    /**
     * List available campaigns stats
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $customerId = (int)customer()->getId();
        $filter     = new CampaignStatsFilter();
        $filter->unsetAttributes();

        $filter->attributes  = (array)request()->getQuery($filter->getModelName(), []);
        $filter->customer_id = $customerId;
        $filter->addRelatedRecord('customer', customer()->getModel(), false);

        $canExport = $filter->customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes';
        if ($canExport && $filter->getIsExportAction()) {

            // Set the download headers
            HeaderHelper::setDownloadHeaders('campaigns-stats.csv');

            $attributes = [
                'name', 'subject', 'listName', 'subscribersCount', 'deliverySuccess', 'uniqueOpens',
                'allOpens', 'uniqueClicks', 'allClicks',
                'unsubscribes', 'bounces', 'softBounces',
                'hardBounces',
            ];

            try {
                $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
                $csvWriter->insertOne(array_map([$filter, 'getAttributeLabel'], $attributes));

                $criteria = $filter->search()->getCriteria();
                $criteria->limit  = 10;
                $criteria->offset = 0;

                $models = CampaignStatsFilter::model()->findAll($criteria);
                while (!empty($models)) {
                    foreach ($models as $model) {
                        $out = [];
                        foreach ($attributes as $attribute) {
                            $out[] = (string)$model->$attribute;
                        }
                        $csvWriter->insertOne($out);
                    }
                    $criteria->offset = $criteria->offset + $criteria->limit;
                    $models = CampaignStatsFilter::model()->findAll($criteria);
                }
            } catch (Exception $e) {
            }

            app()->end();
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Campaigns'),
            'pageHeading'     => t('campaigns', 'Stats'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('campaigns', 'Stats') => createUrl('campaigns_stats/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('index', compact('filter', 'customerId'));
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _registerJuiBs(CEvent $event)
    {
        if (in_array($event->params['action']->id, ['index'])) {
            $this->addPageStyles([
                ['src' => apps()->getBaseUrl('assets/css/jui-bs/jquery-ui-1.10.3.custom.css'), 'priority' => -1001],
            ]);
        }
    }
}
