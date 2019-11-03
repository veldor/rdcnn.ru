<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 *
 * @property int $id [int(10) unsigned]
 * @property int $user_name [int(10) unsigned]
 * @property string $user_pass [varchar(255)]
 * @property string $user_access_token [varchar(255)]
 * @property string $user_auth_key [varchar(255)]
 * @property int $last_login_try [int(11)]
 * @property bool $failed_try [tinyint(4)]
 * @property string $username [varchar(255)]  Номер обследования
 * @property string $auth_key [varchar(255)]
 * @property string $password_hash [varchar(255)]  Хеш пароля
 * @property int $status [smallint(6)]  Статус пользователя
 * @property int $created_at [int(11)]  Дата регистрации
 * @property int $updated_at [int(11)]
 * @property string $access_token [varchar(255)]
 */
class User extends ActiveRecord implements IdentityInterface
{

    const ADMIN_NAME = 'adfascvlgalsegrlkuglkbaldasdf';

    // имя таблицы
    public static function tableName()
    {
        return "person";
    }

    /**
     * Finds an identity by the given ID.
     * @param string|int $id the ID to be looked for
     * @return IdentityInterface the identity object that matches the given ID.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * Finds an identity by the given token.
     * @param mixed $token the token to be looked for
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\yii\filters\auth\HttpBearerAuth]] will set this parameter to be `yii\filters\auth\HttpBearerAuth`.
     * @return IdentityInterface the identity object that matches the given token.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::find()->where(["accessToken" => $token])->one();
    }

    public static function findByUsername($username)
    {
        // найти по имени администратора невозможно
        if($username != self::ADMIN_NAME){
            return static::find()->where(["username" => $username])->one();
        }
        return null;
    }

    public static function getAdmin()
    {
        return static::find()->where(["username" => self::ADMIN_NAME])->one();
    }

    public static function findAllRegistered()
    {
        // верну все записи кроме админской
        return static::find()->where(['<>', 'username', self::ADMIN_NAME])->all();
    }

    public function validatePassword($password){
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Returns an ID that can uniquely identify a user identity.
     * @return string|int an ID that uniquely identifies a user identity.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns a key that can be used to check the validity of a given identity ID.
     *
     * The key should be unique for each individual user, and should be persistent
     * so that it can be used to check the validity of the user identity.
     *
     * The space of such keys should be big enough to defeat potential identity attacks.
     *
     * This is required if [[User::enableAutoLogin]] is enabled. The returned key will be stored on the
     * client side as a cookie and will be used to authenticate user even if PHP session has been expired.
     *
     * Make sure to invalidate earlier issued authKeys when you implement force user logout, password change and
     * other scenarios, that require forceful access revocation for old sessions.
     *
     * @return string a key that is used to check the validity of a given identity ID.
     * @see validateAuthKey()
     */
    public function getAuthKey()
    {
        return $this->user_auth_key;
    }

    /**
     * Validates the given auth key.
     *
     * This is required if [[User::enableAutoLogin]] is enabled.
     * @param string $authKey the given auth key
     * @return bool whether the given auth key is valid.
     * @see getAuthKey()
     */
    public function validateAuthKey($authKey)
    {
        return $this->user_auth_key === $authKey;
    }

    public static function generateNumericPassword(){
        $chars = array_merge(range(0,9));
        shuffle($chars);
        $password = implode(array_slice($chars, 0,4));
        return $password;
    }
}
