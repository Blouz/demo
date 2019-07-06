<?php
/**
 * 商城支付回调
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/8/25
 */
namespace frontend\modules\v13\controllers;

use Yii;
use common\helpers\Common;
use yii\helpers\ArrayHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\PrivilegeOrder;

class ShopPayNotifyController extends BasePrivilegeController {
    //支付回调
    public function actionIndex() {
        file_put_contents('/tmp/new_txt.log',  "执行时间：".date('Y-m-d H:i:s')." a回调来了\n", FILE_APPEND);
        //支付方式  1支付宝, 2微信
        $method = RequestHelper::get('method', '', 'trim');
        $method_type = [
            'alipay' => 'AlipayNotify',
            'wxpay'  => 'WxNotify',
        ];
        $pay_name = [
            'alipay' => '支付宝',
            'wxpay'  => '微信',
        ];
        $pay_type = [
            'alipay' => 1,
            'wxpay'  => 2,
        ];
        //支付方式错误
        if (!isset($method_type[$method])) {
            $this->returnJsonMsg('403', [], Common::C('code', '403'));
        }
        //实例化支付类
        $class = 'common\libs'.'\\'.$method_type[$method];
        $notify = Yii::createObject([
            'class'=>$class::className(),
            'type'=>'callback',
        ]);
        $result = $notify->handle();
        file_put_contents('/tmp/new_txt.log',  "执行时间：".date('Y-m-d H:i:s')." e打印回调参数:".var_export($result, true)."\n", FILE_APPEND);
        if(empty($result)) {
            $notify->HandleReply(false);
        }
        //获取回调信息
        $extra_info = [
            //支付流水号
            'trade_no'     => ArrayHelper::getValue($result, 'trade_no', ''),
            //商户订单号
            'out_trade_no' => ArrayHelper::getValue($result, 'out_trade_no', ''),
        ];
        //商城支付
        $Omodel = PrivilegeOrder::findOne(['order_sn'=>$extra_info['out_trade_no'],'pay_status'=>0]);
        if (empty($Omodel)) {
            $notify->HandleReply(false);
        }
        $this->setTransaction('db_social');
        $Omodel->pay_method = $pay_type[$method];//1支付宝 2微信 3余额
        $Omodel->status = 2;//2待发货
        $Omodel->pay_status = 1;//2已支付
        $Omodel->pay_sn = $extra_info['trade_no'];
        $Omodel->pay_time = date('Y-m-d H:i:s');
        //更新订单
        $res1 = $Omodel->save();
        if (empty($res1)) {
            $this->backTransaction();
            $notify->HandleReply(false);
        }
        $datalog = [
            'type' => 3,//1需求 2服务 3商城
            'idsn' => $Omodel->order_sn,
            'type_txt' => '支付特权商城订单',
            'pay_type' => $pay_name[$method],
            'pay_sn' => $Omodel->pay_sn,
        ];
        //添加交易明细
        $res2 = $this->addTradingDetail(4, -$Omodel->price_all, '购买特权商品', $Omodel->mobile, $Omodel->order_sn, $datalog);
        if (empty($res2)) {
            $this->backTransaction();
            $notify->HandleReply(false);
        }
        
        $this->commitTransaction();
        $notify->HandleReply(true);
        exit;
    }
}
