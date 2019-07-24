<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\db\Query;

class InstallController extends Controller
{
    public function actionInit()
    {

        //SQL операции
        self::installDB();

        echo 'Table sessions, users for backend and frontend created\r\n';


        //создать RBAC
        //self::createRBAC();

        echo 'RBAC Installed\r\n';

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

    //Первая SQL миграция
    public static function installDB() {

        return true;

    }

}
