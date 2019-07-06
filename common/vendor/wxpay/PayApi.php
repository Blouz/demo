<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-26 上午10:24
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace common\vendor\wxpay;

use common\vendor\wxpay\lib\WxPayApi;
use common\vendor\wxpay\lib\WxPayConfig;
use common\vendor\wxpay\lib\WxPayUnifiedOrder;
use yii\helpers\ArrayHelper;

include_once('lib/WxPayData.php');
//include_once('lib/WxPay.Config.php');
//require_once __DIR__."/lib/WxPay.Api.php";
//
//require_once __DIR__.'/lib/WxPay.Notify.php';
//require_once __DIR__.'/lib/WxPay.Data.php';
//require_once __DIR__.'/lib/log.php';
class PayApi
{
    /*public $wechat_config = [
        //该页数据需要自己申请根据自己的填写
        //'app_id'=> "wx45d5a66d0c1ec20d",// 公众号身份标识
        //'app_secret'=> "88ea0906f6abd6a4622135d9487abae3",// 权限获取所需密钥 Key
        //'pay_sign_key'=> "x8jo2q2wtwnohgysn9enk8po8jpyvtnx",// 加密密钥 Key，也即appKey
        //'partner_id'=> '1239353902',// 财付通商户身份标识
        //'partner_key'=> 'x8jo2q2wtwnohgysn9enk8po8jpyvtnx',// 财付通商户权限密钥 Key
	
	//沈阳刘延伟 16/11/22 修改
        'app_id'=> "wx113947dfdacee245",// 公众号身份标识
        'app_secret'=> "0443c5188d07a10e0fb4a0f6f3823ff3",// 权限获取所需密钥 Key
        'pay_sign_key'=> "aycdefghijklMNopqrstuvwxbz123456",// 加密密钥 Key，也即appKey
        'partner_id'=> '1379143602',// 财付通商户身份标识
        'partner_key'=> 'aycdefghijklMNopqrstuvwxbz123456',// 财付通商户权限密钥 Key
	
	
        'notify_url'=> '',// 微信支付完成服务器通知页面地址
        'cacert_url'=> '',
        'AccessTokenFile'=> '',
    ];*/
    public $pay_config;
    public function __construct($config)
    {
        $this->pay_config = $config;
    }
    //public function
    /**
     * 统一下单
     * @throws lib\WxPayException
     */
    public function unifiedOrder()
    {
        //return ['data'=>1];
       // echo 33;exit();
       // var_dump($this->pay_config);exit();
        $input = new WxPayUnifiedOrder();
        $input->SetBody($this->pay_config['body']);
        $input->SetDetail(ArrayHelper::getValue($this->pay_config, 'detail', 'i500'));
        $input->SetAttach($this->pay_config['body']);
        $input->SetOut_trade_no($this->pay_config['order_sn']);
        $input->SetTotal_fee($this->pay_config['total'] * 100);
//        $input->SetTime_start(date("YmdHis"));
//        $input->SetTime_expire(date("YmdHis", time() + 600));
        //$input->SetGoods_tag("test_goods_tag");
        //$request['notify_url']      = Common::C('baseUrl').'/v4/notify/recharge';
        $input->SetNotify_url(ArrayHelper::getValue($this->pay_config, 'notify_url'));
        $input->SetTrade_type("APP");
        $result = WxPayApi::unifiedOrder($input);
        //var_dump($result);exit();
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            $pay_info['appid'] = $result['appid'];
            $pay_info['partnerid'] = $result['mch_id'];
            $pay_info['prepayid'] = $result['prepay_id'];
            $pay_info['noncestr'] = $result['nonce_str'];
            $pay_info['timestamp'] = time();
            $pay_info['package'] = 'Sign=WXPay';
            $sign = $this->SecondSign($pay_info);
            $pay_info['sign'] = $sign;
            return $pay_info;
	} else {
            return false;
        }
       // return $result;



       // $order = WxPayApi::unifiedOrder($input);
//
        //var_dump($result);exit();
       // $input->SetOpenid($openId);
//PayApi::unifiedOrder
    //    PayApi::unifiedOrder($input);
    }

    /**
     * 回调处理
     */
    public static function handle()
    {
        $notify = new Notify();
        $notify->handle();
    }
    /**
     * 二次生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function SecondSign($data)
    {
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string = "";
        foreach ($data as $k => $v)
        {
            $string .= $k . "=" . $v . "&";

        }

        $string = trim($string, "&");
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".WxPayConfig::KEY;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
}
