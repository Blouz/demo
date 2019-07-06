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

use common\vendor\alipay\Alipay;
use common\vendor\wxpay\PayApi;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\UserTradingWithdrawal;
use frontend\models\i500_social\ShouldDemand;
use frontend\models\i500_social\ShouldSupplyOrder;
use frontend\models\i500_social\UserCertification;
use frontend\models\i500_social\UserBankcardNew;

class PayController extends BaseShouldController {
    //余额充值
    public function actionRecharge() {
        //金额
        $total = RequestHelper::post('total', 0, 'floatval');
        $total = $this->returnPrice($total);
        //-1支付宝, -2微信, >0为银行卡id
        $type =  RequestHelper::post('type', 0, 'intval');
        //数据为空
        if (empty($total) || empty($type)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //充值最小10元
        if ($total<10) {
            $this->returnJsonMsg('2034', [], Common::C('code', '2034'));
        }
        //提现充值流程
        $model = new UserTradingWithdrawal();
        //验证支付类型（当前仅支持:支付宝,微信,银行卡）
        $bank_info = null;
        if (!in_array($type, [-1,-2])) {
            $bank_info = UserBankcardNew::findOne(['id'=>$type,'mobile'=>$this->mobile]);
            //非本人银行卡
            if (empty($bank_info)) {
                $this->returnJsonMsg('403', [], Common::C('code', '403'));
            }
            //银行卡支付需要支付密码
            $password = RequestHelper::post('password', '', 'trim');
            if (empty($password)) {
                $this->returnJsonMsg('511', [], Common::C('code', '511'));
            }
            //密码错误
            if (!$this->checkPayPwd($password)) {
                $this->returnJsonMsg('607', [], Common::C('code', '607'));
            }
            $model->bank_number = $bank_info->bank_number;
            $model->bank_type = $bank_info->bank_type;
            $model->bank_belong = $bank_info->bank_belong;
        }
        $model->idsn = $this->getIdsn('TC');
        $model->uid = $this->uid;
        $model->mobile = $this->mobile;
        $model->type = 1;//1充值 2提现
        $model->pay_type = ($type==-1) ? 1 : (($type==-2) ? 2 : 3);//1支付宝 2微信 3银行卡
        $model->price = $total;
        $model->status = 1;//1未完成 2已完成
        
        $res = $model->save();
        if (empty($res)) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        
        $body = "咪邻-账户充值";
        $method = ($type==-1) ? 'alipay' : (($type==-2) ? 'wxpay' : '银行卡');
        //支付参数
        $pay_config = [
            'order_sn'   => $model->idsn,
            'total'      => $total,
            'subject'    => $body,
            'body'       => $body.$model->idsn,
            'notify_url' => Common::C('baseUrl').'v12/pay-notify/'.$method.'/1',
        ];
        if ($type == -1) {
            //支付宝类实例化
            $pay = new Alipay($pay_config);
        } else if($type == -2) {
            //微信类实例化
            $pay = new PayApi($pay_config);
        } else {
            $pay_config['bmobile'] = $bank_info->bmobile;
            $pay_config['bank_number'] = $bank_info->bank_number;
            //银行卡类实例化
            $pay = null;
            $this->returnJsonMsg('404', [], '暂不支持银行卡支付');
        }
        //获取第三方下单信息
        $pay_info = $pay->unifiedOrder();
        if (empty($pay_info)) {
            $this->returnJsonMsg('500', [], Common::C('code', '500'));
        }
        
        $data = [
            'order_sn'   => $model->idsn,
            'total'      => $total * 100,
            'info'       => $pay_info,
            'notify_url' => $pay_config['notify_url'],
            'body'       => $body,
        ];
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
    
    //非余额支付供需订单
    public function actionShouldOrder() {
        //订单编号
        $idsn = RequestHelper::post('idsn', 0, 'trim');
        //-1支付宝, -2微信, >0为银行卡id
        $type =  RequestHelper::post('type', 0, 'intval');
        //数据为空
        if (empty($idsn) || empty($type)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //验证数据（订单编号:JD或YD开头）（当前支付仅支持:支付宝,微信）
        if (!in_array(substr($idsn,0,2), ['JD','YD'])) {
            $this->returnJsonMsg('403', [], Common::C('code', '403'));
        }
        //验证支付类型（当前仅支持:支付宝,微信,银行卡）
        $bank_info = null;
        if (!in_array($type, [-1,-2])) {
            $bank_info = UserBankcardNew::findOne(['id'=>$type,'mobile'=>$this->mobile]);
            //非本人银行卡
            if (empty($bank_info)) {
                $this->returnJsonMsg('403', [], Common::C('code', '403'));
            }
            //银行卡支付需要支付密码
            $password = RequestHelper::post('password', '', 'trim');
            if (empty($password)) {
                $this->returnJsonMsg('511', [], Common::C('code', '511'));
            }
            //密码错误
            if (!$this->checkPayPwd($password)) {
                $this->returnJsonMsg('607', [], Common::C('code', '607'));
            }
        }
        //用户未认证认证信息
        if (!$this->checkUserCert()) {
            $this->returnJsonMsg('2106', [], Common::C('code', '2106'));
        }
        $pay_type_arr = [-1=>1, -2=>2];
        //1支付宝 2微信 3银联 4余额
        $pay_type = isset($pay_type_arr[$type]) ? $pay_type_arr[$type] : 3;
        //根据订单编号前两位判断订单所属
        $total = 0;
        //回调中供需类型
        $mtt = 0;
        $body = "咪邻-支付";
        switch (substr($idsn,0,2)) {
            //需求服务
            case 'JD':
                $body = "咪邻-需求支付";
                $mtt = 2;
                $Omodel = ShouldDemand::findOne(['idsn'=>$idsn,'pay_status'=>1]);
                if (empty($Omodel)) {
                    $this->returnJsonMsg('404', [], Common::C('code', '404'));
                }
                $Omodel->pay_type = $pay_type;
                $Omodel->save();
                //价格
                $total = $Omodel->price;
                break;
            //供求服务
            case 'YD':
                $body = "咪邻-服务支付";
                $mtt = 3;
                $Omodel = ShouldSupplyOrder::findOne(['idsn'=>$idsn,'pay_status'=>1]);
                if (empty($Omodel)) {
                    $this->returnJsonMsg('404', [], Common::C('code', '404'));
                }
                $Omodel->pay_type = $pay_type;
                $Omodel->save();
                //价格
                $total = $Omodel->price_all;
                break;
        }
        
        $method_arr = [-1=>'alipay', -2=>'wxpay'];
        $method = isset($method_arr[$type]) ? $method_arr[$type] : '银行卡';
        //支付参数
        $pay_config = [
            'order_sn'   => $idsn,
            'total'      => $total,
            'subject'    => $body,
            'body'       => $body.$idsn,
            'notify_url' => Common::C('baseUrl').'v12/pay-notify/'.$method.'/'.$mtt,
        ];
        if ($type == -1) {
            //支付宝类实例化
            $pay = new Alipay($pay_config);
        } else if($type == -2) {
            //微信类实例化
            $pay = new PayApi($pay_config);
        } else {
            $pay_config['bmobile'] = $bank_info->bmobile;
            $pay_config['bank_number'] = $bank_info->bank_number;
            //银行卡类实例化
            $pay = null;
            $this->returnJsonMsg('404', [], '暂不支持银行卡支付');
        }
        //获取第三方下单信息
        $pay_info = $pay->unifiedOrder();
        if (empty($pay_info)) {
            $this->returnJsonMsg('500', [], Common::C('code', '500'));
        }
        
        $data = [
            'order_sn' => $idsn,
            'total'    => $total * 100,
            'info'     => $pay_info,
            'notify_url' => $pay_config['notify_url'],
            'body'       => $body,
        ];
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }

    //余额支付供需订单
    public function actionAccountShouldOrder() {
        //订单编号
        $idsn = RequestHelper::post('idsn', '', 'trim');
        if (empty($idsn)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //验证数据（订单编号:JD或YD开头）
        if (!in_array(substr($idsn,0,2), ['JD','YD'])) {
            $this->returnJsonMsg('403', [], Common::C('code', '403'));
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
        
        $Omodel = null;
        $total = 0;
        $title = '';
        $datalog = [];
        switch (substr($idsn,0,2)) {
            //需求服务
            case 'JD':
                $Omodel = ShouldDemand::findOne(['idsn'=>$idsn,'pay_status'=>1]);
                if (empty($Omodel)) {
                    $this->returnJsonMsg('404', [], Common::C('code', '404'));
                }
                $Omodel->pay_type = 4;//余额
                $Omodel->status = 2;//2待接单
                $Omodel->pay_status = 2;//2已支付
                $Omodel->pay_time = date('Y-m-d H:i:s');
                //价格
                $total = $Omodel->price;
                $title = $Omodel->title;
                $datalog = [
                    'type' => 1,//1需求 2服务
                    'idsn' => $idsn,
                    'type_txt' => '支付需求赏金担保',
                    'pay_type' => '余额支付',
                ];
                break;
            //供求服务
            case 'YD':
                $Omodel = ShouldSupplyOrder::findOne(['idsn'=>$idsn,'pay_status'=>1]);
                if (empty($Omodel)) {
                    $this->returnJsonMsg('404', [], Common::C('code', '404'));
                }
                $Omodel->pay_type = 4;//余额
                $Omodel->status = 3;//3待接单
                $Omodel->pay_status = 2;//2已支付
                $Omodel->pay_time = date('Y-m-d H:i:s');
                $Omodel->aotu_offb_time = date('Y-m-d H:i:s', strtotime('+6 hour'));
                //价格
                $total = $Omodel->price_all;
                $title = $Omodel->title;
                $datalog = [
                    'type' => 2,//1需求 2服务
                    'idsn' => $idsn,
                    'type_txt' => '服务消费',
                    'dmobile' => $Omodel->dmobile,
                    'pay_type' => '余额支付',
                ];
                break;
        }
        $this->setTransaction('db_social');
        //获取用户认证信息对象
        $UCinfo = UserCertification::findOne(['mobile'=>$this->mobile,'status'=>2]);
        if (empty($UCinfo)) {
            $this->returnJsonMsg('1061', [], Common::C('code', '1061'));
        }
        //余额不足
        if ($UCinfo->change < $total) {
            $this->returnJsonMsg('2030', [], Common::C('code', '2030'));
        }
        //更新用户余额
        $res1 = $this->saveUserChange($this->mobile, -$total);
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
        //服务推送
        if (substr($idsn,0,2)=='YD') {
            //消息推送
            $this->pushToAppSupply($Omodel->id);
        }
        //添加交易明细
        $res3 = $this->addTradingDetail(4, -$total, $title, $this->mobile, $idsn, $datalog);
        if (empty($res3)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->commitTransaction();
        
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
}