<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * User
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "user".
 *
 * The followings are the available columns in table 'user':
 * @property integer $user_id
 * @property string $user_uid
 * @property integer|null $group_id
 * @property integer|null $language_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property string $timezone
 * @property string $avatar
 * @property string $removable
 * @property string $twofa_enabled
 * @property string $twofa_secret
 * @property integer $twofa_timestamp
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 * @property string $last_login
 *
 * The followings are the available model relations:
 * @property Language $language
 * @property UserGroup $group
 * @property UserAutoLoginToken[] $autoLoginTokens
 * @property PricePlanOrderNote[] $pricePlanOrderNotes
 * @property UserMessage[] $messages
 */
class User extends ActiveRecord
{
    /**
     * @var string
     */
    public $fake_password;

    /**
     * @var string
     */
    public $confirm_password;

    /**
     * @var string
     */
    public $confirm_email;

    /**
     * @var string
     */
    public $new_avatar;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{user}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $avatarMimes = null;
        if (CommonHelper::functionExists('finfo_open')) {

            /** @var FileExtensionMimes $extensionMimes */
            $extensionMimes = app()->getComponent('extensionMimes');

            /** @var array $avatarMimes */
            $avatarMimes = $extensionMimes->get(['png', 'jpg', 'jpeg', 'gif'])->toArray();
        }

        $rules = [
            // when new user is created .
            ['first_name, last_name, email, confirm_email, fake_password, confirm_password, timezone, status', 'required', 'on' => 'insert'],
            // when a user is updated
            ['first_name, last_name, email, confirm_email, timezone, status', 'required', 'on' => 'update'],
            //
            ['language_id, group_id', 'numerical', 'integerOnly' => true],
            ['group_id', 'exist', 'className' => UserGroup::class],
            ['language_id', 'exist', 'className' => Language::class],
            ['first_name, last_name', 'length', 'min' => 1, 'max' => 100],
            ['email, confirm_email', 'length', 'min' => 4, 'max' => 100],
            ['email, confirm_email', 'email', 'validateIDN' => true],
            ['timezone', 'in', 'range' => array_keys(DateTimeHelper::getTimeZones())],
            ['fake_password, confirm_password', 'length', 'min' => 6, 'max' => 100],
            ['confirm_password', 'compare', 'compareAttribute' => 'fake_password'],
            ['confirm_email', 'compare', 'compareAttribute' => 'email'],
            ['email', 'unique', 'criteria' => ['condition' => 'user_id != :uid', 'params' => [':uid' => (int)$this->user_id]]],

            // avatar
            ['new_avatar', 'file', 'types' => ['png', 'jpg', 'jpeg', 'gif'], 'mimeTypes' => $avatarMimes, 'allowEmpty' => true],

            // mark them as safe for search
            ['first_name, last_name, email, status, group_id', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'language'              => [self::BELONGS_TO, Language::class, 'language_id'],
            'group'                 => [self::BELONGS_TO, UserGroup::class, 'group_id'],
            'autoLoginTokens'       => [self::HAS_MANY, UserAutoLoginToken::class, 'user_id'],
            'pricePlanOrderNotes'   => [self::HAS_MANY, PricePlanOrderNote::class, 'user_id'],
            'messages'              => [self::HAS_MANY, UserMessage::class, 'user_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'user_id'       => t('users', 'User'),
            'language_id'   => t('users', 'Language'),
            'group_id'      => t('users', 'Group'),
            'first_name'    => t('users', 'First name'),
            'last_name'     => t('users', 'Last name'),
            'email'         => t('users', 'Email'),
            'password'      => t('users', 'Password'),
            'timezone'      => t('users', 'Timezone'),
            'avatar'        => t('users', 'Avatar'),
            'new_avatar'    => t('users', 'New avatar'),
            'removable'     => t('users', 'Removable'),

            'confirm_email'     => t('users', 'Confirm email'),
            'fake_password'     => t('users', 'Password'),
            'confirm_password'  => t('users', 'Confirm password'),

            'twofa_enabled' => t('users', '2FA enabled'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'twofa_enabled' => t('users', 'Please make sure you scan the QR code in your authenticator application before enabling this feature, otherwise you will be locked out from your account'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     * @throws CException
     */
    public function search()
    {
        $criteria = new CDbCriteria();

        $criteria->compare('first_name', $this->first_name, true);
        $criteria->compare('last_name', $this->last_name, true);
        $criteria->compare('email', $this->email, true);
        $criteria->compare('status', $this->status);
        $criteria->compare('group_id', $this->group_id);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    'user_id'   => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return User the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var User $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        // @phpstan-ignore-next-line
        return collect([$this->first_name, $this->last_name])
            ->filter()
            ->whenEmpty(function (Illuminate\Support\Collection $collection) {
                return $collection->push((string)$this->email);
            })
            ->implode(' ');
    }

    /**
     * @return array
     */
    public function getStatusesArray(): array
    {
        return [
            self::STATUS_ACTIVE     => t('app', 'Active'),
            self::STATUS_INACTIVE   => t('app', 'Inactive'),
        ];
    }

    /**
     * @return array
     */
    public function getTimeZonesArray(): array
    {
        return DateTimeHelper::getTimeZones();
    }

    /**
     * @param string $user_uid
     *
     * @return User|null
     */
    public function findByUid(string $user_uid): ?self
    {
        return self::model()->findByAttributes([
            'user_uid' => $user_uid,
        ]);
    }

    /**
     * @return string
     */
    public function generateUid(): string
    {
        $unique = StringHelper::uniqid();
        $exists = $this->findByUid($unique);

        if (!empty($exists)) {
            return $this->generateUid();
        }

        return $unique;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return (string)$this->user_uid;
    }

    /**
     * For compatibility with the User component
     *
     * @return int
     */
    public function getId(): int
    {
        return (int)$this->user_id;
    }

    /**
     * @param int $size
     *
     * @return string
     */
    public function getGravatarUrl(int $size = 50): string
    {
        $gravatar = sprintf('//www.gravatar.com/avatar/%s?s=%d', md5(strtolower(trim((string)$this->email))), (int)$size);
        return (string)hooks()->applyFilters('user_get_gravatar_url', $gravatar, $this, $size);
    }

    /**
     * @param int $width
     * @param int $height
     * @param bool $forceSize
     *
     * @return string
     */
    public function getAvatarUrl(int $width = 50, int $height = 50, bool $forceSize = false): string
    {
        if (empty($this->avatar)) {
            return $this->getGravatarUrl($width);
        }
        return (string)ImageHelper::resize($this->avatar, $width, $height, $forceSize);
    }

    /**
     * @param string|array $route
     *
     * @return bool
     */
    public function hasRouteAccess($route): bool
    {
        if (empty($this->group_id)) {
            return true;
        }
        return $this->group->hasRouteAccess($route);
    }

    /**
     * @return void
     */
    public function updateLastLogin(): void
    {
        if (!array_key_exists('last_login', $this->getAttributes())) {
            return;
        }
        $columns = ['last_login' => MW_DATETIME_NOW];
        $params  = [':id' => $this->user_id];
        db()->createCommand()->update($this->tableName(), $columns, 'user_id = :id', $params);
        $this->last_login = date('Y-m-d H:i:s');
    }

    /**
     * @return bool
     */
    public function getTwoFaEnabled(): bool
    {
        return (string)$this->twofa_enabled === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getIsRemovable(): bool
    {
        return (string)$this->removable === self::TEXT_YES;
    }

    /**
     * @return void
     */
    protected function afterValidate()
    {
        parent::afterValidate();
        $this->handleUploadedAvatar();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (!parent::beforeSave()) {
            return false;
        }

        if (empty($this->user_uid)) {
            $this->user_uid = $this->generateUid();
        }

        if (!empty($this->fake_password)) {
            $this->password = passwordHasher()->hash($this->fake_password);
        }

        if ($this->removable === self::TEXT_NO) {
            $this->status = self::STATUS_ACTIVE;
            $this->group_id = null;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        return $this->removable === self::TEXT_YES;
    }

    /**
     * @return void
     */
    protected function handleUploadedAvatar(): void
    {
        if ($this->hasErrors()) {
            return;
        }

        /** @var CUploadedFile|null $avatar */
        $avatar = CUploadedFile::getInstance($this, 'new_avatar');

        if (!$avatar) {
            return;
        }

        $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.files.avatars');
        if (!file_exists($storagePath) || !is_dir($storagePath)) {
            if (!mkdir($storagePath, 0777, true)) {
                $this->addError('new_avatar', t('users', 'The avatars storage directory({path}) does not exists and cannot be created!', [
                    '{path}' => $storagePath,
                ]));
                return;
            }
        }

        $newAvatarName = StringHelper::random(8, true) . '-' . $avatar->getName();
        if (!$avatar->saveAs($storagePath . '/' . $newAvatarName)) {
            $this->addError('new_avatar', t('users', 'Cannot move the avatar into the correct storage folder!'));
            return;
        }

        $this->avatar = '/frontend/assets/files/avatars/' . $newAvatarName;
    }
}
