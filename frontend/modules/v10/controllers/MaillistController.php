<?php
/**
 * Created by PhpStorm.
 * User: MAC
 * Author huangdekui
 * Date: 2017/3/31
 * Time: 13:50
 * Email huangdekui@i500m.com
 */

namespace frontend\modules\v10\controllers;


use common\helpers\Common;
use common\helpers\CurlHelper;
use common\helpers\RequestHelper;
use yii\helpers\ArrayHelper;
use frontend\models\i500_social\UserSms;

class MaillistController extends BaseController
{
    public function actionSend(){
        $mobile = RequestHelper::post('mobile','','');
        if (empty($mobile)) {
            return $this->returnJsonMsg('604',[],Common::C('code','604'));
        }
        if (!Common::validateMobile($mobile)) {
            return $this->returnJsonMsg('605',[],Common::C('code','605'));
        }

        $fid = RequestHelper::post('fid','','');
        if (empty($fid)) {
            return $this->returnJsonMsg('604',[],Common::C('code','604'));
        }
        $time = date("Y-m-d", time());
        $cond = ['between', 'create_time', $time.' 00:00:00', $time.' 23:59:59'];
        $mail = UserSms::find()->where(['mobile'=>$fid])->andwhere($cond)->asArray()->one();
        if(empty($mail)) {
            $usersms = new UserSms();
            $usersms->mobile = $fid; 
            $usersms->content = Common::getSmsTemplate(7);
            $usersms->send_time = date("Y-m-d H:i:s", time());
            $sms = $usersms->save();
            if (!$sms) {
                return $this->returnJsonMsg('500', [], Common::C('code', '500'));
            }

            $sms_content = Common::getSmsTemplate(7);
            /**发送短信通道**/
            $rs = $this->sendSmsChannel($fid, $sms_content);
            if (!$rs) {
                return $this->returnJsonMsg('611', [], '短信发送失败');
            }
            return $this->returnJsonMsg('200',[],Common::C('code','200'));
        }else {
            return $this->returnJsonMsg('667',[],Common::C('code','667'));
        }
    }
}