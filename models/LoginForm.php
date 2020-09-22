<?php

namespace app\models;

use app\models\utils\GrammarHandler;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\base\Model;
use yii\db\StaleObjectException;

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
            self::SCENARIO_ADMIN_LOGIN => ['username', 'password'],
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
            [['username', 'password'], 'required', 'on' => self::SCENARIO_ADMIN_LOGIN],
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
    public function login(): bool
    {
        if ($this->validate()) {
            $user = $this->getUser();
            if(null === $user){
                throw new Exception('Не найден пользователь!');
            }
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
     * @return User
     */
    public function getUser(): User
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }
        return $this->_user;
    }

    /**
     * @return bool
     */
    public function loginAdmin(): bool
    {
        $blocked = $this->checkBlacklist();
        if ($blocked) {
            $blocked->last_try = time();
            $blocked->save();
            $this->addError('password', 'Компьютер в чёрном списке. Обратитесь к администратору!');
            return false;
        }

        // получу админа
        $admin = User::getAdmin();

        // проверю, правильно ли введено имя
        if ($admin->username !== $this->username) {
            $this->registerWrongTry();
            $this->addError('password', 'Неверный логин или пароль');
            return false;
        }

        if (!$admin->validatePassword($this->password)) {
            $this->registerWrongTry();
            $this->addError('password', 'Неверный логин или пароль');
            return false;
        }

// логиню пользователя
        if (empty($admin->access_token)) {
            try {
                $admin->access_token = Yii::$app->getSecurity()->generateRandomString(255);
            } catch (Exception $e) {
                die('не удалось добавить токен');
            }
        }
        $admin->save();
        return Yii::$app->user->login($admin, 60 * 60 * 30);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function loginUser(): bool
    {
        // проверю, не занесён ли IP в чёрный список
        $blocked = $this->checkBlacklist();
        if ($blocked) {
            // если прошло больше суток с последнего ввода пароля- уберу IP из блеклиста
            if(time() - $blocked->last_try > 60 * 60 * 24){
                try {
                    $blocked->delete();
                } catch (StaleObjectException $e) {
                } catch (Throwable $e) {
                    // ошибка при удалении блокировки
                }
            }
            // если количество неудачных попыток больше 3 и не прошло 10 минут- отправим ожидать
            elseif($blocked->try_count > 3 && (time() - $blocked->last_try < 600)){
                $this->addError('username', 'Слишком много неверных попыток ввода пароля. Должно пройти не менее 10 минут с последней попытки');
                return false;
            }
            elseif ($blocked->missed_execution_number > 20){
                $this->addError('username', 'Слишком много попыток ввода номера обследования. Попробуйте снова через сутки');
                return false;
            }
        }
        // проверю, не производится ли попытка зайти под админской учёткой
        $admin = User::getAdmin();
        if($this->username === $admin->username){
            $this->addError('password', 'Неверный номер обследования или пароль');
            return false;
        }

        // получу данные о пользователе
        $user = User::findByUsername(GrammarHandler::toLatin($this->username));
        if ($user !== null) {
            if ($user->failed_try > 20) {
                $this->addError('username', 'Было выполнено слишком много неверных попыток ввода пароля. В целях безопасности данные были удалены. Вы можете обратиться к нам для восстановления доступа');
                return false;
            }
            if (!$user->validatePassword($this->password)) {
                $user->last_login_try = time();
                $user->failed_try = ++$user->failed_try;
                $user->save();
                $this->addError('username', 'Неверный номер обследования или пароль');
                if($blocked){
                    $blocked->updateCounters(['try_count' => 1]);
                    $blocked->last_try = time();
                    $blocked->save();
                }
                else{
                    $this->registerWrongTry();
                }
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
        $this->addError('username', 'Неверный номер обследования или пароль');
        // добавлю пользователя в список подозрительных
        if($blocked){
            $blocked->updateCounters(['missed_execution_number' => 1]);
            $blocked->save();
        }
        else{
            $this->registerWrongTry();
        }
        return false;
    }


    private function checkBlacklist()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        return Table_blacklist::findOne(['ip' => $ip]);
    }

    private function registerWrongTry(): void
    {
        // проверю, не занесён ли уже IP в базу данных
        $ip = $_SERVER['REMOTE_ADDR'];
        $is_blocked = Table_blacklist::findOne(['ip' => $ip]);
        if ($is_blocked === null) {
            // внесу IP в чёрный список
            $blacklist = new Table_blacklist();
            $blacklist->ip = $ip;
            $blacklist->try_count = 1;
            $blacklist->last_try = time();
            $blacklist->save();
        }
    }
}
