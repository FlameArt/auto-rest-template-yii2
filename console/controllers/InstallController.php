<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\db\Exception;
use yii\db\Query;

class InstallController extends Controller
{
    public function actionInit()
    {

        //SQL операции
        self::installDB();

        echo 'Table sessions created\r\n';

        self::InstallContentModel();

        echo 'Content model installed\r\n';

        //создать RBAC
        //self::createRBAC();

        // echo 'RBAC Installed\r\n';

    }

    //создание админа (отдельно, т.к. базовый админ уже есть в первой миграции)
    public function actionRegisterAdmin () {

        // Код отключён в целях безопасности

        /*
        $user = new User();
        $user->username = 'RootArt';
        $user->email = 'RootArt@RootArt.ru';
        $user->setPassword('ПАРОЛЬ');
        $user->generateAuthKey();

        $user->save();

        //Подсоединить роли
        $auth = Yii::$app->authManager;
        $authorRole = $auth->getRole('MainUser');
        $auth->assign($authorRole, $user->getId());

        return 'good';
        */

    }


    //установка RBAC ролей
    public static function createRBAC () {

        $auth = Yii::$app->authManager;

        // add "createPost" permission
        $createPost = $auth->createPermission('createPost');
        $createPost->description = 'Create a post';
        $auth->add($createPost);

        // add "updatePost" permission
        $updatePost = $auth->createPermission('updatePost');
        $updatePost->description = 'Update post';
        $auth->add($updatePost);

        // add "author" role and give this role the "createPost" permission
        $author = $auth->createRole('picboy');
        $auth->add($author);
        $auth->addChild($author, $createPost);
        $auth->addChild($author, $updatePost);

        // add "admin" role and give this role the "updatePost" permission
        // as well as the permissions of the "author" role
        $admin = $auth->createRole('admin');
        $auth->add($admin);
        $auth->addChild($admin, $updatePost);
        $auth->addChild($admin, $author);


        //Присоединим роли к существующим пользователям
        $auth->assign($admin, 1); //даём роль админа юзеру с ID=1

    }


    public static function InstallContentModel(){

        try {
            Yii::$app->db->createCommand('
CREATE TABLE `content` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`site` INT(10) UNSIGNED NULL DEFAULT NULL,
	`title` VARCHAR(500) NULL DEFAULT NULL COMMENT \'Заголовок страницы\',
	`content` TEXT NULL COMMENT \'Содержимое страницы\',
	`page` VARCHAR(256) NULL DEFAULT NULL COMMENT \'Страница, компонент Vue, для которого предназначен контент\',
	`public_date` TIMESTAMP NULL DEFAULT NULL COMMENT \'Заложенная дата публикации\',
	`status` TINYINT(4) NULL DEFAULT \'0\' COMMENT \'Статус записи: 0 - скрыт, 1 - опубликован\',
	`route` VARCHAR(2048) NULL DEFAULT \'/NotReleased\' COMMENT \'Полный роут-адрес до страницы\',
	PRIMARY KEY (`id`)
)
COLLATE=\'utf8mb4_general_ci\'
ENGINE=InnoDB
;

'
            )->execute();
        } catch (Exception $e) {
        }


    }

    //Первая SQL миграция
    public static function installDB() {

        // Создаём таблицу сессии
        Yii::$app->db->createCommand('
CREATE TABLE sessions
(
    id CHAR(40) NOT NULL PRIMARY KEY,
    expire INTEGER,
    data BLOB
)'
        )->execute();

        return true;

    }

}
