<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        //数据库配置
        'db_social'=> [
            'class'=>'yii\db\Connection',
            'dsn'=>'mysql:host=127.0.0.1;dbname=i500',
            'username'=>'db',
            'password'=>'',
            'charset'=>'utf8',
        ],
        'db'    => [
            'class'=>'yii\db\Connection',
            'dsn'=>'mysql:host=118.186.247.55;dbname=500m_new',
            'username'=>'500m',
            'password'=>'500m',
            'charset'=>'utf8',
        ],
        //数据库配置
        'db_social'=> [
            'class'=>'yii\db\Connection',
            'dsn'=>'mysql:host=127.0.0.1;dbname=i500',
            'username'=>'root',
            'password'=>'',
            'charset'=>'utf8',
        ],
        'db'    => [
            'class'=>'yii\db\Connection',
            'dsn'=>'mysql:host=127.0.0.1;dbname=i500',
            'username'=>'root',
            'password'=>'root',
            'charset'=>'utf8',
        ],
        'mongodb' => [
            'class' => 'yii\mongodb\Connection',
            'dsn' => 'mongodb://500m:trouCUs3hq1i@118.186.247.55:27017/shop',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
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
            //'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [],
        ]
    ],
    'id' => 'app-frontend',
    'basePath' => dirname(dirname(__DIR__)) . '/frontend',
    'bootstrap' => ['log'],
    'defaultRoute'=>'index',  //设置默认路由
    'controllerNamespace' => 'frontend\controllers',
    'params' => require(__DIR__ . '/params.php'),
    'modules' => [
        'v1' => [
            'class' => 'frontend\modules\v1\Module',
        ],
    ],
];
