<?php

namespace frontend\modules\rewrite\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use yii\helpers\ArrayHelper;

class TestabcController extends UserBaseController
{
    public $actions = array(
		'0'=>'',
		'1'=>['info'],
		'2'=>['haha']
	);

    public function beforeAction($action){  
        $user_actions = $this->actions[$this->community_info['is_pioneer']];
        if ( !in_array($action->id, $user_actions)) {
            $this->returnJsonMsg(403, [], Common::C('coderewrite', '403'));
        }
        return parent::beforeAction($action);
    }


    public function actionInfo()
    {   

        echo 111;
    }


    public function actionHaha()
    {   
        echo 222;
    }
}