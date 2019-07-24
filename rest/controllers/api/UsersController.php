<?php
namespace rest\controllers\api;

use yii\rest\ActiveController;

class UsersController extends ActiveController
{
    public $modelClass = 'common\models\User';

    public function actionIndex()
    {
        return [
            "Welcome"=> "Welcome!"
        ];
    }

}

