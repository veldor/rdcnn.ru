<?php

namespace app\models;

use Yii;
use yii\base\Exception;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class LoginForm extends Model
{
    const SCENARIO_ADMIN_LOGIN = 'admin_login';
    const SCENARIO_USER_LOGIN = 'user_login';


    public function scenarios()
    {
        return [
            self::SCENARIO_ADMIN_LOGIN => ['password'],
            self::SCENARIO_USER_LOGIN => ['username', 'password'],
        ];
    }

    public $username;
    public $password;

    private $_user = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required', 'on' => self::SCENARIO_USER_LOGIN],
            [['password'], 'required', 'on' => self::SCENARIO_ADMIN_LOGIN],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => 'Номер обследования',
            'password' => 'Пароль',
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     */
    public function validatePassword($attribute)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!empty($user)) {
                // проверю, если было больше 5 неудачных попыток ввода пароля- время между попытками должно составлять не меньше 10 минут
                if ($user->failed_try > 2 && $user->last_login_try > time() - 600) {
                    $this->addError($attribute, 'Слишком много неверных попыток ввода пароля. Должно пройти не менее 10 минут с последней попытки');
                    return;
                }
                if ($user->failed_try > 5) {
                    $this->addError($attribute, 'Учётная запись заблокирована. Обратитесь к администратору для восстановления доступа');
                    return;
                }

                if (!$user->validatePassword($this->$attribute)) {
                    $user->last_login_try = time();
                    $user->failed_try = ++$user->failed_try;
                    $user->save();
                } else {
                    return;
                }
            }
            $this->addError($attribute, 'Неверный номер обследования или пароль');
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return bool whether the user is logged in successfully
     * @throws Exception
     */
    public function login()
    {
        if ($this->validate()) {
            $user = $this->getUser();
            $user->failed_try = 0;
            if (!$user->user_access_token) {
                $user->user_access_token = Yii::$app->getSecurity()->generateRandomString(255);
            }
            $user->save();
            return Yii::$app->user->login($this->getUser(), 0);
        }
        return false;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function loginAdmin()
    {
        // получу админа
        $admin = User::getAdmin();

        // проверю, если было больше 2 неудачных попыток ввода пароля- время между попытками должно составлять не меньше 10 минут
        if ($admin->failed_try > 2 && $admin->last_login_try > time() - 600) {
            $this->addError('password', 'Слишком много неверных попыток ввода пароля. Должно пройти не менее 10 минут с последней попытки');
            return false;
        }
        if ($admin->failed_try > 5) {
            $this->addError('password', 'Учётная запись заблокирована. Обратитесь к системному администратору для восстановления доступа');
            return false;
        }

        if (!$admin->validatePassword($this->password)) {
            $admin->last_login_try = time();
            $admin->failed_try = ++$admin->failed_try;
            $admin->save();
            $this->addError('password', 'Неверный номер обследования или пароль');
        } else {
            // логиню пользователя
            $admin->failed_try = 0;
            if (empty($admin->access_token)) {
                $admin->access_token = Yii::$app->getSecurity()->generateRandomString(255);
            }
            $admin->save();
            return Yii::$app->user->login($admin, 0);
        }
        return false;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function loginUser()
    {
        // получу данные о пользователе
        $user = User::findByUsername(ExecutionHandler::toLatin($this->username));
        if(!empty($user)){
            // проверю, если было больше 2 неудачных попыток ввода пароля- время между попытками должно составлять не меньше 10 минут
            if ($user->failed_try > 5 && $user->last_login_try > time() - 600) {
                $this->addError('username', 'Слишком много неверных попыток ввода пароля. Должно пройти не менее 10 минут с последней попытки');
                return false;
            }
            if ($user->failed_try > 15) {
                $this->addError('username', 'Было выполнено слишком много неверных попыток ввода пароля. В целях безопасности данные были удалены. Вы можете обратиться к нам для восстановления доступа');
                return false;
            }
            if (!$user->validatePassword($this->password)) {
                $user->last_login_try = time();
                $user->failed_try = ++$user->failed_try;
                $user->save();
                $this->addError('username', 'Неверный номер обследования или пароль');
                return false;
            }
            // логиню пользователя
            $user->failed_try = 0;
            if (empty($user->access_token)) {
                $user->access_token = Yii::$app->getSecurity()->generateRandomString(255);
            }
            $user->save();
            return Yii::$app->user->login($user, 0);
        }
        $this->addError("username", "Неверный номер обследования или пароль");
        return false;
    }


    public static function autoLoginAdmin()
    {
        // получу админа
        $admin = User::getAdmin();
        Yii::$app->user->login($admin, 0);
    }
}
