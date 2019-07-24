<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        // Файловый кеш
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        //Отправка почты
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            'useFileTransport' => false, // = false - Почта будет отправлятся на реальный адрес, а не скидываться в файл
        ],
    ],
];
