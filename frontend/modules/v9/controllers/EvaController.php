<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\modules\v9\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\ServiceOrderEvaluation;
use frontend\models\i500_social\UserOrderDetail;
use frontend\models\i500_social\UserOrder;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\db\Query;
/**
 * Service
 *
 * @category Social
 * @package  Service

 */
class EvaController extends BaseController
{
    public function actionIndex()
    {
        $sid = RequestHelper::post('sid', '', '');
        if (empty($sid)) {
            $this->returnJsonMsg('8900',[], '服务id不能为空');
        }
        $field[] = "i500_service_order_evaluation.id";
        $field[] = "i500_service_order_evaluation.star";
        $field[] = "i500_service_order_evaluation.content";
        $field[] = "i500_service_order_evaluation.create_time";
        $field[] = "i500_user_basic_info.nickname as nickname";
        $field[] = "i500_user_basic_info.avatar as avatar";
        
        $condition[ServiceOrderEvaluation::tableName().'.type'] = 1;
        $condition[UserOrderDetail::tableName().'.sid'] = $sid;
        $result = ServiceOrderEvaluation::find()->select($field)
                                                ->join('LEFT JOIN','i500_userorder_detail','i500_userorder_detail.order_sn=i500_service_order_evaluation.order_sn')
                                                ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_service_order_evaluation.mobile')
                                                ->where($condition)
//                 ->createCommand()->getRawSql();
                                                ->asArray()
                                                ->all();
         $this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));	
    }
    public function actionPub()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('8901',[], '登录手机号不能为空');
        }
        $order_sn = RequestHelper::post('order_sn', '', '');
        if (empty($order_sn)) {
            $this->returnJsonMsg('8902',[], '订单号不能为空');
        }
        $star = RequestHelper::post('star', '', '');
        if (empty($star)) {
            $this->returnJsonMsg('8903',[], '星评不能为空');
        }
        $content = RequestHelper::post('content', '', '');
        if (empty($content)) {
            $this->returnJsonMsg('8904',[], '服务id不能为空');
        }
        $type = RequestHelper::post('type', '', '');
        if (empty($type)) {
            $this->returnJsonMsg('8905',[], '标识 1=体验方 2=服务方，不能为空');
        }
        
        $evaluation = new ServiceOrderEvaluation();
        $evaluation->mobile = $mobile;
        $evaluation->order_sn = $order_sn;
        $evaluation->star = $star;
        $evaluation->content = $content;
        $evaluation->type = $type;
        
        $result = $evaluation->save(false);
        if($result)
        {
            UserOrder::updateAll(['user_comment_status'=>1],['order_sn'=>$order_sn]);
        }
        $this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));
    }
    
    public function actionImg()
    {
        $order_sn = RequestHelper::post('order_sn', '', '');
        if (empty($order_sn)) {
            $this->returnJsonMsg('8901',[], '登录手机号不能为空');
        }
        
        $condition[UserOrderDetail::tableName().'.order_sn'] = $order_sn;

        $result = UserOrderDetail::find()->select(['image'])
                               ->where($condition)
                               ->orderBy('sid')
                               ->asArray()
                               ->all();
        $new_img = array();

        foreach($result as $res)
        {
           $img = json_decode($res['image']);
           $new_img[] = $img[0];
        }
        $this->returnJsonMsg('200', $new_img, Common::C('code','200','data','[]'));
    }
}