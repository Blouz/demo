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
namespace frontend\modules\v8\controllers;

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
use frontend\models\i500_social\SendMoney;
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
     * 红包
     * @return array
     */
    public function actionSendMoney()
    {
        $total = RequestHelper::post('total',0);
        $type =  RequestHelper::post('type', 0);//1 支付宝 2 微信
        $mobile =  RequestHelper::post('mobile', 0);//1 支付宝 2 微信
        $usermobile = RequestHelper::post('usermobile','','');
        //创建订单号
        $order_sn = Common::createSn(35, $mobile);
		if (empty($total) || empty($mobile) || empty($usermobile) || !in_array($type, [1,2,3])) {
            return $this->returnJsonMsg('422',[],'数据不合法');
        }
        if (empty($order_sn)) {
            return $this->returnJsonMsg('501',[],'channel网络繁忙');
        }
        if ($type == 3) {
            $can_amount = UserBasicInfo::find()->select(['can_amount'])->where(['mobile'=>$mobile])->one();
            if ($total>$can_amount['can_amount']) {
                $this->returnJsonMsg('423',[],'余额不足，请充值');
            }
        }
        $body = "红包";
        //$method = ($type == 1) ? 'alipay' :'wxpay';
        $method = self::$pay_method[$type];
		$pay_config = [
            'order_sn'=>$order_sn,
            'total'=>$total,
            'subject'=>$body,
            'body'=>$body,
            'notify_url'=>Common::C('baseUrl').'v4/notify/'.$method.'/8',
        ];
        $sendmoney = new SendMoney();
        $sendmoney -> order_sn = $order_sn;
        $sendmoney -> mobile = $mobile;
        $sendmoney -> usermobile = $usermobile;
        $sendmoney -> total = $total;
        $sendmoney -> pay_method = $method;
        $re = $sendmoney -> save(false);
        if ($re) {
            if ($type == 1) {
                $pay = new Alipay($pay_config);
                $pay_info = $pay->unifiedOrder();
            } else if($type == 2) {
                $pay = new PayApi($pay_config);
                $pay_info = $pay->unifiedOrder();
            } else if ($type == 3) {//余额支付
                $pay_config = [
                    'order_sn'=>$order_sn,
                    'total'=>$total,
                    'mobile'=>$mobile,
                    'notify_url'=>'',
                    'pay_info'=>json_encode(['pay_method'=>'account', 'pay_time'=>date("Y-m-d H:i:s")]),
                    'pay_method'=>$method,
                ];
                $pay = new Balance($pay_config);
                $pay_info = $pay->unifiedSendMoneyOrder();
            }

            if ($pay_info == false) {
                return $this->returnJsonMsg('422',[],'数据不合法');
            } else {
                $pay_config['info'] = $pay_info;
                //$this->result['data'] = $pay_config;
            }
        } else {
            return $this->returnJsonMsg('500',[],'网络繁忙');
        }
        return $this->returnJsonMsg(200,$pay_config,Common::C('code',200));
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
        if (empty($mobile || empty($order_sn))) {
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
//        
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
                // $this->result['code'] = 422;
                // $this->result['message'] = '便利店暂不支持余额支付';
				$connection = \Yii::$app->db_social;
				$orders = new Order;
				$order = $orders::find()->select(['total'])->where(['order_sn'=>$order_sn])->asArray()->one();
				if(empty($order)) {
					$this->returnJsonMsg('500', [], Common::C('code', '500'));
				}
				$order_total = $order['total'];
				
				$userbasicinfo = new UserBasicInfo;
				$user = $userbasicinfo::find()->select(['can_amount'])->where(['mobile'=>$mobile])->asArray()->one();
				$user_amount = $user['can_amount'];
				$balance = $user_amount - $order_total;
				$transaction = $connection->beginTransaction();
				if($balance < 0)
				{
					$this->returnJsonMsg('7001', [], '余额不足');
				}else{
					$sql =  "UPDATE i500_order SET pay_status = 1 WHERE order_sn='$order_sn'";
					$res = \Yii::$app->db_social->createCommand($sql)->execute(); 
					
					$sql1 = "UPDATE i500_user_basic_info SET can_amount = '$balance' WHERE mobile='$mobile'";
					$res1 = Yii::$app->db_social->createCommand($sql1)->execute();
					
					if($res>0 || $res1 > 0)
					{
						$transaction->commit();
						$userbasicinfo = new UserBasicInfo;
						$userbasicinfo = $userbasicinfo::find()->select(['nickname','can_amount'])->where(['mobile'=>$mobile])->asArray()->one();
                        //获取要推送的channel_id
						$user = new User;
                        $channel_id = $user::find()->select('channel_id')->where(['mobile'=>$mobile])->scalar();
                        // echo json_encode($channel_id);exit;
                        if (!empty($channel_id)) 
                        {
                            $channel = explode('-', $channel_id);
                            $data['device_type'] = ArrayHelper::getValue($channel, 0);
                            $data['channel_id'] = ArrayHelper::getValue($channel, 1);
                            $data['type'] = 3;//新访客  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论
                            $data['title'] = $userbasicinfo['nickname']."你的订单已支付";
                            $data['description'] = $userbasicinfo['nickname']."你的订单已支付";
                            $channel_url = \Yii::$app->params['channelHost'] . 'v1/push';
                            $re = CurlHelper::post($channel_url, $data);
                        }
						
						$accountdetail = new AccountDetail;         
						$accountdetail->mobile =$mobile;  
						$accountdetail->type ='1';  
						$accountdetail->price =$order_total;  
						$accountdetail->amount =$balance;  
						$accountdetail->remark ='便利店订单支付';  
						$accountdetail->create_time = date('Y-m-d,h:i:s',time());  
						$accountdetail->order_sn =$order_sn;  
						$accountdetail->status ='1';  
						$accountdetail->pay_method ='3'; 
						$accountdetail->save();
					}else{
						$transaction->rollBack();
					}
				}
				
				$this->result['code'] = 200;
				$this->result['data'] = [
					'order_sn'=>$order_sn,
					'mobile'=>$mobile,
					'can_amount'=>$userbasicinfo['can_amount'],
					'notify_url'=>'',
					'pay_info'=>json_encode(['pay_method'=>'account', 'pay_time'=>date("Y-m-d H:i:s")]),
					'pay_method'=>'account',
				];
				$this->result['message'] = '操做成功';
                return $this->result;
            }elseif($order_type == 2) {
				$connection = \Yii::$app->db_social;
				$orders = new UserOrder;
				$order = $orders::find()->select(['total','shop_mobile'])->where(['order_sn'=>$order_sn])->asArray()->one();
				if(empty($order)) {
					$this->returnJsonMsg('500', [], Common::C('code', '500'));
				}
				$order_total = $order['total'];
				$shop_mobile = $order['shop_mobile'];
				$userbasicinfo = new UserBasicInfo;
				$user = $userbasicinfo::find()->select(['can_amount'])->where(['mobile'=>$mobile])->asArray()->one();
				if(empty($user)){
					$this->returnJsonMsg('404', [], Common::C('code', '404'));
				}
				$shop = $userbasicinfo::find()->select(['can_amount'])->where(['mobile'=>$shop_mobile])->asArray()->one();
				if(empty($shop)){
					$this->returnJsonMsg('404', [], Common::C('code', '404'));
				}
				$user_amount = $user['can_amount'];
				$shop_amount = $shop['can_amount'];
				$balance = $user_amount - $order_total;
				$balance1 = $shop_amount + $order_total;
				$transaction = $connection->beginTransaction();
				if($balance < 0)
				{
					$this->returnJsonMsg('7001', [], '余额不足');
				}else{
                    $operation_time = date('Y-m-d,h:i:s',time());
					$sql =  "UPDATE i500_user_order SET pay_status = 1,status=4 ,operation_time= '$operation_time' WHERE order_sn='$order_sn'";
					$res = \Yii::$app->db_social->createCommand($sql)->execute(); 
					
					$sql1 = "UPDATE i500_user_basic_info SET can_amount = '$balance' WHERE mobile='$mobile'";
					$res1 = Yii::$app->db_social->createCommand($sql1)->execute();
					
					$sql2 = "UPDATE i500_user_basic_info SET can_amount = '$balance1' WHERE mobile='$shop_mobile'";
					$res2 = Yii::$app->db_social->createCommand($sql2)->execute();
					
					if($res>0 || $res1 > 0 || $res2 > 0)
					{
						$transaction->commit();
						$userinfo = new UserBasicInfo;
						$userbasicinfo = $userinfo::find()->select(['nickname','can_amount'])->where(['mobile'=>$mobile])->asArray()->one();
						
                        $shop = $userinfo::find()->select(['nickname'])->where(['mobile'=>$shop_mobile])->asArray()->one();
						$user = new User;
						$channel_id = $user::find()->select('channel_id')->where(['mobile'=>$shop_mobile])->scalar();
						
						if (!empty($channel_id)) 
						{
							$channel = explode('-', $channel_id);
							$data['device_type'] = ArrayHelper::getValue($channel, 0);
							$data['channel_id'] = ArrayHelper::getValue($channel, 1);
							$data['type'] = 3;
							$data['title'] = $shop['nickname']."你的订单已支付";
							$data['description'] = $shop['nickname']."你的订单已支付";
							$channel_url = \Yii::$app->params['channelHost'] . 'v1/push';
							$re = CurlHelper::post($channel_url, $data);
						}
						
						$accountdetail = new AccountDetail;         
						$accountdetail->mobile =$mobile;  
						$accountdetail->type = '1';  
						$accountdetail->price ="-".$order_total;  
						$accountdetail->amount =$balance;  
						$accountdetail->remark ='交易';  
						$accountdetail->create_time = date('Y-m-d,h:i:s',time());  
						$accountdetail->order_sn =$order_sn;  
						$accountdetail->status ='1';  
						$accountdetail->pay_method ='3'; 
						$accountdetail->save();
						
						$can_amount = $userinfo::find()->select(['can_amount'])->where(['mobile'=>$shop_mobile])->asArray()->one();
						$accountdetail = new AccountDetail;
						$accountdetail->mobile =$shop_mobile;  
						$accountdetail->type = '1';  
						$accountdetail->price ="+".$order_total;  
						$accountdetail->amount = $can_amount['can_amount'];  
						$accountdetail->remark ='交易';  
						$accountdetail->create_time = date('Y-m-d,h:i:s',time());  
						$accountdetail->order_sn =$order_sn;  
						$accountdetail->status ='1';  
						$accountdetail->pay_method ='3'; 
						$accountdetail->save();
					}else{
						$transaction->rollBack();
					}
				}
				
				$this->result['code'] = 200;
				$this->result['data'] = [
					'order_sn'=>$order_sn,
					'mobile'=>$mobile,
					'pay_status'=>'1',
					'can_amount'=>$userbasicinfo['can_amount'],
					'notify_url'=>'',
					'pay_info'=>json_encode(['pay_method'=>'account', 'pay_time'=>date("Y-m-d H:i:s")]),
					'pay_method'=>'account',
				];
				$this->result['message'] = '操做成功';
                return $this->result;
			}
			if($type != 3){
				$pay_config = [
					'order_sn'=>$order_sn,
					'price'=>$order->total,
					'mobile'=>$mobile,
				   // 'remark'=>'预约服务订单支付',
					'notify_url'=>'',
					'pay_info'=>json_encode(['pay_method'=>'account', 'pay_time'=>date("Y-m-d H:i:s")]),
					'pay_method'=>$method,
				];
			}

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
			//$connection = \Yii::$app->db_social;
			//更改服务订单支付状态为已支付
			//$connection->createCommand()->update('i500_service_order', ['pay_status' => 1], 'order_sn='.$order_sn)->execute();
			
            $pay_config['info'] = $info;
            $this->result['data'] = $pay_config;
        }


        return $this->result;

    }
}
