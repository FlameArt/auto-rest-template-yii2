<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-rest',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'rest\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [

        'request' => [
            'csrfParam' => 'vp',
        ],

        'user' => [
            'identityClass' => 'common\models\DB\Users',
            'enableAutoLogin' => true,
            'identityCookie' => [
                'name' => 'user', // unique for backend
                'path'=>'/rest/web'  // correct path for the backend app.
            ]
        ],

        'session' => [
            'class' => 'yii\web\DbSession',
            'db' => 'db',  // ID компонента для взаимодействия с БД. По умолчанию 'db'.
            'sessionTable' => 'sessions', // название таблицы для хранения данных сессии. По умолчанию 'session'.
            'name' => 'session', // unique
            'timeout' => 3600*24*30*36,
            //'savePath' => __DIR__ . '/../runtime', // папка для хранения сессии, которая нужна только для файлового хранения
        ],

        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],

        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        'assetManager' => [
            'appendTimestamp' => true,
            'bundles' => YII_ENV_PROD ? require(__DIR__ . '/' . 'production-asset.php' ) : [],
        ],

        'urlManager' => [

            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,

            'rules' => [
                //['class' => 'yii\rest\UrlRule', 'controller' => 'user'],
                'lul' => 'site/index',
                '<controller:[\w-]+>s/<action:[\w-]+>' => '<controller>/<action>',
            ],
        ],


    ],
    'params' => $params,
];
