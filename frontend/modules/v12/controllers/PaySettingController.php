<?php
/**
 * v12 支付设定
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      2017/6/21
 */

namespace frontend\modules\v12\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\UserCertification;
use frontend\models\i500_social\UserVerifyCode;

class PaySettingController extends BaseShouldController {
    
    /**
     * 是否已设置支付密码
     * @author liuyanwei
     * @return json
     */
    public function actionIsSettingPwd(){
        //查询该用户是否设置密码
        $info = UserCertification::find()->select(['id','paypwd'])
                    ->where(['mobile'=>$this->mobile])
                    ->asArray()
                    ->one();

        $data['status'] = 2;
        if(!empty($info) && $info['paypwd'] != "") {
        	$data['status'] = 1;
        }
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     * 验证支付密码是否正确
     * @param string $password 密码md5
     * @author liuyanwei
     * @return json
     */
    public function actionCheckPwd(){
        $password =  RequestHelper::post('password','','trim');
        if (empty($password)) {
            return $this->returnJsonMsg('2100',[],Common::C('code','2100'));
        }
        //判断密码是否正确
        if(!$this->checkPayPwd($password)){
            $this->returnJsonMsg('607', [], Common::C('code', '607'));
        }
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
    
    //发送支付密码的验证码
    public function actionSendCode() {
        $code = Common::getRandomNumber();
        $sms_content = Common::getSmsTemplate(8, $code);
        //保存验证码
        $user_verify_code_model = new UserVerifyCode();
        $user_verify_code_data['mobile']     = $this->mobile;
        $user_verify_code_data['code']       = $code;
        $user_verify_code_data['type']       = 8;
        $user_verify_code_data['expires_in'] = date('Y-m-d H:i:s', (time()+ Common::C('verify_code_timeout')));
        $rs = $user_verify_code_model->insertInfo($user_verify_code_data);
        if (empty($rs)) {
            $this->returnJsonMsg('611', [], Common::C('code', '611'));
        }
        //发送短信
        $this->sendSmsChannel($this->mobile, $sms_content);
        
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
    
    //验证支付密码的验证码
    public function actionCheckCode() {
        //验证码
        $code = RequestHelper::post('code', '', 'trim');
        if (empty($code)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        $where = [
            'mobile' => $this->mobile,
            'code'   => $code,
            'type'   => 8,
        ];
        //获取验证码信息
        $code_model = new UserVerifyCode();
        $code_info = $code_model->getInfo($where, true, 'id,expires_in', '', 'id desc');
        //不存在
        if (empty($code_info)) {
            $this->returnJsonMsg('610', [], Common::C('code', '610'));
        }
        //超时
        if (strtotime($code_info['expires_in']) <= time()) {
            $this->returnJsonMsg('609', [], Common::C('code', '609'));
        }
        
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
    
    //设置支付密码
    public function actionSetPwd() {
        //验证码
        $code = RequestHelper::post('code', '', 'trim');
        if (empty($code)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //密码
        $password1 = RequestHelper::post('password1', '', 'trim');
        if (empty($password1)) {
            $this->returnJsonMsg('2100', [], Common::C('code', '2100'));
        }
        //确认密码
        $password2 = RequestHelper::post('password2', '', 'trim');
        if (empty($password2)) {
            $this->returnJsonMsg('2100', [], Common::C('code', '2100'));
        }
        //密码格式错误或不一致
        if (!preg_match('/^[a-z0-9]{32}$/',$password1) || $password1!=$password2) {
            $this->returnJsonMsg('607', [], Common::C('code', '607'));
        }
        
        $where = ['mobile' => $this->mobile, 'code' => $code, 'type' => 8];
        //获取验证码信息
        $code_model = new UserVerifyCode();
        $code_info = $code_model->getInfo($where, true, 'id,expires_in', '', 'id desc');
        //不存在
        if (empty($code_info)) {
            $this->returnJsonMsg('610', [], Common::C('code', '610'));
        }
        //超时
        if (strtotime($code_info['expires_in']) <= time()) {
            $this->returnJsonMsg('609', [], Common::C('code', '609'));
        }
        //设置该验证码失效
        UserVerifyCode::updateAll(['expires_in'=>date('Y-m-d H:i:s')],['id'=>$code_info['id']]);
        
        //获取用户认证信息对象
        $UCinfo = UserCertification::findOne(['mobile'=>$this->mobile]);
        if (empty($UCinfo)) {
            $UCinfo = new UserCertification();
            $UCinfo->uid = $this->uid;
            $UCinfo->mobile = $this->mobile;
            $UCinfo->status = 1;
        }
        //设置密码
        $paysalt = Common::getRandomNumber();
        $UCinfo->paypwd = md5($password1.$paysalt);
        $UCinfo->paysalt = $paysalt;
        $UCinfo->pwderror_num = 0;
        $res = $UCinfo->save();
        if (empty($res)) {
            $this->returnJsonMsg('612', [], Common::C('code', '612'));
        }
        
        $data['status'] = 1;
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
    
    //修改支付密码
    public function actionUpPwd() {
        //旧密码
        $password = RequestHelper::post('password', '', 'trim');
        if (empty($password)) {
            $this->returnJsonMsg('2100', [], Common::C('code', '2100'));
        }
        //原密码错误
        if(!$this->checkPayPwd($password)){
            $this->returnJsonMsg('607', [], Common::C('code', '607'));
        }
        //新密码
        $password1 = RequestHelper::post('password1', '', 'trim');
        if (empty($password1)) {
            $this->returnJsonMsg('2100', [], Common::C('code', '2100'));
        }
        //确认密码
        $password2 = RequestHelper::post('password2', '', 'trim');
        if (empty($password2)) {
            $this->returnJsonMsg('2100', [], Common::C('code', '2100'));
        }
        //密码格式错误或不一致
        if (!preg_match('/^[a-z0-9]{32}$/',$password1) || $password1!=$password2) {
            $this->returnJsonMsg('607', [], Common::C('code', '607'));
        }
        
        //获取用户认证信息对象
        $UCinfo = UserCertification::findOne(['mobile'=>$this->mobile]);
        if (empty($UCinfo)) {
            $this->returnJsonMsg('404', [], Common::C('code', '404'));
        }
        //设置密码
        $paysalt = Common::getRandomNumber();
        $UCinfo->paypwd = md5($password1.$paysalt);
        $UCinfo->paysalt = $paysalt;
        $UCinfo->pwderror_num = 0;
        $res = $UCinfo->save();
        if (empty($res)) {
            $this->returnJsonMsg('612', [], Common::C('code', '612'));
        }
        
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
}
