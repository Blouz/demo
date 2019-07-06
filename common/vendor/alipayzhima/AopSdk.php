<?php
/**
 * AOP SDK 入口文件
 * 请不要修改这个文件，除非你知道怎样修改以及怎样恢复
 * @author wyy
 */

/**
 * 定义常量开始
 * 在include("AopSdk.php")之前定义这些常量，不要直接修改本文件，以利于升级覆盖
 */
/**
 * SDK工作目录
 * 存放日志，AOP缓存数据
 */
if (!defined("AOP_SDK_WORK_DIR"))
{
	define("AOP_SDK_WORK_DIR", "/tmp/");
}
/**
 * 是否处于开发模式
 * 在你自己电脑上开发程序的时候千万不要设为false，以免缓存造成你的代码修改了不生效
 * 部署到生产环境正式运营后，如果性能压力大，可以把此常量设定为false，能提高运行速度（对应的代价就是你下次升级程序时要清一下缓存）
 */
if (!defined("AOP_SDK_DEV_MODE"))
{
	define("AOP_SDK_DEV_MODE", true);
}
/**
 * 定义常量结束
 */

/**
 * 欺诈信息验证
 * @author wyy
 * @param string $cert_no 证件号
 * @param string $name 姓名
 * @param string $bank_card 银行卡号
 * @param string $mobile 预留手机号
 * @return boolean
 */
function zhima_credit_antifraud_verify($cert_no='', $name='', $bank_card='', $mobile='') {
    if (empty($cert_no) || empty($name)) {
        return false;
    }
    /**
     * 找到lotusphp入口文件，并初始化lotusphp
     * lotusphp是一个第三方php框架，其主页在：lotusphp.googlecode.com
     */
    $lotusHome = dirname(__FILE__) . DIRECTORY_SEPARATOR . "lotusphp_runtime" . DIRECTORY_SEPARATOR;
    include($lotusHome . "Lotus.php");
    $lotus = new Lotus;
    $lotus->option["autoload_dir"] = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'aop';
    $lotus->devMode = AOP_SDK_DEV_MODE;
    $lotus->defaultStoreDir = AOP_SDK_WORK_DIR;
    $lotus->init();
    //公共参数
    $aop = new AopClient ();
    $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
    $aop->appId = '2016081101732850';
    $aop->rsaPrivateKey = 'MIIEowIBAAKCAQEA5LTgvYfVLpWqmIA4C7dkpNz6+idvT8ho4RoTBl3qjSVEF6G+jFvEFci5RRQIcDzImX51cLqweMoPODmzgtEJskKIaZnSNxCrYq4/YAOIJYh0eNtuhvkxtNoJLj8DEocBRkH+ohzRcTz5A9rqXMMr8ZPCbGtpp6My/EKJixcJuLt8jWAFqtGjKekVuTAhdbKkDZfCq7bAKlC7AniNgo5aEAig2rTMhiJ4qqj2Z2BBDlA/f8iLJUX1tc0kuiT+KkNu66TOJLhycGLqUFqoEGGbbjMrujfoqhujZ3MqC3SUvMgSQ/3vbXh0TVK8n9yvMHE7H1+pz/QbX2ejBjdUic2KQQIDAQABAoIBAGD/Qa3AdS6btU2WW67Eaf/t5NfBb9kjgy+tElKJ9FdGbdmj9J+sr74ZclIUy/tmHCHVW1OjKgMgl1HeTv71Typ2Q0qePWSzQizJAAHlnZFljRhQ4FCIUYeFTY16lf/+mBDHHop8tGtVU7tJ1knfULzVUQkQiS/a7F3GjnUaITpiJI8H0XrHSWWRt9rJ4R4OexWCI4ApiNI60tQTIy+Qt/3Gvxfw3Wv4hXGe+MCc9P9Il81xGpLMu0sAl2gFoE4x0wIJJsUVp/UASY8B6RFTOfLZVuUywn8Y0fKhB4RHnisFTCMerivtTCUZB5NqZCVTBTrtqzjHtjSSpzoOHfalVpECgYEA9OqvvUeJ/EandRTnv8m0W9mM3zPhsnewtr8k6m4ErZewBSlDyG+CaGhLBklDEhzxgHTl+t8QyRgVogOj+8raaSpaF6EQMyBspawJYiq0XWv2Zb/XhHD25bguLECanh6ZJQ+aZaIuMzn4DByTFLzBr+C3Y3SOM2Ode6nThssfxe0CgYEA7w5mQ6E6/s97FitoyxyhLuJhzlPks7Js+13mEavA0d5X4bsmRWjFuHcoEEPvIrDiKGrIjbb1D4OWm5DaG0nGKg3Yp436YAVwdLUopVBIeqhlx6iXoztsvMTI8iN2Xk0srYVW+QVrzCNcMZv/wAOaqxXPhLvzPPuR5i6va4TvyyUCgYA/zZfEtotS5lEfpjeNdg1/VBhn2Us1xAqMQRVr4Qdk9bxMS+i1oZ8Wdfz9gT5wzIt5jHqRNWVZDeYs08f3g2wMs5TOzQ28U/kZ28yB/2AHamXBMg4CGa+oPqgArT/aa1w4FG2DhpGElNhyx0rlvxqyJ9d+mFGnP486NQ6+8dGwLQKBgQDOXp/pwkYrsvGPMepFbXG4B7ZnaAUHHAD4/xaeKqdKhadYNzfSs5/8nuD3Ka5HRGv4eDaqIEduHSrnISUoaYeoxktCWk7Kled+2OU90RDA0p8KvYAqaiJ002ylm/eTcQwgv/tU/lkQ4XW1XfZqzLG5ABslexuAiaxqCeNl7l0UAQKBgFbKejg2X9aIQvrze807erPNLEoWWDpAhK6HTwYXmWqsNkpvxJnJoq6K34H0cRtnpQF5IAwSpT/csu8MztcE4ayOdLjJzH+YhpuyO/HGL+8RXsMbBqJnm0lmcxv3+7Bm2fAPFMMZ0wOjfHnTTmHZ1QI3Uqx7Rs++M/Hxf5A+vRzt';
    $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAiHPDKmXiERrBP53W74hMRais5Aw6Qgf9WIJfovYaX77nqn4a8kuT2ZiFy/zlDWJG4WjGOyFyfJziDe23LdCFtQXW1JjNP5kSonfEkI70u46izF6XfmfDXHAASF84C6yS36Q5eRGJRlO+6WJkGGl3lTyQo+uPJk1aFc+YhB3ot0ZOLlpgBGKG8I4KLIuMd2yG6jtafvgG5h/aXPigiDQiYCXwvNHGRMxekkVjkMKuezI48DgKFmHvn2jca2euqEUV8LC5bwfWDQWyfkOxkCRf27Q6rcmThXXrJZBsp4zj/9HHtHg9TWxeO/q2iJfq27zQ1YneHTQjpLig+/wU7Bv4qwIDAQAB';
    $aop->apiVersion = '1.0';
    $aop->signType = 'RSA2';
    $aop->postCharset='UTF-8';
    $aop->format='json';
    //欺诈信息验证请求参数
    $request = new ZhimaCreditAntifraudVerifyRequest ();
    $request->setBizContent("{" .
        "\"product_code\":\"w1010100000000002859\"," .//产品码(使用示例值)
        "\"transaction_id\":\"".date('YmdHis') . substr(time(),-5) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT)."\"," .//商户唯一标示
        "\"cert_no\":\"{$cert_no}\"," .//证件号
        "\"cert_type\":\"IDENTITY_CARD\"," .//证件类型
        "\"name\":\"{$name}\"," .//姓名
        "\"mobile\":\"{$mobile}\"," .//手机号码
        "\"bank_card\":\"{$bank_card}\"," .//银行卡号
        "}");
    $result = $aop->execute ( $request);
    $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
    $data = $result->$responseNode;
    $resultCode = $data->code;
    if(!empty($resultCode)&&$resultCode == 10000){
        //银行卡不匹配
        if (!empty($bank_card) && (in_array('V_BC_PH_UK', $data->verify_code) || in_array('V_BC_PH_UM', $data->verify_code) || in_array('V_BC_CN_UK', $data->verify_code))) {
        //手机号不匹配
        } elseif (!empty($mobile) && (in_array('V_PH_NA', $data->verify_code) || in_array('V_PH_CN_UM', $data->verify_code) || in_array('V_PH_NM_UM', $data->verify_code))) {
        //姓名不匹配
        } elseif (in_array('V_CN_NM_UM', $data->verify_code)) {
        } else {
            return true;
        }
    }
    return false;
}