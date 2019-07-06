<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-18 上午11:42
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
return [
    /**
     *商家后台api
     */
//    '<controller:\w+>/<id:\d+>' => '<controller>/view',
//    '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
    'v4/notify/<method>/<type>' => 'v4/notify/index',
    'v12/pay-notify/<method>/<type>' => 'v12/pay-notify/index',
    'v13/shop-pay-notify/<method>/<type>' => 'v13/shop-pay-notify/index',
    [
        'class' => 'yii\rest\UrlRule',
        'controller' => ['v4/seek','v4/service'],
        'extraPatterns' => [
            'GET list' => 'list',
            'GET near' => 'near',
           // 'GET community/<community_id:\d+>/city/<community_city_id:\d+>' => 'near',
//            'GET community/<community_id:\d+>/city/<community_city_id:\d+>' => 'near',
            'POST PUT login' => 'login',
            'DELETE delete'=>'delete',
            'POST edit'=>'edit',
        ],

    ],
    [
        'class' => 'yii\rest\UrlRule',
        'controller' => ['v4/recruit'],
        'except' => ['delete', 'update', 'index'],
        'tokens' => [
//            '{id}' => '<id:\\d[\\d,]*',
            '{mobile}' => '<mobile:\\w+>'
        ],
        'extraPatterns' => [
            //'GET,HEAD {mobile}' => 'view',
            'GET view' => 'view',
            'POST' => 'create',
        ],
    ],
    [
        'class' => 'yii\rest\UrlRule',
        'controller' => ['v4/servicecategory'],
        'extraPatterns' => [
            'GET list' => 'list',
            'GET childs' => 'childs',
        ],

    ],
    [
        'class' => 'yii\rest\UrlRule',
        'controller' => ['v4/adv'],
        'except' => ['delete', 'update', 'index'],
//        'extraPatterns' => [
//            'GET list' => 'list',
//        ],

    ],
    [
        'class' => 'yii\rest\UrlRule',
        'controller' => ['v4/pay'],
        'extraPatterns' => [
            'GET index' => 'index',
            'GET view' => 'view',
        ],

    ],
    [
        'class' => 'yii\rest\UrlRule',
        'controller' => ['v4/suggest', 'v4/comment'],
        'except' => ['delete', 'update', 'index','view'],
//        'extraPatterns' => [
//            'GET list' => 'list',
//        ],

    ],
    [
        'class' => 'yii\rest\UrlRule',
        'controller' => ['v4/bankcard'],
        'extraPatterns' => [
            //'GET index' => 'index',
           // 'GET view' => 'view',
            'DELETE delete'=>'delete',
        ],

    ],
    [
        'class' => 'yii\rest\UrlRule',
        'controller' => ['v4/coupon'],
        'except' => ['delete', 'update','view'],
        'extraPatterns' => [
            'GET my-coupons/<mobile>' => 'my-coupons',
        ],

    ],
    [
        'class' => 'yii\rest\UrlRule',
        'controller' => ['v4/pay'],
        'except' => ['delete', 'update','view'],
//        'extraPatterns' => [
//            'GET my-coupons/<mobile>' => 'my-coupons',
//        ],
    ],
    [
        'class' => 'yii\rest\UrlRule',
        'controller' => ['v5/post'],
        'except' => ['delete', 'update','view'],
        'extraPatterns' => [
            'GET forum/<forum_id>' => 'forum',
            'GET comments/<post_id>' => 'comments',
        ],
    ],
    [
        'class' => 'yii\rest\UrlRule',
        'controller' => ['v5/post-comment'],
        'except' => ['delete', 'update','view'],
    ],

   // 'GET category/list'    => 'v4/servicecategories/list',
    //'<controller:\w+>/<mobile:\w+>' => '<controller>/view',

    [
        'class' => 'yii\rest\UrlRule',
        'controller' => ['v4/friend'],
        'extraPatterns' => [
            'GET near' => 'near',
            'GET new-friend' => 'new-friend',
            'POST do-friend' => 'do-friend',
        ],
    ],
    [
        'class' => 'yii\rest\UrlRule',
        'controller' => ['v5/friend'],
        'extraPatterns' => [
            'GET near' => 'near',
        ],
    ],
];