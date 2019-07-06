<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        //缓存配置 SSDB
        'cache' => [
            'class' => 'yii\caching\SsdbCache',
            'servers' => [
                [
                    'host' => '172.16.13.20',
                    'port' => 8888,
                    'auth' => 'ozaewjmxzq9iNwpxbelhsjajiyiIy8hy',
                    'timeout' => 2000,
                    'keyPrefix' => 'SOCIAL_API_'
                ]
            ],
        ],
        //数据库配置
        'db_social'=> [
            'class'=>'yii\db\Connection',
            'dsn'=>'mysql:host=172.16.13.20;dbname=i500_social',
            'username'=>'db_social',
            'password'=>'ZkorhM-a9gotq4z',
            'charset'=>'utf8mb4',
        ],
        'db_shop'  => [
            'class'=>'yii\db\Connection',
            'dsn'=>'mysql:host=172.16.13.20;dbname=shop',
            'username'=>'db_shop',
            'password'=>'oba9|xBPgcjh9ls',
            'charset'=>'utf8',
        ],
        'db_500m'  => [
            'class'=>'yii\db\Connection',
            'dsn'=>'mysql:host=172.16.13.20;dbname=500m',
            'username'=>'db_500m',
            'password'=>'x5cw]WGsdh1eorw',
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
