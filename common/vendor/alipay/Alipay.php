<?php

namespace  common\vendor\alipay;
use common\helpers\Common;

/**
 *  APP支付类
 * Author zhaochengqiang@iyangpin.com
 * @return string
 */
class Alipay
{
    /**
     * HTTPS形式消息验证地址
     */
    var $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
    /**
     * HTTP形式消息验证地址
     */
    var $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';

    public $partner = '2088421636157566';
    public $key = '8tgr0631ghzbqmt1v136vhal5cawyqwf';
    public $seller_email = 'lni500@126.com';
    public $service = "mobile.securitypay.pay";
    public $transport = "http";
    private $_input_charset = "utf-8";
    public $alipay_config;

    public $pay_config;
//    public $order_sn;
//    public $total;
//    public $subject;
//    public $body;
//    public $notify_url;
    public function __construct($config)
    {
        $this->pay_config = $config;
        $this->alipay_config = \Yii::$app->params['alipay'];
        /*$this->alipay_config =[
             'sign_type'=>'RSA',
             '_input_charset'=> 'utf-8',
             'cacert'    => 'cacert.pem',
             'key' => '8tgr0631ghzbqmt1v136vhal5cawyqwf',
             'partner' => '2088421636157566',
             'transport' => 'http',
             'seller_id' => 'lni500@126.com',
             'service'   => "mobile.securitypay.pay",
             'private_key_path'	=> '../../common/vendor/alipay/key/rsa_private_key.pem',
             'ali_public_key_path'=> '../../common/vendor/alipay/key/alipay_public_key.pem',
         ] ;*/
    }
    public function unifiedOrder()
    {

        $request['out_trade_no']    = $this->pay_config['order_sn'];
        $request['subject']         = $this->pay_config['subject'];
        $request['body']            = $this->pay_config['body'];
        $request['total_fee']       = $this->pay_config['total'];
        //$request['notify_url']      = Common::C('baseUrl').'v4/notify/alipay/1';
        $request['notify_url']      = $this->pay_config['notify_url'];
        $request['merchant_url']    = "";

        /**记录日志 end **/
        $form = $this->buildForm($request);
        $pay_info = '';
        if (is_array($form) && !empty($form)) {
            //记录支付宝接口请求日志
            // $this->_CommonServicePayLog($order_sn, $total, '', $app_name.'-app支付宝', '', 0, $app_name.'-app端发起请求支付');
            //组织info串返回
            $new = [];
            foreach ($form as $k => $v) {
                unset($form['subject']);
                if ($k=='sign') {
                    $new[] .= $k.'="'.UrlEncode($v).'"';
                } else {
                    $new[] .= $k.'="'.$v.'"';
                }
            }
            $pay_info=implode("&", $new);
        }
        //$pay_info['return_code'] = 'SUCCESS';
        //$result['pay_info'] = $pay_info;

        return $pay_info;
    }

    /**
     *  APP支付请求
     * Author zhaochengqiang@iyangpin.com
     * @param array $request 数据信息
     * @return string
     */
    public function buildForm($request=[])
    {

        //构造要请求的参数数组，无需改动

        $para_token = array(
            "partner" => $this->partner,
            "seller_id" => $this->seller_email,
            //"partner" => '2088901799749689',
            //"seller_id" => "iyangpin@126.com",
            "out_trade_no" => $request['out_trade_no'],
            "subject" => $request['subject'],
            "body" => $request['body'],
            "total_fee" => $request['total_fee'],
            "notify_url" => $request['notify_url'],
            "service" => "mobile.securitypay.pay",
            "payment_type" => "1",
            "_input_charset" => trim(strtolower($this->_input_charset)),
            "it_b_pay" => "10d",
        );

        //建立请求
        //echo "<pre>";
        //var_dump($para_token);exit;

        $html_text = $this->buildRequestHttp($para_token);

        return $html_text;
    }


    /**
     * 建立请求，以模拟远程HTTP的POST请求方式构造并获取支付宝的处理结果
     * @param $para_temp 请求参数数组
     * @return 支付宝处理结果
     */
    function buildRequestHttp($para_temp)
    {
        //待请求参数数组字符串
        $request_data = $this->buildRequestPara($para_temp);
        return $request_data;
    }

    /**
     * 生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
    function buildRequestPara($para_temp)
    {
        //生成签名结果
        $mysign = $this->buildRequestMysign($para_temp);
        //签名结果与签名方式加入请求提交参数组中
        $para_temp['sign'] = $mysign;
        if($para_temp['service'] != 'alipay.wap.trade.create.direct' && $para_temp['service'] != 'alipay.wap.auth.authAndExecute') {
            $para_temp['sign_type'] = strtoupper(trim($this->alipay_config['sign_type']));
        }

        return $para_temp;
    }

    /**
     * 生成签名结果
     * @param string $para_sort 已排序要签名的数组
     * @return String
     */
    function buildRequestMysign($para_sort)
    {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        //echo $prestr;exit;
        $mysign = $this->rsaSign($prestr, $this->alipay_config['private_key_path']);
        return $mysign;
    }


    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * @return 拼接完成以后的字符串
     */
    function createLinkstring($para)
    {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key.'="'.$val.'"&';
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

        return $arg;
    }


    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
     * @param $para 需要拼接的数组
     * @return 拼接完成以后的字符串
     */
    function createLinkstringUrlencode($para)
    {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key."=".urlencode($val)."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

        return $arg;
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * @return 去掉空值与签名参数后的新签名参数组
     */
    function paraFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each ($para)) {
            if($key == "sign" || $key == "sign_type" || $val == "")continue;
            else	$para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }
    /**
     * 对数组排序
     * @param $para 排序前的数组
     * @return 排序后的数组
     */
    function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * RSA签名
     * @param $data 待签名数据
     * @param $private_key_path 商户私钥文件路径
     * @return 签名结果
     */
    function rsaSign($data, $private_key_path)
    {

        $priKey = file_get_contents($private_key_path); //exit;

        $res = openssl_get_privatekey($priKey);
        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * RSA验签
     * @param $data 待签名数据
     * @param $ali_public_key_path 支付宝的公钥文件路径
     * @param $sign 要校对的的签名结果
     * @return 验证结果
     */
    function rsaVerify($data, $ali_public_key_path, $sign)
    {
        $pubKey = file_get_contents($ali_public_key_path);
        $res = openssl_get_publickey($pubKey);
        $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        openssl_free_key($res);
        return $result;
    }

    /**
     * RSA解密
     * @param $content 需要解密的内容，密文
     * @param $private_key_path 商户私钥文件路径
     * @return 解密后内容，明文
     */
    function rsaDecrypt($content, $private_key_path)
    {
        $priKey = file_get_contents($private_key_path);
        $res = openssl_get_privatekey($priKey);
        //用base64将内容还原成二进制
        $content = base64_decode($content);
        //把需要解密的内容，按128位拆开解密
        $result  = '';
        for($i = 0; $i < strlen($content)/128; $i++  ) {
            $data = substr($content, $i * 128, 128);
            openssl_private_decrypt($data, $decrypt, $res);
            $result .= $decrypt;
        }
        openssl_free_key($res);
        return $result;
    }



    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
    function verifyNotify()
    {
        if(empty($_POST)) {//判断POST来的数组是否为空
            return false;
        }
        else {
            //生成签名结果
            $isSign = $this->getSignVeryfy($_POST, $_POST["sign"]);
            //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
            $responseTxt = 'true';
            if (! empty($_POST["notify_id"])) {$responseTxt = $this->getResponse($_POST["notify_id"]);}

            //写日志记录
            //if ($isSign) {
            //	$isSignStr = 'true';
            //}
            //else {
            //	$isSignStr = 'false';
            //}
            //$log_text = "responseTxt=".$responseTxt."\n notify_url_log:isSign=".$isSignStr.",";
            //$log_text = $log_text.createLinkString($_POST);
            //logResult($log_text);

            //验证
            //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
            //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
            if (preg_match("/true$/i",$responseTxt) && $isSign) {
                return true;
            } else {
                return false;
            }
        }
    }


    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
    function getSignVeryfy($para_temp, $sign)
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        $para_filter["_input_charset"]="utf-8";

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->VeryfycreateLinkstring($para_sort);

        //echo $prestr;exit;

        $isSgin = false;
        switch (strtoupper(trim($this->alipay_config['sign_type']))) {
            case "RSA" :
                $isSgin = $this->rsaVerify($prestr, trim($this->alipay_config['ali_public_key_path']), $sign);
                break;
            default :
                $isSgin = false;
        }

        return $isSgin;
    }
    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * @return 拼接完成以后的字符串
     */
    function VeryfycreateLinkstring($para)
    {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key.'='.$val.'&';
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

        return $arg;
    }



    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
    function getResponse($notify_id)
    {
        $transport = strtolower(trim($this->alipay_config['transport']));
        $partner = trim($this->alipay_config['partner']);
        $veryfy_url = '';
        if($transport == 'https') {
            $veryfy_url = $this->https_verify_url;
        }
        else {
            $veryfy_url = $this->http_verify_url;
        }
        $veryfy_url = $veryfy_url."partner=" . $partner . "&notify_id=" . $notify_id;
        $responseTxt = $this->getHttpResponseGET($veryfy_url, $this->alipay_config['cacert']);

        return $responseTxt;
    }


    /**
     * 远程获取数据，GET模式
     * 注意：
     * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     * @param $url 指定URL完整路径地址
     * @param $cacert_url 指定当前工作目录绝对路径
     * @return 远程输出的数据
     */
    function getHttpResponseGET($url,$cacert_url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
        curl_setopt($curl, CURLOPT_CAINFO, $cacert_url);//证书地址
        $responseText = curl_exec($curl);
        //var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);

        return $responseText;
    }

}