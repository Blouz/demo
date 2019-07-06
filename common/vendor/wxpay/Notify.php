<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-25 下午4:37
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
 /*
namespace common\vendor\wxpay;

//use common\vendor\weixins\lib\WxPayApi;
//use common\vendor\weixins\lib\WxPayNotify;

//use common\vendor\wxpay\lib\WxPayApi;
//use common\vendor\wxpay\lib\WxPayNotify;
//use common\vendor\wxpay\lib\WxPayOrderQuery;

//
use common\vendor\wxpay\lib\WxPayNotify;

//require_once __DIR__."/lib/WxPay.Api.php";
//require_once __DIR__.'/lib/WxPay.Notify.php';
//require_once __DIR__.'/lib/log.php';
class Notify extends  WxPayNotify
{


    //查询订单
    public function Queryorder($transaction_id)
    {
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($input);
       // Log::DEBUG("query:" . json_encode($result));
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }

}
*/