<?php
namespace common\models\UserModel\Forms;

use Yii;
use yii\base\Model;

use common\models\DB\UsersBackend;
use common\models\DB\UsersFrontend;

/**
 * Форма регистрации
 */
class SignupForm extends Model
{

    // Входные параметры формы
    public $username;
    public $email;
    public $password;


    /**
     * @inheritdoc
     */
    public function rules()
    {

        //РАЗНЫЕ ТАБЛИЦЫ ЮЗЕРОВ ДЛЯ БЭКЕНДА И ФРОНТЕНДА
        switch(\Yii::$app->id) {
            case 'app-backend': {
                $target_class='backend\models\DB\UsersBackend';
                break;
            }
            case 'app-frontend': {
                $target_class='frontend\models\DB\UsersFrontend';
                break;
            }
            default: {
                throw new \yii\web\HttpException(501, 'Неизвестный модуль');
                die('end');
            }
        }


        return [
            ['username', 'filter', 'filter' => 'trim'],
            ['username', 'required'],
            ['username', 'unique', 'targetClass' => $target_class, 'message' => 'Такое имя пользователя уже существует'],
            ['username', 'string', 'min' => 2, 'max' => 255],

            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => $target_class, 'message' => 'Этот e-mail уже зарегистрирован'],

            ['password', 'required'],
            ['password', 'string', 'min' => 6],
        ];
    }

    /**
     * Регистрирует пользователя, возвращает его модель
     */
    public function signup()
    {
        if (!$this->validate()) {
            return null;
        }

        //РАЗНЫЕ ТАБЛИЦЫ ЮЗЕРОВ ДЛЯ БЭКЕНДА И ФРОНТЕНДА
        switch(\Yii::$app->id) {
            case 'app-backend': {
                $user = new UsersBackend();
                break;
            }
            case 'app-frontend': {
                $user = new UsersFrontend();
                break;
            }
            default: {
                throw new \yii\web\HttpException(501, 'Неизвестный модуль');
                die('end');
            }
        }


        $user->username = $this->username;
        $user->email = $this->email;
        $user->setPassword($this->password);
        $user->generateAuthKey();
        
        return $user->save() ? $user : null;
    }
}
