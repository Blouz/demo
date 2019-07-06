<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace console\controllers;

use frontend\models\i500_social\UserOrder;


class OrderController extends Controller
{
    //订单取消
    public function actionCancled()
    {
        $current_time = date("Y-m-d H:i:s",time());
        $time = date("Y-m-d H:i:s",strtotime($current_time ."-15 minute"));
        $res = UserOrder::updateAll(array('status'=>'3'),'create_time<:ct AND pay_status=:ps AND status=:st',array(':ct'=>$time,':ps'=>0,'st'=>0));
        $this->returnJsonMsg('200', $res, Common::C('code', '200'));
    }
    //订单确认收货
    public function actionRecieved()
    {
        $current_time = date("Y-m-d H:i:s",time());
        $time = date("Y-m-d H:i:s",strtotime($current_time ."-7 day"));//7天后到期
        $res = UserOrder::updateAll(array('status'=>'2'),'operation_time<:ct AND pay_status=:ps AND status=:st',array(':ct'=>$time,':ps'=>1,'st'=>5));
        $this->returnJsonMsg('200', $res, Common::C('code', '200'));
    }
}