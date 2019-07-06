<?php
require_once('sdk.php');



if(!empty($and))
{
    $default_apiKey = '3QmkuU8eIXzWNsRggssYFqY1';
    $default_secretkey = '2SuGh8AAfPc81fsmlxvy6LFsXXX2aABY';
    // 创建SDK对象.
    $sdk = new PushSDK($default_apiKey,$default_secretkey);
//    $channelId = array('4411503463896039415','3665430934380974344');
    $channelIdand = $and;
    $sdk->setDeviceType('3');

//
//    // 设置消息类型为 通知类型.
    $opts = array (
        'msg_type' => 1 
        );

    $message = array (
    // 消息的标题.
    'title' => '您好!',
    // 消息内容 
    'description' => "猫鼬已被别人抓走了",
    'custom_content'=>['type'=>"20"],
    );
    
    // 向目标设备发送一条消息
    $rs = $sdk->pushBatchUniMsg($channelIdand, $message, $opts);


}
     
if(!empty($ios))
{
    $default_apiKey = 'tGX2RVIi6gBTcSZreGjeswQC';
    $default_secretkey = 'cF1wkx9NEfyME0NfWpwSxM3aKG8aaU85';
    // 创建SDK对象.
    $pushsdk = new PushSDK($default_apiKey,$default_secretkey);
    $pushsdk->setDeviceType('4');
    //$channelId = '5395648070159975533';
//    $channelId = array('5395648070159975533','5124929664435262341');
    $channelId = $ios;
    
    
    unset($message);
    $message = array (
        'aps' => array (
            // 消息内容
            'alert' => "猫鼬已被别人抓走了.",          
        ), 
        'type' => "20",
    );

    // 设置消息类型为 通知类型.
    unset($opts);
    $opts = array (
        'msg_type' => 1,        // iOS不支持透传, 只能设置 msg_type:1, 即通知消息.
        'deploy_status' => 1,   // iOS应用的部署状态:  1：开发状态；2：生产状态； 若不指定，则默认设置为生产状态。
    );

    // 向目标设备发送一条消息
    $rs = $pushsdk->pushBatchUniMsg($channelId, $message, $opts);

//    var_dump($rs);
//    exit;
}