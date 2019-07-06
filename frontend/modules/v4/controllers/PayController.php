<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-25 下午2:56
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v4\controllers;

use common\helpers\CurlHelper;
use common\libs\Balance;
use common\libs\Wxpay;
use common\vendor\alipay\Alipay;
use common\vendor\wxpay\lib\WxPayUnifiedOrder;
use common\vendor\wxpay\PayApi;
use frontend\controllers\AuthController;
use frontend\controllers\RestController;
use frontend\models\i500_social\AccountDetail;
use frontend\models\i500_social\Order;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserOrder;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

class PayController extends RestController
{
    public static $pay_method = [
        1=>'alipay',
        2=>'wxpay',
        3=>'account',
    ];
    public $modelClass = 'frontend\models\i500_social\AccountDetail';
    public function actions(){
        $actions = parent::actions();
        unset($actions['delete'],$actions['update']);
        // 使用"prepareDataProvider()"方法自定义数据provider
        //$actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];

        return $actions;
    }

    /**
     * 充值
     * @return array
     */
    public function actionRecharge()
    {
        $total = RequestHelper::post('total', 0);
        $type =  RequestHelper::post('type', 0);//1 支付宝 2 微信
        $mobile =  RequestHelper::post('mobile', 0);//1 支付宝 2 微信
//创建订单号
        $order_sn = Common::createSn(35, $mobile);
        //$order_sn = time().rand(1000,9999);
        //$order_sn = time();
        if (empty($total) || empty($mobile) || !in_array($type, [1,2])) {
            $this->result['code'] = 422;
            $this->result['message'] = '数据不合法';
            return $this->result;
        }

        if (empty($order_sn)) {
            $this->result['code'] = 501;
            $this->result['message'] = 'channel网络繁忙';
            return $this->result;
        }
        $body = "i500m账户充值";
//
        $model = new AccountDetail();
        $model->mobile = $mobile;
        $model->order_sn = $order_sn;
        $model->create_time = date("Y-m-d H:i:s");
        $model->price = $total;
        $model->type = 5;
        $model->status = 0;
        $model->pay_method = $type;
        $model->remark = $body;
        $re = $model->save(false);
        if ($re) {
            $method = ($type == 1) ? 'alipay' :'wx';
            $pay_config = [
                'order_sn'=>$order_sn,
                'total'=>$total,
                'subject'=>$body,
                'body'=>$body,
                'notify_url'=>Common::C('baseUrl').'v4/notify/'.$method.'/1',
            ];
            if ($type == 1) {
                $pay = new Alipay($pay_config);
            } else if($type == 2) {
                $pay = new PayApi($pay_config);
            }
           // $this->result['data'] = ['order_sn'=>$order_sn,'notify_url'=>Common::C('baseUrl').'v4/notify/alipay/1'];
            $pay_info = $pay->unifiedOrder();

            if (!empty($pay_info)) {
                $this->result['data'] = ['order_sn'=>$order_sn, 'notify_url'=>$pay_config['notify_url'] , 'total'=>$total * 100, 'mobile'=>$mobile, 'info'=>$pay_info];
                return $this->result;
            } else {
                $this->result['code'] = 501;
                $this->result['message'] = '网络繁忙';
                return $this->result;
            }

        } else {
            $this->result['code'] = 500;
            $this->result['message'] = '网络繁忙';
            return $this->result;
        }

    }
    /**
     * 余额支付
     */
    public function actionBalance()
    {
        $mobile = RequestHelper::post('mobile', 0);
        $order_sn =  RequestHelper::post('order_sn', 0);//1 支付宝 2 微信
        if (empty($mobile) || empty($order_sn)) {
            $this->result['code'] = 422;
            $this->result['message'] = '数据不合法';
            return $this->result;
        }
        $map = ['mobile'=>$mobile, 'order_sn'=>$order_sn];
        $model = UserOrder::findOne($map);
        $user_model = UserBasicInfo::findOne(['mobile'=>$mobile]);
        $user_total = 0;
        if (!empty($user_model)) {
            $user_total = $user_model->no_amount + $user_model->can_amount;
        }
        if (!empty($model)) {
            if ($user_total >= $model->total) {

            }
        } else {
            $this->result['code'] = 422;
            $this->result['message'] = '无效的订单号';
            return $this->result;
        }
        $model->total;
    }

    /**
     * 订单支付
     * mobile  手机号
     * type 1 支付宝 2 微信 3 余额
     * order_sn  订单号
     * order_type 订单类型 1 服务订单 2 需求订单
     */
    public function actionPayOrder()
    {
        $type =  RequestHelper::post('type', 0);//1 支付宝 2 微信 3余额
        $mobile =  RequestHelper::post('mobile', 0);//1 支付宝 2 微信
        $order_sn =  RequestHelper::post('order_sn', 0);//1 支付宝 2 微信
        $order_type =  RequestHelper::post('order_type', 0);//1 充值 2 需求服务 3 便利店
        //$order_type =  RequestHelper::post('order_type', 0);//1 支付宝 2 微信
        //$order_sn =  RequestHelper::post('total', 0);//1 支付宝 2 微信

        if (empty($mobile) || !in_array($type, [1,2,3]) || empty($order_sn) || !in_array($order_type, [1, 2, 3])) {
            $this->result['code'] = 422;
            $this->result['message'] = '数据不合法';
            return $this->result;
        }
        $map = ['order_sn'=>$order_sn];
        if ($order_type == 3) {
            $order = Order::findOne($map);
            $title = $body = '便利店订单支付';
        } else {
            $order = UserOrder::findOne($map);
            $title = $body = ($order->order_type == 1) ? '服务订单支付' :'需求订单支付';
        }

        if (empty($order)) {
            $this->result['code'] = 404;
            $this->result['message'] = '订单不存在';
            return $this->result;
        }
        //var_dump($order);exit();
        //三者都不为空 则已经支付
        if ($order->pay_status == 1 && !empty($order->pay_method)) {
            $this->result['code'] = 422;
            $this->result['message'] = '已经支付,请勿重复支付';
            return $this->result;
        }
        $method = self::$pay_method[$type];
        //$method = ($type == 1) ? 'alipay' :'wx';
        //$order_info = json_decode($order->order_info, true);

        //$body = "i500m账户充值";
        $pay_config = [
            'order_sn'=>$order_sn,
            'total'=>$order->total,
            'subject'=>$title,
            'body'=>$body,
            'notify_url'=>Common::C('baseUrl').'v4/notify/'.$method.'/'.$order_type,
        ];
//var_dump($pay_config);exit();
        if ($type == 1) {
            $pay = new Alipay($pay_config);
        } else if($type == 2) {
            $pay = new PayApi($pay_config);
        } else if ($type == 3) {//余额支付
            if ($order_type == 3) {
                $this->result['code'] = 422;
                $this->result['message'] = '便利店暂不支持余额支付';
                return $this->result;
            }
            $pay_config = [
                'order_sn'=>$order_sn,
                'price'=>$order->total,
                'mobile'=>$mobile,
               // 'remark'=>'预约服务订单支付',
                'notify_url'=>'',
                'pay_info'=>json_encode(['pay_method'=>'account', 'pay_time'=>date("Y-m-d H:i:s")]),
                'pay_method'=>$method,
            ];
            $pay = new Balance($pay_config);
            //$pay->pay_method = $method;
        }
        // $this->result['data'] = ['order_sn'=>$order_sn,'notify_url'=>Common::C('baseUrl').'v4/notify/alipay/1'];
        $info = $pay->unifiedOrder();
        //$pay_info = $pay->goPay();
        if ($info == false) {
            $this->result['code'] = 422;
            $this->result['message'] = $pay->error;
        } else {
            $pay_config['info'] = $info;
            $this->result['data'] = $pay_config;
        }


        return $this->result;

    }
}