<?php
/**
 * 商城支付
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/8/25
 */
namespace frontend\modules\v13\controllers;

use common\vendor\alipay\Alipay;
use common\vendor\wxpay\PayApi;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\PrivilegeOrder;

class ShopPayController extends BasePrivilegeController {
    //预加载
    public function beforeAction($action) {
        //处理订单超时关闭问题
        $this->autoTimeOrder();
        return parent::beforeAction($action);
    }
    
    //非余额支付商城订单
    public function actionOrder() {
        //订单编号
        $idsn = RequestHelper::post('idsn', 0, 'trim');
        //1支付宝, 2微信
        $type =  RequestHelper::post('type', 0, 'intval');
        //数据为空
        if (empty($idsn) || !in_array($type, [1,2])) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //回调中供需类型
        $body = "咪邻-商城";
        //查询订单
        $Omodel = PrivilegeOrder::findOne(['order_sn'=>$idsn,'status'=>1,'pay_status'=>0]);
        if (empty($Omodel)) {
            $this->returnJsonMsg('404', [], Common::C('code', '404'));
        }
        $Omodel->pay_method = $type;
        $Omodel->save();
        //价格
        $total = $Omodel->price_all;
        
        $method_arr = [1=>'alipay', 2=>'wxpay'];
        $method = $method_arr[$type];
        //支付参数
        $pay_config = [
            'order_sn'   => $idsn,
            'total'      => $total,
            'subject'    => $body,
            'body'       => $body.$idsn,
            'notify_url' => Common::C('baseUrl').'v13/shop-pay-notify/'.$method.'/1',
        ];
        if ($type == 1) {
            //支付宝类实例化
            $pay = new Alipay($pay_config);
        } else if($type == 2) {
            //微信类实例化
            $pay = new PayApi($pay_config);
        } else {
            $this->returnJsonMsg('404', [], '暂不支持其他支付');
        }
        //获取第三方下单信息
        $pay_info = $pay->unifiedOrder();
        if (empty($pay_info)) {
            $this->returnJsonMsg('500', [], Common::C('code', '500'));
        }
        
        $data = [
            'order_sn' => $idsn,
            'total'    => $total * 100,//价格(分)
            'info'     => $pay_info,
            'notify_url' => $pay_config['notify_url'],
            'body'       => $body,
        ];
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }

    //余额支付供需订单
    public function actionAccountOrder() {
        //订单编号
        $idsn = RequestHelper::post('idsn', '', 'trim');
        if (empty($idsn)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //支付密码
        $password = RequestHelper::post('password', '', 'trim');
        if (empty($password)) {
            $this->returnJsonMsg('2100', [], Common::C('code', '2100'));
        }
        //支付密码错误
        if(!$this->checkPayPwd($password)){
            $this->returnJsonMsg('607', [], Common::C('code', '607'));
        }
        
        $Omodel = PrivilegeOrder::findOne(['order_sn'=>$idsn,'status'=>1,'pay_status'=>0]);
        if (empty($Omodel)) {
            $this->returnJsonMsg('404', [], Common::C('code', '404'));
        }
        $Omodel->pay_method = 3;//余额
        $Omodel->status = 2;//2待发货
        $Omodel->pay_status = 1;//2已支付
        $Omodel->pay_time = date('Y-m-d H:i:s');
        //价格
        $datalog = [
            'type' => 1,//1需求 2服务
            'idsn' => $Omodel->order_sn,
            'type_txt' => '支付特权商城订单',
            'pay_type' => '余额支付',
        ];
        $this->setTransaction('db_social');
        //余额不足
        if ($this->getUserAccount() < $Omodel->price_all) {
            $this->returnJsonMsg('2030', [], Common::C('code', '2030'));
        }
        //更新用户余额
        $res1 = $this->saveUserChange($this->mobile, -$Omodel->price_all);
        if (empty($res1)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [], Common::C('code','400'));
        }
        //更新订单
        $res2 = $Omodel->save();
        if (empty($res2)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        //添加交易明细
        $res3 = $this->addTradingDetail(4, -$Omodel->price_all, '购买特权商品', $Omodel->mobile, $Omodel->order_sn, $datalog);
        if (empty($res3)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->commitTransaction();
        
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
}