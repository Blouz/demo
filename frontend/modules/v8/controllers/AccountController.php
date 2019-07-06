<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\modules\v8\controllers;

use common\helpers\CurlHelper;
use common\libs\Balance;
use frontend\models\i500_social\AccountDetail;
use frontend\models\i500_social\Order;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

class AccountController extends BaseController
{
    public function actionAccountDetail()
    {
        $mobile=RequestHelper::post('mobile', '', ''); 
        if(empty($mobile))
        {
            $this->returnJsonMsg('1600',[], '手机号不能为空');	
		}
        $result = AccountDetail::find()->select(['id','type','price','amount','order_sn','create_time'])
									   ->where(['status'=>1])
									   ->andWhere(['mobile'=>$mobile])
									   ->orderBy('id desc')
									   ->asArray()
									   ->all();
        $this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));	
    }
}