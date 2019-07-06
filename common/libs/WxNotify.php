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
namespace common\libs;

use common\vendor\wxpay\lib\WxPayApi;
use yii\base\Object;
use yii\helpers\ArrayHelper;

//include_once('lib/WxPay.Config.php');
//require_once __DIR__."/lib/WxPay.Api.php";
//
//require_once __DIR__.'/lib/WxPay.Notify.php';
//require_once __DIR__.'/lib/WxPay.Data.php';
//require_once __DIR__.'/lib/log.php';
class WxNotify extends Object
{
    public $type;
    public function __construct($config = [])
    {
        parent::__construct($config);
    }
    public function init()
    {
        parent::init();

        // ... 配置生效后的初始化过程
    }
    /**
     * 回调处理
     */
    public function handle()
    {
        file_put_contents('/tmp/new_txt.log',  "执行时间：".date('Y-m-d H:i:s')." 微信回调进来了\n", FILE_APPEND);
        $msg = "OK";
        //$result = WxPayApi::notify(array($this, 'NotifyCallBack'), $msg);
        $result = WxPayApi::notify('NotifyCallBack', $msg);
        file_put_contents('/tmp/new_txt.log',  "微信处理完毕：".date('Y-m-d H:i:s').var_export($result, true)." \n", FILE_APPEND);
        $result['total_fee'] = ArrayHelper::getValue($result, 'total_fee', 0);
        $data = [];
        if(!empty($result)) {
            $pay_time = ArrayHelper::getValue($result, 'time_end', '');
            $pay_time = date("Y-m-d H:i:s", strtotime($pay_time));
            $data = [
                'buyer_email'=>ArrayHelper::getValue($result, 'openid', ''),
                'gmt_payment'=>$pay_time,
                'trade_no'=>ArrayHelper::getValue($result, 'transaction_id', ''),
                'out_trade_no'=>ArrayHelper::getValue($result, 'out_trade_no', ''),
            ];
        }
        //$model->order_sn = ArrayHelper::getValue($result, 'trade_no', '');

        return $data;
    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg)
    {
        file_put_contents('/tmp/new_txt.log',  "执行时间：".date('Y-m-d H:i:s')."微信处理过程process\n", FILE_APPEND);
    }
    /**
     *
     * 回调返回数据
     * @param bool $result  成功或失败
     * @return string
     */
    public function HandleReply($result)
    {
        $data = [];
        if($result == false){
            $data['return_code']='FAIL';
            $data['return_msg']="异常";
        } else {
            $data['return_code']='SUCCESS';
            $data['return_msg']='OK';
        }
        echo $this->ToXml2($data);
        file_put_contents('/tmp/new_txt.log',  "执行时间：".date('Y-m-d H:i:s')."通知微信".var_export($data, true)."\n", FILE_APPEND);

    }
    /**
     * 输出xml字符
     * @throws WxPayException
     **/
    public function ToXml2($data=[])
    {
        if(!is_array($data)
            || count($data) <= 0)
        {
            echo "数组数据异常！";exit;
        }

        $xml = "<xml>";
        foreach ($data as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }


}