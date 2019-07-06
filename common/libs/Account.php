<?php
/**
 * 账户 处理类
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

use common\helpers\Common;
use common\helpers\CurlHelper;
use frontend\models\i500_social\AccountDetail;
use frontend\models\i500_social\SendMoney;
use frontend\models\i500_social\User;
use yii\base\ErrorException;
use yii\base\Object;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use frontend\models\i500_social\UserOrder;
use frontend\models\i500_social\UserBasicInfo;

class Account extends Object
{
    public $db;
    public $data;
    public $account_table = 'i500_account_detail';
    public $user_table = 'i500_user_basic_info';
    public $order_table = 'i500_user_order';
    public $shop_order_table = 'i500_order';
    public $send_money = 'i500_send_money';
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
    public $frozen_amount = 0;
    public $score = 0;
    public $amount = 0;
    public $error;
    public $pay_type;//支付类型 1 支付宝
    public function __construct($data)
    {
        if (empty($data)) {
            throw new ErrorException("无效的数据");
        }
        $this->data = $data;
        $this->db = \Yii::$app->db_social;
//        $command = $this->db->createCommand("select `mobile`,`no_amount`, `can_amount` from ".$this->user_table." where mobile=:mobile");
//        $command->bindValue(":mobile", $this->data['mobile']);
//        $user_info = $command->queryOne();
//        if (!empty($user_info)) {
//            $this->can_amount = $user_info['can_amount'];
//            $this->no_amount = $user_info['no_amount'];
//            $this->amount = $this->no_amount + $this->can_amount;
//        }
    }
    protected function initBalance()
    {
        $command = $this->db->createCommand("select `mobile`,`no_amount`, `can_amount`, `frozen_amount`, `score` from ".$this->user_table." where mobile=:mobile");
        $command->bindValue(":mobile", $this->data['mobile']);
        $user_info = $command->queryOne();
        if (!empty($user_info)) {
            $this->can_amount = $user_info['can_amount'];
            $this->no_amount = $user_info['no_amount'];
            $this->frozen_amount = $user_info['frozen_amount'];
            $this->score = $user_info['score'];
            $this->amount = $this->no_amount + $this->can_amount;
        }
    }

    /**
     * 记录交易明细 如果是账户充值 或者 余额支付 则涉及到余额变动
     * 先消费 不可提现金额 再消费可体现金额
     * 支持字段 mobile type  price remark create_time order_sn status pay_method extra_info
     */
    public function recordAccount()
    {
        $this->getAmount($this->data['price']);
        if (!empty($this->error)) {
            return false;
        }
        $transaction = $this->db->beginTransaction();
        try {
            $this->db->createCommand()->insert($this->account_table, $this->data)->execute();
            $this->db->createCommand()->update($this->user_table,
                [
                    'no_amount'=>$this->no_amount,
                    'can_amount'=>$this->can_amount,
                ],
                ['mobile'=>$this->data['mobile']]
            )->execute();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
        }
        return true;
    }
    /**
     * 提现资金变动  //输入 提现金额 手机号 额外参数（银行卡信息）
     */
    public function withdraw()
    {
        $this->type = 'frozen';
        $re = $this->getAmount($this->data['price']);
        if ($re == false) {
            return false;
        }
        $transaction = $this->db->beginTransaction();
        try {
            $account_data['mobile'] = ArrayHelper::getValue($this->data, 'mobile', '');
            $account_data['type'] = 6;
            $account_data['price'] = -$this->data['price'];
            $account_data['amount'] = $this->amount;
            $account_data['remark'] = 'i500账户提现';
            $account_data['create_time'] = date("Y-m-d H:i:s");
            $account_data['order_sn'] = ArrayHelper::getValue($this->data, 'pay_method', '');
            $account_data['status'] = 1;
            $account_data['pay_method'] = ArrayHelper::getValue($this->data, 'pay_method', '');//$order_info['pay_method'];
            $account_data['extra_info'] = ArrayHelper::getValue($this->data, 'pay_info', '');//$order_info['pay_method'];
            $this->db->createCommand()->insert($this->account_table, $account_data)->execute();
            $this->db->createCommand()->update($this->user_table,
                [
                    'no_amount'=>$this->no_amount,
                    'can_amount'=>$this->can_amount,
                    'frozen_amount'=>$this->frozen_amount,
                ],
                ['mobile'=>$this->data['mobile']]
            )->execute();
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
        }
        return false;
    }
    /**
     * 金额处理
     * @param float $price 消费金额
     * @return bool
     */
    protected function getAmount($price)
    {
        //file_put_contents('/tmp/new_txt.log',  "amount init？：".date('Y-m-d H:i:s')." \n", FILE_APPEND);
        $this->initBalance();//初始化获取账户信息
        if (in_array($this->type, ['add', 'reduce', 'frozen'])) {
            //如果是挣钱 则余额增加
            if ($this->type === 'add') {
                if ($this->data['type'] == 7) {//系统奖励 为不可提现金额
                    $this->no_amount +=$price;
                } else {
                    $this->can_amount +=$price;
                }
            } else if($this->type == 'reduce') {//否则是消费
                //如果不可提现金额大于消费金额 减去不可提现金额
                if ($price > $this->amount) {
                    $this->error = '余额不足';
                    return false;
                }else {
                    if ($this->no_amount >= $price) {
                        $this->no_amount = $this->no_amount - $price;
                    } else {
                        $this->can_amount = $this->can_amount - ($price - $this->no_amount);
                        //$this->can_amount = $this->can_amount - $this->no_amount;
                        $this->no_amount = 0;
                    }
                }
            } else if($this->type == 'frozen') {//如果是提现冻结
                if ($price > $this->can_amount) {
                    $this->error = '超过账户可提现余额';
                    return false;
                } else {
                    $this->can_amount = $this->can_amount - $price;

                    $this->frozen_amount += $price;
                }

            }
        } else {
            $this->error = '消费类型必须为add或reduce';
            return false;
        }
        $this->amount = $this->can_amount + $this->no_amount;
        return true;

    }
    /**
     * 账户充值
     */
    public function recharge()
    {
        $order_sn = $this->data['order_sn'];
        $order_data = $this->getAccountDetail($order_sn);
        if (!empty($order_data)) {
            if($order_data['status'] == 0) {//未处理
                $extra_info = ArrayHelper::getValue($this->data, 'pay_info', '');
                $this->type = 'add';//增加 账户充值
                $this->data['mobile'] = $order_data['mobile'];
                $this->data['type'] = $order_data['type'];
                $this->getAmount($order_data['price']);
                $transaction = $this->db->beginTransaction();
                try {
                    //更新用户余额
                    $this->db->createCommand()->update($this->user_table,
                        [
                            'no_amount'=>$this->no_amount,
                            'can_amount'=>$this->can_amount,
                        ],
                        ['mobile'=>$order_data['mobile']]
                    )->execute();
                    //更新交易明细
                    $this->db->createCommand()->update($this->account_table,
                        [
                            'extra_info'=>$extra_info,
                            'amount'=>$this->amount,
                            'status'=>1,
                        ],
                        ['mobile'=>$order_data['mobile'], 'order_sn'=>$order_sn]
                    )->execute();
                    $transaction->commit();
                    return true;
                } catch (Exception $e) {
                    $transaction->rollBack();
                    return false;
                }
            } else {//防止已经处理的订单 支付宝或者微信又推送了
                return true;
            }
        } else {
            return false;
        }
        //$this->db->createCommand()->insert($this->account_table, $this->data)->execute();
    }
    /**
     * 获取订单详情
     * @param $order_sn
     * @return array
     */
    protected function getOrder($order_sn)
    {
        $command = $this->db->createCommand("select `order_sn`, `status`, `pay_status`, `mobile`, `total`, `order_type`, `order_info`, `pay_method`,`shop_mobile` from ".$this->order_table." where order_sn=:order_sn");
        $command->bindValue(":order_sn", $order_sn);
        $order_data = $command->queryOne();
        //var_dump($order_data);exit();
        if (empty($order_data)) {
            $this->error = '订单不存在';
            return [];
        }
        return $order_data;
    }
    /**
     * 获取便利店订单详情
     * @param $order_sn
     * @return array
     */
    protected function getShopOrder($order_sn)
    {
        $command = $this->db->createCommand("select `order_sn`, `status`, `pay_status`, `mobile`, `total`, `pay_method`,`shop_id` from ".$this->shop_order_table." where order_sn=:order_sn");
        $command->bindValue(":order_sn", $order_sn);
        $order_data = $command->queryOne();
        //var_dump($order_data);exit();
        if (empty($order_data)) {
            $this->error = '订单不存在';
            return [];
        }
        return $order_data;
    }

    /**
     * 获取账户交易明细的订单详情
     * @param $order_sn
     * @return array
     */
    protected function getAccountDetail($order_sn)
    {
        $command = $this->db->createCommand("select * from ".$this->account_table." where order_sn=:order_sn");
        $command->bindValue(":order_sn", $order_sn);
        $order_data = $command->queryOne();
        //var_dump($order_data);exit();
        if (empty($order_data)) {
            $this->error = '订单不存在';
            return [];
        }
        return $order_data;
    }

    /**
     * 消费支付 记录  改动 交易明细 订单状态  支付成功之后调用  回调
     * update pay_status 1 pay_method(wx,alipay) pay_time
     * @param int $order_type  2 服务需求  3 便利店
     * @return bool;
     */
    public function orderPay($order_type = 2)

    {
        $order_sn = ArrayHelper::getValue($this->data, 'order_sn', '');
        $pay_method = ArrayHelper::getValue($this->data, 'pay_method', '');
        if ($order_type == 3) {
            $order_data = $this->getShopOrder($order_sn);
            $remark = '便利店订单';
            $account_data['type'] = 8;
        } else {
            $order_data = $this->getOrder($order_sn);
            $remark = $order_data['order_type'] == 1 ? '预约:':'需求:';
            $account_data['type'] = $order_data['order_type'];
            $order_info = json_decode($order_data['order_info'], true);
            $remark = $remark. ArrayHelper::getValue($order_info, '0.content', '').' 支付';
        }

        if (empty($order_data)) {
            $this->error = '订单不存在';
            return false;
        }
        if ($order_data['pay_status'] == 1) {
            //$this->error = '已经支付,请勿重复支付';
            return true;
        }
        $mobile = ArrayHelper::getValue($order_data, 'mobile', '');

        $transaction = $this->db->beginTransaction();
        try {

            if ($pay_method == 'account') {
                //如果是余额支付 则成功支付后 修改账户资金变动
                $re = $this->getAmount($order_data['total']);
                if ($re == false) {
                   // $this->error
                    $transaction->rollBack();
                    return false;
                }
                $this->db->createCommand()->update($this->user_table,
                    [
                        'no_amount'=>$this->no_amount,
                        'can_amount'=>$this->can_amount,
                    ],
                    ['mobile'=>$mobile]
                )->execute();
                $account_data['amount'] = $this->amount;
            }


            $account_data['mobile'] = $mobile;
            if($pay_method=="alipay")
            {
                $account_data['pay_method'] = "1";
            }
            if($pay_method=="wxpay")
            {
                $account_data['pay_method'] = "2";
            }
            $account_data['price'] = -$order_data['total'];
            $account_data['remark'] = $remark;
            $account_data['create_time'] = date("Y-m-d H:i:s");
            $account_data['order_sn'] = $order_sn;
            $account_data['status'] = 1;
           // $account_data['pay_method'] = ArrayHelper::getValue($this->data, 'pay_method', '');//$order_info['pay_method'];
            $account_data['extra_info'] = ArrayHelper::getValue($this->data, 'extra_info', '');//$order_info['pay_method'];
            $this->db->createCommand()->insert($this->account_table, $account_data)->execute();

            if ($order_type == 3) {
                $this->db->createCommand()->update($this->shop_order_table,
                    [
                        'status'=>1,
                        'pay_status'=>1,
                        'pay_time'=>date("Y-m-d H:i:s"),
                        'pay_method'=>$this->data['pay_method'],
                    ],
                    ['mobile'=>$mobile, 'order_sn'=>$order_sn]
                )->execute();
            } else {
              $paysucess =  $this->db->createCommand()->update($this->order_table,
                    [
                        'status'=>1,
                        'pay_status'=>1,
                        'pay_time'=>date("Y-m-d H:i:s"),
                        'pay_method'=>$this->data['pay_method'],
                    ],
                    ['mobile'=>$mobile, 'order_sn'=>$order_sn]
                )->execute();
            //支付成功后修改余额
              if($paysucess>0)
              {
                $userorder = new UserOrder();
                $field=array();
                $field[] = "i500_user_order.id";
                $field[] = "i500_user_order.shop_mobile";
                $field[] = "i500_user_order.total";
                $field[] = "i500_user_order.pay_status";
                $field[] = "i500_user_basic_info.can_amount as can_amount";

                $condition[UserOrder::tableName().'.order_sn'] = $order_sn;
                $res = $userorder->find()->select($field)
                                    ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_user_order.shop_mobile')
                                    ->where($condition)
                                    ->asArray()
                                    ->one();
                $pstatus = $res['pay_status'];
                $usermobile = $res['shop_mobile'];
                $can_amount = $res['can_amount'];
                $totalprice = $res['total'];
                if($pstatus==1)
                {
                    $amount = $can_amount + $totalprice;

                    $connection = \Yii::$app->db_social;
                    $transaction = $connection->beginTransaction();
                    $current_time = date("Y-m-d H:i:s",time());
                    UserOrder::updateAll(['status'=>4,'operation_time'=>$current_time],['order_sn'=>$order_sn]);
                    $success = UserBasicInfo::updateAll(['can_amount'=>$amount],['mobile'=>$usermobile]);
                    
                    if($success>0)
                    {
                        $account_detail = new AccountDetail();
                        $account_detail->mobile = $usermobile;
                        $account_detail->type = 1;
                        $account_detail->price = $totalprice;
                        $account_detail->amount = $amount;
                        $account_detail->order_sn = $order_sn;
                        $account_detail->status = 1;
                        
                        $account_detail->save(false);
                        $transaction->commit();
                        //获取要推送的channel_id
                        $channel_id = User::find()->select('channel_id')->where(['mobile'=>$usermobile])->scalar();
                        // 推送消息给服务方
                        if (!empty($channel_id)) 
                        {
                            $channel = explode('-', $channel_id);
                            $data['device_type'] = ArrayHelper::getValue($channel, 0);
                            $data['channel_id'] = ArrayHelper::getValue($channel, 1);
                            $data['type'] = 3;//新访客  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论
                            $data['title'] = "您有一个新的订单";
                            $data['description'] = "您有一个新的订单";
                            $channel_url = \Yii::$app->params['channelHost'] . 'v1/push';
                            $re = CurlHelper::post($channel_url, $data);
                        }
                    }
                    else
                    {
                        $transaction->rollBack();
                    }
                }
              }
                
            }
            

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
        }
        try {
            //$channel_mobile = ($order_data['order_type'] == 1) ? $order_data['shop_mobile'] : $order_data['mobile'];
            $channel_id = User::find()->select('channel_id')->where(['mobile'=>$order_data['shop_mobile']])->scalar();
           // file_put_contents('/tmp/push.log', "执行时间：" . date('Y-m-d H:i:s') . " 推送失败 订单数据：" . $channel_id . "\n", FILE_APPEND);

            if (!empty($channel_id)) {
                $channel = explode('-',$channel_id);
                $data['device_type'] = ArrayHelper::getValue($channel, 0);
                $data['channel_id'] = ArrayHelper::getValue($channel, 1);
                //$data['type'] = ($order_data['order_type'] == 1) ? 3 : 4;
                $data['type'] = 3;
                $data['title'] = '您有一个新订单';
                $data['description'] = '您有一个新订单对方已付款 待完成';
                //$data['title'] = '您有一个新订单';
                $channel_url = \Yii::$app->params['channelHost'] . 'v1/push';
                $re = CurlHelper::post($channel_url, $data);
                if ($re['code'] == 200) {
                    file_put_contents('/tmp/push.log', "执行时间：" . date('Y-m-d H:i:s') . " 推送成功 订单数据：" . $order_sn . "\n", FILE_APPEND);

                } else {
                    file_put_contents('/tmp/push.log', "执行时间：" . date('Y-m-d H:i:s') . " 推送失败 订单数据：" . $order_sn . "\n", FILE_APPEND);

                }
            }
        } catch( Exception $e) {
            file_put_contents('/tmp/push.log', "执行时间：" . date('Y-m-d H:i:s') . " 推送失败 订单数据：" . var_export($data, true) . "\n", FILE_APPEND);
        }
        return true;
    }

    /**
     * 订单确定完成
     * 订单状态改变
     * 服务方余可提现余额增加 用户积分增加
     * 记录入账交易明细
     */
    public function complete()
    {
        $order_sn = ArrayHelper::getValue($this->data, 'order_sn', '');
        $order_data = $this->getOrder($order_sn);
        $account_data = [];
        $this->data['mobile'] = $order_data['shop_mobile'];
        $this->data['type'] = $order_data['order_type'];
        $this->type = 'add';
        //计算账户资金
        $this->getAmount($order_data['total']);
        if (empty($order_data)) {
            $this->error = '订单不存在';
            return false;
        }
        if ($order_data['pay_status'] != 1) {
            $this->error = '您还未支付 请先支付';
            return false;
        }
        if ($order_data['status'] == 2 || $order_data['status'] == 3) {
            //$this->error = '已经支付,请勿重复支付';
            $this->error = '订单已经完成，请勿重复操作';
            return false;
        }

        $transaction = $this->db->beginTransaction();
        try {
            //更改订单状态
            $this->db->createCommand()->update($this->order_table,
                [
                    'status'=>2,
                    'pay_status'=>1,
                    'operation_time'=>date("Y-m-d H:i:s"),
                ],
                ['mobile'=>$order_data['mobile'], 'order_sn'=>$order_sn]
            )->execute();

            $account_data['mobile'] = $order_data['shop_mobile'];
            $account_data['type'] = $order_data['order_type'];
            $account_data['price'] = $order_data['total'];
            $account_data['remark'] = '服务收入';
            $account_data['create_time'] = date("Y-m-d H:i:s");
            $account_data['order_sn'] = $order_sn;
            $account_data['status'] = 1;
            //$account_data['pay_method'] = 0;//$order_info['pay_method'];
            $account_data['extra_info'] = '';//$order_info['pay_method'];
            $this->score += Common::moneyToScore($order_data['total']);
            $this->db->createCommand()->insert($this->account_table, $account_data)->execute();
            $this->db->createCommand()->update($this->user_table,
                [
                    'no_amount'=>$this->no_amount,
                    'can_amount'=>$this->can_amount,
                    'score'=>$this->score,
                ],
                ['mobile'=>$this->data['mobile']]
            )->execute();
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }

    public function cancel()
    {
        $order_sn = ArrayHelper::getValue($this->data, 'order_sn', '');
        $order_data = $this->getOrder($order_sn);
        if (empty($order_data) || in_array($order_data['status'], [2, 3])) {
            $this->error = '订单不存在，或者重复操作';
        }
        $transaction = $this->db->beginTransaction();
        try {
            $this->db->createCommand()->insert($this->account_table, $account_data)->execute();
            $this->db->createCommand()->update($this->user_table,
                [
                    'no_amount'=>$this->no_amount,
                    'can_amount'=>$this->can_amount,
                ],
                ['mobile'=>$this->data['mobile']]
            )->execute();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }

    /*
     * 红包支付
     * @param $order_sn
     * @param $pay_method
     * @return array
     */

    public function SendMoney()
    {
        $order_sn = ArrayHelper::getValue($this->data, 'order_sn', '');
        file_put_contents('/tmp/new_txt.log',  "执行时间：".date('Y-m-d H:i:s')."订单号".$order_sn, FILE_APPEND);
        $pay_method = ArrayHelper::getValue($this->data, 'pay_method', '');

        $sendmoney = SendMoney::find()->select(['mobile','usermobile','total','pay_status'])->where(['order_sn'=>$order_sn])->asArray()->one();
        if ($sendmoney['pay_status'] == 1) {
            //$this->error = '已经支付,请勿重复支付';
            return true;
        }
        if (empty($sendmoney)) {
            $this->error = '订单不存在';
            return false;
        }
        $mobile = ArrayHelper::getValue($sendmoney, 'mobile', '');

        $transaction = $this->db->beginTransaction();
        try {
            if ($pay_method == 'account') {
                //如果是余额支付 则成功支付后 修改账户资金变动
                $re = $this->getAmount($sendmoney['total']);
                if ($re == false) {
                    $transaction->rollBack();
                    return false;
                }
                //发红包
                $this->db->createCommand()->update($this->user_table,
                    [
                        'no_amount'=>$this->no_amount,
                        'can_amount'=>$this->can_amount,
                    ],
                    ['mobile'=>$mobile]
                )->execute();
                $account_data['amount'] = $this->amount;
            }

            $remark = '红包';
            $account_data['mobile'] = $mobile;
            $account_data['price'] = -$sendmoney['total'];
            $account_data['remark'] = $remark;
            $account_data['create_time'] = date("Y-m-d H:i:s");
			$account_data['pay_method'] = $pay_method;
			$account_data['type'] = 8;
            $account_data['order_sn'] = $order_sn;
            $account_data['status'] = 1;
            $account_data['extra_info'] = ArrayHelper::getValue($this->data, 'extra_info', '');
            $this->db->createCommand()->insert($this->account_table, $account_data)->execute();

            //收红包方
            //$amount = UserBasicInfo::find()->select(['can_amount'])->where([]);
            $account_data1['mobile'] =  $sendmoney['usermobile'];
            $account_data1['price'] = $sendmoney['total'];
            $account_data1['remark'] = $remark;
            $account_data1['create_time'] = date("Y-m-d H:i:s");
			$account_data1['pay_method'] = $pay_method;
			$account_data1['type'] = 8;
            $account_data1['order_sn'] = $order_sn;
            $account_data1['status'] = 1;
            $account_data1['extra_info'] = ArrayHelper::getValue($this->data, 'extra_info', '');
            $this->db->createCommand()->insert($this->account_table, $account_data1)->execute();
            $paysucess =  $this->db->createCommand()->update($this->send_money,
                [
                    'status'=>1,
                    'pay_status'=>1,
                ],
                ['mobile'=>$mobile, 'order_sn'=>$order_sn]
            )->execute();

            //支付成功后修改余额
            if($paysucess>0)
            {
                $send = new SendMoney();
                $field = array();
                $field[] = "i500_send_money.mobile";
                $field[] = "i500_send_money.usermobile";
                $field[] = "i500_send_money.total";
                $field[] = "i500_send_money.pay_status";
                $field[] = "i500_send_money.order_sn";
                $field[] = "i500_user_basic_info.can_amount as can_amount";

                $condition[SendMoney::tableName().'.order_sn'] = $order_sn;
                //发红包
                /*$res = $send->find()->select($field)
                    ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_send_money.mobile')
                    ->where($condition)
                    ->asArray()
                    ->one();*/
                //收红包
                $res1 = $send->find()->select($field)
                    ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_send_money.usermobile')
                    ->where($condition)
                    ->asArray()
                    ->one();
                $pstatus = $res1['pay_status'];
                $usermobile = $sendmoney['usermobile'];
                $totalprice = $res1['total'];
                if($pstatus == 1)
                {
                    $amount = $res1['can_amount']+$totalprice;
                    $connection = \Yii::$app->db_social;
                    $transaction = $connection->beginTransaction();
                    $success = UserBasicInfo::updateAll(['can_amount'=>$amount],['mobile'=>$usermobile]);
                    if ($success>0)
                    {
                        $transaction->commit();
                    }
                    else
                    {
                        $transaction->rollBack();
                    }
                }
            }
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
        }
        return true;
    }

}