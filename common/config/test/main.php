<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        //缓存配置 SSDB
        'cache' => [
            'class' => 'yii\caching\SsdbCache',
            'servers' => [
                [
                    'host' => '127.0.0.1',
                    'port' => 8888,
                    'auth' => 'kakvi6Zfjsqvddwourzr0wfZjeckqtxj',
                    'timeout' => 2000,
                    'keyPrefix' => 'SOCIAL_API_'
                ]
            ],
        ],
        //数据库配置
        'db_social'=> [
            'class'=>'yii\db\Connection',
            'dsn'=>'mysql:host=127.0.0.1;dbname=i500_social',
            'username'=>'500m',
            'password'=>'500m',
            'charset' => 'utf8mb4',
        ],
        'db_shop'  => [
            'class'=>'yii\db\Connection',
            'dsn'=>'mysql:host=127.0.0.1;dbname=shop',
            'username'=>'shop',
            'password'=>'shop',
            'charset'=>'utf8',
        ],
        'db_500m'  => [
            'class'=>'yii\db\Connection',
            'dsn'=>'mysql:host=127.0.0.1;dbname=500m_new',
            'username'=>'500m',
            'password'=>'500m',
            'charset'=>'utf8',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning','trace'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'error/index',
        ],
        'urlManager'=> [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => false,
            'showScriptName' => false,
            'rules' => require 'rules.php',
        ],
    ],
    'id' => 'app-frontend',
    'language'=>'zh-CN',
    'basePath' => dirname(dirname(__DIR__)) . '/frontend',
    'bootstrap' => ['log'],
    'defaultRoute'=>'index',  //设置默认路由
    'controllerNamespace' => 'frontend\controllers',
    'params' => require(__DIR__ . '/params.php'),
    'modules' => [
        'v1' => [
            'class' => 'frontend\modules\v1\Module',
        ],
        'v2' => [
            'class' => 'frontend\modules\v2\Module',
        ],
        'v3' => [
            'class' => 'frontend\modules\v3\Module',
        ],
        'v4' => [
            'class' => 'frontend\modules\v4\Module',
        ],
        'v5' => [
            'class' => 'frontend\modules\v5\Module',
        ],
    ],
];
