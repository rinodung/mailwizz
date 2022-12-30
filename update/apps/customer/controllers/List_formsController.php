<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List_formsController
 *
 * Handles the actions for list forms related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class List_formsController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageLists()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        $this->addPageScript(['src' => AssetsUrl::js('list-forms.js')]);
        parent::init();
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionIndex($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $subscribeUrl   = apps()->getAppUrl('frontend', 'lists/' . $list->list_uid . '/subscribe', true);
        $subscribeForm  = '';

        try {
            $subscribeHtml = (string)(new GuzzleHttp\Client())->get($subscribeUrl)->getBody();
        } catch (Exception $e) {
            $subscribeHtml = '';
        }

        libxml_use_internal_errors(true);

        try {
            $query = qp($subscribeHtml, 'body', [
                'ignore_parser_warnings'    => true,
                'convert_to_encoding'       => app()->charset,
                'convert_from_encoding'     => app()->charset,
                'use_parser'                => 'html',
            ]);

            $query->top()->find('form')->attr('action', $subscribeUrl);
            $query->top()->find('form')->find('input[name="csrf_token"]')->remove();
            $subscribeForm = (string)$query->top()->find('form')->html();

            if (preg_match('#(<textarea[^>]+)/>#i', $subscribeForm)) {
                $subscribeForm = (string)preg_replace('#(<textarea[^>]+)/>#i', '$1></textarea>', $subscribeForm);
            }

            /** @var OptionCommon $common */
            $common = container()->get(OptionCommon::class);

            $tidyEnabled = app_param('email.templates.tidy.enabled', true);
            $tidyEnabled = $tidyEnabled && $common->getUseTidy();
            if ($tidyEnabled && class_exists('tidy', false)) {
                $tidy    = new tidy();
                $options = (array)app_param('email.templates.tidy.options', []);
                $tidy->parseString($subscribeForm, $options, 'utf8');
                if ($tidy->cleanRepair()) {
                    /** @var tidyNode $node */
                    $node = $tidy->html();
                    $_subscribeForm = !empty($node->value) ? (string)$node->value : '';
                    if (!empty($_subscribeForm) && preg_match('/<form[^>]+>(.*)<\/form>/six', $_subscribeForm, $matches)) {
                        $subscribeForm = (string)$matches[0];
                    }
                }
            }

            $subscribeForm = html_encode((string)$subscribeForm);
        } catch (Exception $e) {
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_forms', 'Your mail list forms'),
            'pageHeading'     => t('list_forms', 'Embed list forms'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('list_forms', 'Embed list forms'),
            ],
        ]);

        $this->render('index', compact('list', 'subscribeForm'));
    }

    /**
     * @param string $list_uid
     *
     * @return Lists
     * @throws CHttpException
     */
    public function loadListModel(string $list_uid): Lists
    {
        $model = Lists::model()->findByAttributes([
            'list_uid'      => $list_uid,
            'customer_id'   => (int)customer()->getId(),
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
