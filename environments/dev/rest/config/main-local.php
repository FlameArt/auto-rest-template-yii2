<?php

$config = [
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '',
        ],
    ],
];

if (!YII_ENV_TEST) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['127.0.0.1', '::1'],
        'generators' => [
            'restgen' => [
                'class' => 'rest\gii\restgen\Generator'
            ],
            'apiGii' => [
                'class' => 'rest\gii\crud\Generator'
            ],
            'niceRecords' => [
                'class' => 'rest\gii\model\Generator'
            ]
        ],
    ];
}

return $config;
