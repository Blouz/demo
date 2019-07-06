<?php
require_once('sdk.php');
$message = array (
// 消息的标题.
'title' => '您好!',
// 消息内容 
'description' => "有新邻居加入您所在社区" 
);

// 设置消息类型为 通知类型.
$opts = array (
  'msg_type' => 1 
);
if(!empty($and))
{
    $default_apiKey = '3QmkuU8eIXzWNsRggssYFqY1';
    $default_secretkey = '2SuGh8AAfPc81fsmlxvy6LFsXXX2aABY';
    // 创建SDK对象.
    $sdk = new PushSDK($default_apiKey,$default_secretkey);
//    $channelId = array('4411503463896039415','3665430934380974344');
    $channelIdand = $and;
    $sdk->setDeviceType('3');
    // message content.
//    $message = array (
//    // 消息的标题.
//    'title' => '你好!',
//    // 消息内容 
//    'description' => "有新邻居加入社区" 
//    );
//
//    // 设置消息类型为 通知类型.
//    $opts = array (
//        'msg_type' => 1 
//        );

    // 向目标设备发送一条消息
    $rs = $sdk -> pushBatchUniMsg($channelIdand, $message, $opts);

    // 判断返回值,当发送失败时, $rs的结果为false, 可以通过getError来获得错误信息.
    if($rs === false){
        print_r($sdk->getLastErrorCode()); 
        print_r($sdk->getLastErrorMsg()); 
    }else{
          // 将打印出消息的id,发送时间等相关信息.
           print_r($rs);
    }
}
     
if(!empty($ios))
{
    $default_apiKey = 'tGX2RVIi6gBTcSZreGjeswQC';
    $default_secretkey = 'cF1wkx9NEfyME0NfWpwSxM3aKG8aaU85';
    // 创建SDK对象.
    $pushsdk = new PushSDK($default_apiKey,$default_secretkey);
//    $channelId = array('5124929664435262341','5453035977343440365');

    $channelIdios = $ios;
    $pushsdk->setDeviceType('4');
//     message content.
//    $message = array (
//    // 消息的标题.
//    'title' => '你好!',
//    // 消息内容 
//    'description' => "有新邻居加入社区" 
//    );
//
//    // 设置消息类型为 通知类型.
//    $opts = array (
//        'msg_type' => 1 
//        );

    // 向目标设备发送一条消息
    $rs = $pushsdk -> pushBatchUniMsg($channelIdios, $message, $opts);

    // 判断返回值,当发送失败时, $rs的结果为false, 可以通过getError来获得错误信息.
    if($rs === false){
        print_r($sdk->getLastErrorCode()); 
        print_r($sdk->getLastErrorMsg()); 
    }else{
          // 将打印出消息的id,发送时间等相关信息.
           print_r($rs);
    }
}