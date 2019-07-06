<?php
/**
 * 支付
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/6/21
 */
namespace frontend\modules\v12\controllers;

use Yii;
use common\helpers\Common;
use yii\helpers\ArrayHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\UserTradingWithdrawal;
use frontend\models\i500_social\ShouldDemand;
use frontend\models\i500_social\ShouldSupplyOrder;

class PayNotifyController extends BaseShouldController {
    //支付回调
    public function actionIndex() {
        file_put_contents('/tmp/new_txt.log',  "执行时间：".date('Y-m-d H:i:s')." a回调来了\n", FILE_APPEND);
        file_put_contents('/tmp/new_txt.log',  "执行时间：".date('Y-m-d H:i:s')." b打印GET参数:".var_export($_GET, true)."\n", FILE_APPEND);
        file_put_contents('/tmp/new_txt.log',  "执行时间：".date('Y-m-d H:i:s')." c打印POST参数:".var_export($_POST, true)."\n", FILE_APPEND);
        $_PUT = null;if (is_null($_PUT)) {parse_str(file_get_contents('php://input'), $_PUT);}
        file_put_contents('/tmp/new_txt.log',  "执行时间：".date('Y-m-d H:i:s')." d打印PUT参数:".var_export($_PUT, true)."\n", FILE_APPEND);
        //支付方式
        $method = RequestHelper::get('method', '', 'trim');
        $method = empty($method) ? RequestHelper::post('method', '', 'trim') : $method;
        $method_type = [
            'alipay' => 'AlipayNotify',
            'wxpay'  => 'WxNotify',
        ];
        //支付方式错误
        if (!in_array($method, array_keys($method_type))) {
            $this->returnJsonMsg('403', [], Common::C('code', '403'));
        }
        $pay_name = ($method=='alipay')?'支付宝':($method=='wxpay')?'微信':'';
        $pay_type = ($method=='alipay')?1:($method=='wxpay')?2:3;
        //1余额充值,2需求支付,3供求支付
        $type = RequestHelper::get('type', 0, 'intval');
        $type = empty($type) ? RequestHelper::post('type', 0, 'intval') : $type;
        //支付类型错误
        if (!in_array($type, [1,2,3])) {
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
        switch ($type) {
            //余额充值
            case 1:
                //获取充值流程对象
                $UTinfo = UserTradingWithdrawal::findOne(['idsn'=>$extra_info['out_trade_no'],'status'=>1]);
                if (empty($UTinfo)) {
                    $notify->HandleReply(false);
                }
                $this->setTransaction('db_social');
                //更新流水订单
                $UTinfo->actual_time = date('Y-m-d H:i:s');
                $UTinfo->serial_number = $extra_info['trade_no'];
                $UTinfo->status = 2;//1未完成 2已完成
                $res1 = $UTinfo->save();
                if (empty($res1)) {
                    $this->backTransaction();
                    $notify->HandleReply(false);
                }
                $datalog = [
                    'idsn' => $UTinfo->idsn,
                    'type_txt' => '充值',
                    'pay_type' => $pay_name,
                    'pay_sn' => $UTinfo->serial_number,
                ];
                //添加交易明细
                $res2 = $this->addTradingDetail(3, $UTinfo->price, '充值', $UTinfo->mobile, $UTinfo->idsn, $datalog);
                if (empty($res2)) {
                    $this->backTransaction();
                    $notify->HandleReply(false);
                }
                //更新用户余额
                $res3 = $this->saveUserChange($UTinfo->mobile, $UTinfo->price);
                if (empty($res3)) {
                    $this->backTransaction();
                    $notify->HandleReply(false);
                }
                
                $this->commitTransaction();
                $notify->HandleReply(true);
                break;
            //需求支付
            case 2:
                $Omodel = ShouldDemand::findOne(['idsn'=>$extra_info['out_trade_no'],'pay_status'=>1]);
                if (empty($Omodel)) {
                    $notify->HandleReply(false);
                }
                $this->setTransaction('db_social');
                $Omodel->pay_type = $pay_type;//1支付宝 2微信 3银联 4余额
                $Omodel->status = 2;//2待接单
                $Omodel->pay_status = 2;//2已支付
                $Omodel->pay_sn = $extra_info['trade_no'];
                $Omodel->pay_time = date('Y-m-d H:i:s');
                //更新订单
                $res1 = $Omodel->save();
                if (empty($res1)) {
                    $this->backTransaction();
                    $notify->HandleReply(false);
                }
                $datalog = [
                    'type' => 1,//1需求 2服务
                    'idsn' => $Omodel->idsn,
                    'type_txt' => '支付需求赏金担保',
                    'pay_type' => $pay_name,
                    'pay_sn' => $Omodel->pay_sn,
                ];
                //添加交易明细
                $res2 = $this->addTradingDetail(4, -$Omodel->price, $Omodel->title, $Omodel->mobile, $Omodel->idsn, $datalog);
                if (empty($res2)) {
                    $this->backTransaction();
                    $notify->HandleReply(false);
                }
                
                $this->commitTransaction();
                $notify->HandleReply(true);
                break;
            //供求支付
            case 3:
                $Omodel = ShouldSupplyOrder::findOne(['idsn'=>$extra_info['out_trade_no'],'pay_status'=>1]);
                if (empty($Omodel)) {
                    $notify->HandleReply(false);
                }
                $this->setTransaction('db_social');
                $Omodel->pay_type = $pay_type;//1支付宝 2微信 3银联 4余额
                $Omodel->status = 3;//3待接单
                $Omodel->pay_status = 2;//2已支付
                $Omodel->pay_sn = $extra_info['trade_no'];
                $Omodel->pay_time = date('Y-m-d H:i:s');
                $Omodel->aotu_offb_time = date('Y-m-d H:i:s', strtotime('+6 hour'));
                //更新订单
                $res1 = $Omodel->save();
                if (empty($res1)) {
                    $this->backTransaction();
                    $notify->HandleReply(false);
                }
                //消息推送
                $this->pushToAppSupply($Omodel->id);
                $datalog = [
                    'type' => 2,//1需求 2服务
                    'idsn' => $Omodel->idsn,
                    'type_txt' => '服务消费',
                    'pay_type' => $pay_name,
                    'pay_sn' => $Omodel->pay_sn,
                    'dmobile' => $Omodel->dmobile,
                ];
                //添加交易明细
                $res2 = $this->addTradingDetail(4, -$Omodel->price_all, $Omodel->title, $Omodel->mobile, $Omodel->idsn ,$datalog);
                if (empty($res2)) {
                    $this->backTransaction();
                    $notify->HandleReply(false);
                }
                
                $this->commitTransaction();
                $notify->HandleReply(true);
                break;
        }
        exit;
    }
}
