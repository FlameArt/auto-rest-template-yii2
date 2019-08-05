<?php
namespace rest\controllers;

use Yii;
use yii\helpers\Json;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use yii\filters\ContentNegotiator;
use yii\web\Response;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['auth'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'auth' => ['post'],
                    'logout' => ['post'],
                ],
            ],

            // JSON формат вывода
            'contentNegotiator' => [
                'class' => ContentNegotiator::className(),
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ]
        ];
    }

    /**
     * Displays homepage.
     *
     */
    public function actionIndex()
    {
        return [
            "Welcome"=> "Welcome!"
        ];
    }

    /**
     * Login action.
     */
    public function actionAuth()
    {

        // Юзер уже авторизован - сразу отправляем результаты
        if (!Yii::$app->user->isGuest) {
            return [
                "isAuthorized"=>true,
                "session" => Yii::$app->user->identityCookie
            ];
        }

        // Данные
        $data = Json::decode(\Yii::$app->request->getRawBody(), true);

        // Проверяем целостность и корректность данных
        $exceptionlist = "";
        if(!isset($data->login)) $exceptionlist.="Не указан логин. ";
        if(!isset($data->password)) $exceptionlist.="Не указан пароль. ";

        // Заполняем модель
        $model = new LoginForm();
        $model->username = $data->login;
        $model->password = $data->password;
        $model->rememberMe = true;

        // Логинимся
        if(!$model->login()) {
            return [
                "isAuthorized"=>false,
                "message" => "Неверный логин или пароль. " . $exceptionlist
            ];
        }
        else {

            return [
                "isAuthorized"=>true,
                "session" => Yii::$app->user->identityCookie
            ];

        }

    }

    /**
     * Logout action.
     */
    public function actionLogout()
    {

        Yii::$app->user->logout();

        return [
            "LoggedOut"=>true,
            "NeedLogin"=>true
        ];
    }
}
