<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}
/**
 * CommonEmailTemplate
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.6.2
 */

/**
 * This is the model class for table "{{common_email_template}}".
 *
 * The followings are the available columns in table '{{common_email_template}}':
 * @property integer $template_id
 * @property string $name
 * @property string $slug
 * @property string $subject
 * @property string $content
 * @property string $removable
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property CommonEmailTemplateTag[] $tags
 */
class CommonEmailTemplate extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{common_email_template}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, subject, content', 'required'],
            ['name', 'length', 'max' => 150],
            ['subject', 'length', 'max' => 255],
            ['slug', 'length', 'max' => 255],
            ['slug', 'unique'],

            ['name, slug, subject', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'tags' => [self::HAS_MANY, CommonEmailTemplateTag::class, 'template_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'template_id' => t('common_email_templates', 'Template'),
            'name' => t('common_email_templates', 'Name'),
            'slug' => t('common_email_templates', 'Slug'),
            'subject' => t('common_email_templates', 'Subject'),
            'content' => t('common_email_templates', 'Content'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'name' => t('common_email_templates', 'The name of the template, used internally mostly'),
            'subject' => t('common_email_templates', 'The subject which will be used for this email'),
            'content' => t('common_email_templates', 'The email content'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     * @throws CException
     */
    public function search()
    {
        $criteria = new CDbCriteria();

        $criteria->compare('name', $this->name, true);
        $criteria->compare('slug', $this->slug, true);
        $criteria->compare('subject', $this->subject, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria' => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar' => 'page',
            ],
            'sort' => [
                'defaultOrder' => [
                    'template_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CommonEmailTemplate the static model class
     */
    public static function model($className = __CLASS__)
    {
        /** @var CommonEmailTemplate $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return bool
     */
    public function getIsRemovable(): bool
    {
        return (string)$this->removable === self::TEXT_YES;
    }

    /**
     * @param string $slug
     *
     * @return CommonEmailTemplate|null
     */
    public static function findBySlug(string $slug): ?self
    {
        return self::model()->findByAttributes([
            'slug' => $slug,
        ]);
    }

    /**
     * @param string $slug
     * @param array $params
     * @param array $tags
     *
     * @return array
     */
    public static function getAsParamsArrayBySlug(string $slug, array $params = [], array $tags = []): array
    {
        if (!($model = self::findBySlug($slug))) {
            return CMap::mergeArray([
                'subject' => '',
                'body' => '',
            ], $params);
        }

        return CMap::mergeArray($params, [
            'subject' => $model->getParsedSubject($tags),
            'body' => $model->getParsedContent($tags),
        ]);
    }

    /**
     * @return array
     */
    public static function getCommonTags(): array
    {
        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        $attributes = [
            [
                'tag' => '[DATE]',
                'description' => t('common_email_templates', 'Shows the current date'),
                'value' => date('Y-m-d'),
            ],
            [
                'tag' => '[YEAR]',
                'description' => t('common_email_templates', 'Shows the current year'),
                'value' => date('Y'),
            ],
            [
                'tag' => '[MONTH]',
                'description' => t('common_email_templates', 'Shows the current month'),
                'value' => date('m'),
            ],
            [
                'tag' => '[DAY]',
                'description' => t('common_email_templates', 'Shows the current day'),
                'value' => date('d'),
            ],
            [
                'tag' => '[SITE_NAME]',
                'description' => t('common_email_templates', 'Shows the site name'),
                'value' => $common->getSiteName(),
            ],
        ];

        $tags = [];
        foreach ($attributes as $attr) {
            $tag = new CommonEmailTemplateTag();
            foreach ($attr as $key => $value) {
                $tag->$key = $value;
            }
            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * @return array
     */
    public function getAllTags(): array
    {
        return CMap::mergeArray(
            $this->getIsNewRecord() ? [] : (!empty($this->tags) ? $this->tags : []),
            self::getCommonTags()
        );
    }

    /**
     * @param array $tags
     *
     * @return string
     */
    public function getParsedSubject(array $tags = []): string
    {
        foreach (self::getCommonTags() as $tag) {
            if (!isset($tags[$tag->tag])) {
                $tags[$tag->tag] = $tag->value;
            }
        }
        return (string)str_replace(array_keys($tags), array_values($tags), (string)$this->subject);
    }

    /**
     * @param array $tags
     *
     * @return string
     */
    public function getParsedContent(array $tags = []): string
    {
        foreach (self::getCommonTags() as $tag) {
            if (!isset($tags[$tag->tag])) {
                $tags[$tag->tag] = $tag->value;
            }
        }
        return (string)str_replace(array_keys($tags), array_values($tags), (string)$this->content);
    }

    /**
     * @return array
     */
    public static function getCoreTemplatesDefinitions(): array
    {
        static $definitions;
        if ($definitions === null) {
            $location = (string)Yii::getPathOfAlias('common.data.emails');
            $definitions = require $location . '/definitions.php';
        }

        return (array)$definitions;
    }

    /**
     * @param string $id
     * @return bool
     * @throws CException
     */
    public static function reinstallCoreTemplateByDefinitionId(string $id): bool
    {
        $definitions = self::getCoreTemplatesDefinitions();

        $definition = [];
        foreach ($definitions as $def) {
            if ($def['slug'] === $id) {
                $definition = $def;
                break;
            }
        }

        if (empty($definition)) {
            return false;
        }

        return self::reinstallCoreTemplateByDefinition($definition);
    }

    /**
     * @param array $definition
     * @return bool
     * @throws CException
     */
    public static function reinstallCoreTemplateByDefinition(array $definition): bool
    {
        /** @var OptionEmailTemplate $optionEmailTemplate */
        $optionEmailTemplate = container()->get(OptionEmailTemplate::class);

        /** @var OptionCommon $optionCommon */
        $optionCommon = container()->get(OptionCommon::class);

        if (!is_cli()) {
            /** @var CWebApplication $app */
            $app     = app();
            $context = $app->getController();
        } else {
            /** @var CConsoleApplication $app */
            $app     = app();
            $context = $app->getCommand();
        }

        self::model()->deleteAllByAttributes([
            'slug' => $definition['slug'],
        ]);

        $model = new self();
        $model->attributes = $definition;

        $location = (string)Yii::getPathOfAlias('common.data.emails');
        $content  = $context->renderFile($location . '/' . $model->slug . '.php', [], true);

        $searchReplace = [
            '[SITE_NAME]'       => $optionCommon->getSiteName(),
            '[SITE_TAGLINE]'    => $optionCommon->getSiteTagline(),
            '[CURRENT_YEAR]'    => date('Y'),
            '[CONTENT]'         => $content,
        ];
        $model->content = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $optionEmailTemplate->common);

        if (!$model->save()) {
            return false;
        }

        foreach ($definition['tags'] as $attributes) {
            $tag = new CommonEmailTemplateTag();
            $tag->attributes = $attributes;
            $tag->template_id = $model->template_id;
            $tag->save();
        }

        return true;
    }

    /**
     * @return void
     * @throws CException
     */
    public static function reinstallCoreTemplates()
    {
        foreach (self::getCoreTemplatesDefinitions() as $definition) {
            self::reinstallCoreTemplateByDefinition($definition);
        }
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        if (empty($this->slug)) {
            $this->slug = URLify::filter($this->name);
        }
        return parent::beforeValidate();
    }
}
