<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-12-17 下午5:22
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v5\controllers;

use common\vendor\push\PushSDK;
use frontend\models\i500_social\User;
use yii\web\Controller;
use common\helpers\RequestHelper;

class PushController extends Controller
{
    public $enableCsrfValidation = false;
    //推送类型
    //1 招募审核通过 拒绝 推送
    //2 服务审核通过拒绝 推送
    //todo
    //3 需求方预约服务 推送 给服务方 提示请接单
    //4 服务方接单 推送给预约方 提示 已经被接单

    //需求方预约支付后 提醒 你有一笔交易
    //5 服务方主动抢单 ，需求方收到推送 提示 已被抢单
//    public static $type = [
//        1=>'',
//    ];
    public function actionIndex()
    {
        $channelId = RequestHelper::post('channel_id');
        $device_type = RequestHelper::post('device_type');//推送的设备
        //$channelId = '3862737169522734802';
        //\Yii::$app->request->post();
        $channelId = '5262695184327112874';
        $message = array (
            // 消息的标题.
            'title' => 'Hi!.',
            // 消息内容
            'description' => "hello!, this is a push."
        );
// 设置消息类型为 通知类型.
//        $opts = array (
//            'msg_type' => 1
//        );
        $opts = array (
            'msg_type' => 1,        // iOS不支持透传, 只能设置 msg_type:1, 即通知消息.
            'deploy_status' => 1,   // iOS应用的部署状态:  1：开发状态；2：生产状态； 若不指定，则默认设置为生产状态。
        );
        $push = new PushSDK();
        $rs = $push->pushMsgToSingleDevice($channelId, $message, $opts);
        // 判断返回值,当发送失败时, $rs的结果为false, 可以通过getError来获得错误信息.
        if ($rs === false) {
            $code = $push->getLastErrorCode();
            $msg = $push->getLastErrorMsg();
           // file_put_contents('/tmp/baidu_push.log', "请求时间：".date('Y-m-d H:i:s')." 请求参数:". var_export($this->params, true)."|发送失败状态码".$code.";msg:".$msg."\n", FILE_APPEND);
            var_dump(['code'=>$code, 'msg'=>$msg]);
            //return json_encode(array('code'=>$code,'data'=>'', 'msg'=>$msg));

        } else {
            var_dump($rs);
        }
    }
    public function actionPushBatchUniMsg()
    {
        // 发送给以下五个设备，每个设备ID应与终端设备上产生的 channel_id 一一对应。
        $idArr = array(
            '000000000000001',
            '000000000000002',
            '000000000000003',
            '000000000000004',
            '000000000000005',
        );
        $push = new PushSDK();
        $message = array (
            // 消息的标题.
            'title' => 'Hi!.',
            // 消息内容
            'description' => "hello!, this is a push."
        );
        $opts = array (
            'msg_type' => 1,        // iOS不支持透传, 只能设置 msg_type:1, 即通知消息.
            'deploy_status' => 1,   // iOS应用的部署状态:  1：开发状态；2：生产状态； 若不指定，则默认设置为生产状态。
        );
// 发送
        $rs = $push -> pushBatchUniMsg($idArr, $message, $opts);
    print_r($rs);
        if($rs !== false){
            print_r($rs);    // 将打印出 msg_id 及 send_time
        }
    }
    public function actionTest()
    {
        $a = User::find()->select('channel_id')->where(['mobile'=>'18201570377'])->scalar();
        var_dump($a);
    }
}