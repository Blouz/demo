<?php
/**
 * 账户余额 支付处理类
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-12-14 上午11:50
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace common\libs;

use common\vendor\alipay\Alipay;
use common\vendor\wxpay\PayApi;
use frontend\models\i500_social\AccountDetail;
use yii\base\Object;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

class Balance extends Object
{
    public $db;
    public $data;
    public $account_table = 'i500_account_detail';
    public $user_table = 'i500_user_basic_info';
    public $order_table = 'i500_user_order';
    public static $water_type = [
        1=>'预约服务',//消费
        2=>'需求担保',//消费
        3=>'退款',
        4=>'生活缴费',
        5=>'充值',
        6=>'提现',
        7=>'系统奖励',
        8=>'红包'
    ];
    public $type = '';
    public $can_amount = 0;
    public $no_amount = 0;
    public $amount = 0;
    public $error;
    //public $pay_method;
    public function __construct($data)
    {
        if (empty($data)) {
            throw new ErrorException("无效的数据");
        }
        $this->data = $data;
        $this->db = \Yii::$app->db_social;
    }

    /**
     * 余额支付实现  成功支付 回调处理订单状态
     * price 支付金额  order_sn 订单号  mobile 支付手机号
     */
    public function unifiedOrder()
    {
        $data = [
            'price'=>$this->data['price'],
            'order_sn'=>$this->data['order_sn'],
            'mobile'=>$this->data['mobile'],
            'extra_info'=>$this->data['pay_info'],
            'pay_method'=>$this->data['pay_method'],
        ];
        //去支付
        $account = new Account($data);
        $account->type = 'reduce';
        $re = $account->orderPay();
        if ($re == false) {
            $this->error = $account->error;
        }
        //推送给服务方 已经支付
        return $re;
    }

    /*
     * 红包支付
     */
    public function unifiedSendMoneyOrder()
    {
        $data = [
            'total'=>$this->data['total'],
            'order_sn'=>$this->data['order_sn'],
            'mobile'=>$this->data['mobile'],
            'extra_info'=>$this->data['pay_info'],
            'pay_method'=>$this->data['pay_method'],
        ];
        //去支付
        $account = new Account($data);
        $account->type = 'reduce';
        $re = $account->SendMoney();
        if ($re == false) {
            $this->error = $account->error;
        }
        //推送给服务方 已经支付
        return $re;
    }
}