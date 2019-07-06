<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-25 下午4:17
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v4\controllers;

use common\helpers\CurlHelper;
use common\libs\Account;
use Yii;
use common\vendor\wxpay\PayApi;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use common\helpers\Common;
use common\helpers\RequestHelper;
use yii\web\Response;
use frontend\models\i500_social\UserOrder;
use frontend\models\i500_social\User;
use frontend\models\i500_social\SendMoney;
class NotifyController extends Controller
{
    public $enableCsrfValidation = false;
    public $result = ['code'=>200, 'data'=>[], 'message'=>'OK'];
    public function init()
    {
        parent::init();
        Yii::$app->response->format = Response::FORMAT_JSON;
    }
    protected function response()
    {
        return $this->result;
    }
    public function actionIndex()
    {
        file_put_contents('/tmp/new_txt.log',  "执行时间：".date('Y-m-d H:i:s')." 回调来了\n", FILE_APPEND);
        $method = RequestHelper::get('method', '');//支付方式
        $type = RequestHelper::get('type', 0, 'intval');//1 充值回调  2 消费支付回调 3 便利店 8红包
        $method_type = [
            'alipay'=>'AlipayNotify',
            'wx'=>'WxNotify',
            'wxpay'=>'WxNotify',
        ];
        if (! in_array($method, ['wx','wxpay', 'alipay'])) {
            $this->result['code'] = 601;
            $this->result['message'] = '无效的支付方式';
            return $this->result;
        }
        $class = $method_type[$method];
        if (!in_array($type, [1, 2, 3, 8])) {
            $this->result['code'] = 601;
            $this->result['message'] = '无效的类型';
            return $this->result;
        }
        $class = 'common\libs'.'\\'.$class;
        $notify = Yii::createObject([
            'class'=>$class::className(),
            'type'=>'callback',
        ]);
        //echo '<pre>';var_dump($notify);exit;
        $result = $notify->handle();
        if(!empty($result)) {
            $extra_info = [
                'buyer_email'=>ArrayHelper::getValue($result, 'buyer_email', ''),
                'pay_time'=>ArrayHelper::getValue($result, 'notify_time', ''),
                'pay_method'=>$method,
                'trade_no'=>ArrayHelper::getValue($result, 'trade_no', ''),
            ];
            if ($type == 1) {//充值
                $data = [
                    'order_sn'=>$result['out_trade_no'],
                    'pay_info'=>json_encode($extra_info),
                    //'type'=>5,
                ];
                $Account = new Account($data);
                //$Account->type = 'add';
                $re_account = $Account->recharge();
                if ($re_account) {
                    $notify->HandleReply(true);
                } else {
                    $notify->HandleReply(false);
                }
            } else if(in_array($type, [2, 3])) {//消费回调
                //修改订单状态
                //记录交易明细
                $data = [
                    'order_sn'=>$result['out_trade_no'],
                    'pay_method'=>$method,
                    'pay_time'=>ArrayHelper::getValue($result, 'gmt_payment', ''),
                    'pay_info'=>json_encode($extra_info),
                ];
                $Account = new Account($data);
                $re = $Account->orderPay($type);
                if ($re) {
                    $notify->HandleReply(true);
                } else {
                    $notify->HandleReply(false);
                }
            } else if($type == 8) {//红包

                $data = [
                    'order_sn'=>$result['out_trade_no'],
                    'pay_method'=>$method,
                ];
                file_put_contents('/tmp/new_txt.log',  "执行时间：".date('Y-m-d H:i:s')."支付方式".$method, FILE_APPEND);
                $Account = new Account($data);
                $re = $Account->SendMoney();
                if ($re) {
                    $notify->HandleReply(true);
                } else {
                    $notify->HandleReply(false);
                }
            }
        } else {
            $notify->HandleReply(false);
        }
        file_put_contents('hongbao.log',  "执行时间：".date('Y-m-d H:i:s')." 处理完毕\n", FILE_APPEND);
    }
}
