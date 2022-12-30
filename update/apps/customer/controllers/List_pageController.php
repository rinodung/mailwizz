<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List_pageController
 *
 * Handles the actions for list pages related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class List_pageController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageLists()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        parent::init();
    }

    /**
     * @param string $list_uid
     * @param string $type
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionIndex($list_uid, $type)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListPageType|null $pageType */
        $pageType = ListPageType::model()->findBySlug((string)$type);
        if (empty($pageType)) {
            throw new CHttpException(404, t('app', 'This form type has been disabled!'));
        }

        /** @var ListPage|null $page */
        $page = ListPage::model()->findByAttributes([
            'list_id' => $list->list_id,
            'type_id' => $pageType->type_id,
        ]);
        if (empty($page)) {
            /** @var ListPage $page */
            $page = new ListPage();
            $page->list_id = (int)$list->list_id;
            $page->type_id = (int)$pageType->type_id;
        }

        if (empty($page->content)) {
            $page->content = $pageType->content;
        }

        if ($page->emailSubject->getCanHaveEmailSubject() && empty($page->email_subject)) {
            $page->email_subject = $pageType->email_subject;
        }

        $tags = $pageType->tags->getAvailableTags('', (int)$list->list_id);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($page->getModelName(), []))) {
            $page->attributes = $attributes;
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$page->getModelName()]['content'])) {
                $rawContent = $post[$page->getModelName()]['content'];
                if ($pageType->full_html === ListPage::TEXT_YES) {
                    $page->content = $rawContent;
                } else {
                    $page->content = (string)ioFilter()->purify((string)$rawContent);
                }
            }

            if ($page->save()) {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form contains errors, please correct them and try again.'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'list'      => $list,
                'page'      => $page,
                'pageType'  => $pageType,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect([$this->getRoute(), 'list_uid' => $list->list_uid, 'type' => $pageType->slug]);
                return;
            }
        }

        $this->setData([
            'list'      => $list,
            'pageType'  => $pageType,
        ]);
        $page->fieldDecorator->onHtmlOptionsSetup = [$this, '_addEditorOptions'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('lists', 'Your mail list {formName}', ['{formName}' => html_encode($pageType->name)]),
            'pageHeading'     => t('lists', 'Mail list {formName}', ['{formName}' => html_encode($pageType->name)]),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                $pageType->name,
            ],
        ]);

        /** @var ListPageType[] $pageTypes */
        $pageTypes = ListPageType::model()->findAll();

        $this->render($pageType->slug, compact('list', 'page', 'pageType', 'pageTypes', 'tags'));
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _addEditorOptions(CEvent $event)
    {
        if (!in_array($event->params['attribute'], ['content'])) {
            return;
        }

        $options = [];
        if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
            $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
        }
        $options['id'] = CHtml::activeId($event->sender->owner, $event->params['attribute']);

        /** @var ListPageType $pageType */
        $pageType = $this->getData('pageType');

        if ($event->params['attribute'] == 'content' && $pageType->full_html === ListPage::TEXT_YES) {
            $options['fullPage'] = true;
            $options['allowedContent'] = true;
            $options['height'] = 500;
        }

        $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
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
