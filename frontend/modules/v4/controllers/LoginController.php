<?php
/**
 * 登陆
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Login
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/05 09:21
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v4\controllers;

use frontend\models\i500m\YpUser;
use Yii;
use yii\helpers\ArrayHelper;
use common\helpers\Common;
use common\helpers\RequestHelper;
use common\helpers\HuanXinHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserToken;
use frontend\models\i500_social\UserChannel;
use frontend\models\i500_social\UserVerifyCode;
use frontend\models\i500_social\LoginLog;
use frontend\models\i500_social\VerificationCode;

/**
 * Login
 *
 * @category Social
 * @package  Login
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class LoginController extends BaseController
{
    /**
     * 登陆的方法
     *
     * Param int    $type     登陆方式
     * Param string $mobile   手机号
     * Param string $password md5后的密码
     * Param string $code     验证码
     *
     * @return array
     */
    public function actionIndex()
    {
        $type            = RequestHelper::post('type', '1', '');
        $channel         = RequestHelper::post('channel', '1', '');
        $channel_user_id = RequestHelper::post('channel_user_id', '0', '');
        $mobile          = RequestHelper::post('mobile', '', 'trim');
        $password        = RequestHelper::post('password', '', 'trim');
        $user_model = new User();
        $user_where['mobile']     = $mobile;
        //$user_where['is_deleted'] = '2';
        $user_fields = 'id,mobile,password,salt,login_count,status,source';
        $user_info = $user_model->getInfo($user_where, true, $user_fields);
        if ($type == '1') {
            if (empty($mobile) || !Common::validateMobile($mobile)) {
                $this->returnJsonMsg('422', [], '手机号不合法');
            }
            /**普通登陆**/
            if (empty($password)) {
                $this->returnJsonMsg('422', [], '请输入密码');
            }
            if (!empty($user_info) && $user_info['status'] == 2) {
                if ($user_info['source'] == 1) {
                    $password_input = md5($user_info['salt'].$password);
                } else if ($user_info['source'] == 2) {
                    $password_input = $password;
                }

                if ($password_input != $user_info['password']) {
                    $this->returnJsonMsg('607', [], Common::C('code', '607'));
                }
            } else {
                //读取爱样品网用户
                $yp_user_fields = ['id','mobile','password','status','nickname'=>'username','avatar','create_time'];
                $user_info = YpUser::find()
                    ->select($yp_user_fields)
                    ->where(['mobile'=>$mobile])
                    ->asArray()->one();
                //var_dump($user_info);
                if (!empty($user_info) && $user_info['status'] == 2) {
                    if ($password == $user_info['password']) {
                        //登陆成功注册到social 用户表
                        $user_data = [
                            'mobile'=>$user_info['mobile'],
                            'password'=>$user_info['password'],
                            'status'=>2,
                            'source'=>2,
                            //'token'=> md5($mobile.time()),
                            'create_time'=>date("Y-m-d H:i:s",$user_info['create_time']),
                        ];
                        $user_model->attributes = $user_data;
                        $user_model->expired_in = time() + 3600 * 24 * 30 * 3;//token过期时间
                        // var_dump($model->attributes);exit();
                        if ($user_model->save(false)) {
                            $user_base_model = new UserBasicInfo();
                            $user_base_data['mobile'] = $user_data['mobile'];
                            $user_base_data['nickname'] = $user_info['nickname'];
                            $user_base_model->insertInfo($user_base_data);
                        }
                    } else {
                        $this->returnJsonMsg('422', [], '密码错误');
                    }
                } else {
                    $this->returnJsonMsg('422', [], '用户不存在或已禁用');
                }
            }
        } else {
            /**第三方平台登录**/
            if (empty($channel_user_id)) {
                $this->returnJsonMsg('422', [], '无效的第三方账户');
            }
            if (empty($channel)) {
                $this->returnJsonMsg('422', [], '第三方账户必须');
            }
            if (!in_array($channel, ['1', '2', '3', '4'])) {
                $this->returnJsonMsg('422', [], '无效的第三方账户');
            }
            $user_channel_model = new UserChannel();
            $user_channel_where['channel_user_id'] = $channel_user_id;
            $user_channel_where['channel']         = $channel;
            $user_channel_where['status']          = '1';
            $user_channel_info = $user_channel_model->getInfo($user_channel_where, true, 'id,mobile');
            if (empty($user_channel_info)) {
                $this->returnJsonMsg('616', [], Common::C('code', '616'));
            }
            if (empty($user_channel_info['mobile'])) {
                $this->returnJsonMsg('617', [], Common::C('code', '617'));
            }
            $mobile = $user_channel_info['mobile'];
        }
        //验证完成
        //获取用户基本信息 存储到本地
        $user_info_model = new UserBasicInfo();
        $info = $user_info_model->getInfo(['mobile'=>$mobile], true, 'nickname,avatar,personal_sign');

        /**更新token**/
        $user_token_data['token']       = md5($mobile.time());
        $user_token_data['expired_in'] = time() + 3600 * 24 * 30 * 3;
        $user_model->updateInfo($user_token_data, ['mobile'=>$mobile]);


        $rs_u_info['id']     = $user_info['id'];
        $rs_u_info['mobile'] = $mobile;
        $rs_u_info['token']  = $user_token_data['token'];
        $rs_u_info['nickname']  = ArrayHelper::getValue($info, 'nickname', '');
        $rs_u_info['avatar']  = ArrayHelper::getValue($info, 'avatar', '');
        $rs_u_info['personal_sign']  = ArrayHelper::getValue($info, 'personal_sign', '');
        //判断环信是否注册
        $re = HuanXinHelper::userStatus($mobile);
        if ($re != 200) {
            HuanXinHelper::hxRegister($mobile, Common::C('passwordCode'), Common::C('defaultNickName'));
        }
        $this->returnJsonMsg('200', $rs_u_info, Common::C('code', '200'));
    }

    /**
     * 注册
     * @return array
     */
    public function actionRegister()
    {
        $model = new User();
        $model->scenario = 'register';
        $data = Yii::$app->request->post();
        if (empty($data['code'])) {
            $this->result['code'] = 601;
            $this->result['message'] = '验证码不能为空';
        }
        $model->attributes = $data;
        $model->expired_in = time() + 3600 * 24 * 30 * 3;//token过期时间
        if (!$model->save()) {
            if ($model->hasErrors()) {
                $errors = $model->getFirstErrors();
                if(isset($errors)){
                    if(isset($errors['mobile'])) {
                        if($errors['mobile'] == '此用户已经存在' && !isset($errors['password'])) {
                            if(isset($errors['code'])) {
                                $this->result['code'] = 601;
                                $this->result['message'] = $errors['code'];
                            }else {
                                $info = User::find()->select(['salt','token'])->where(['mobile'=>$data['mobile']])->asArray()->one();
                                $password = md5($info['salt'].$data['password']);
                                $user = User::updateAll(['password'=> $password],['mobile'=>$data['mobile']]);
                                if($user == 0) {
                                    $this->result['code'] = 601;
                                    $this->result['message'] = '请输入新的密码';
                                }else{
                                    $verificationcode = new VerificationCode();
                                    $verificat = $verificationcode::find()->select(['open_invitation_code', 'open_relation_community', 'mention'])->asArray()->one();
                                    //var_dump($verificat);exit();
                                    $step = $model::find()->select(['step'])->where(['mobile'=>$data['mobile']])->asArray()->one();
                                    if($step['step'] != 0) {
                                        $date['step'] = $step['step'];
                                    }elseif($step['step'] == 0){
                                        if($verificat['open_invitation_code'] == 1) {
                                            $date['step'] = '1';
                                        }else{
                                            $date['step'] = '0';
                                        }
                                    }
                                    $value = 'phone';
                                    $mobile = $data['mobile'];
                                    $ident = $this->_addident($value, $mobile);
                                    $date['open_invitation_code'] = $verificat['open_invitation_code'];
                                    $date['open_relation_community'] = $verificat['open_relation_community'];
                                    $date['token'] = $info['token'];
                                    $date['mention'] = $verificat['mention'];
                                    $this->result['code'] = 200;
                                    $this->result['data'] = $date;
                                    $this->result['message'] = 'OK';
                                }
                            }
                        }else{
                            $errors = array_values($errors);
                            $this->result['code'] = 601;
                            $this->result['message'] = ArrayHelper::getValue($errors, 0, 'Error');
                        }
                    }else{
                        $errors = array_values($errors);
                        $this->result['code'] = 601;
                        $this->result['message'] = ArrayHelper::getValue($errors, 0, 'Error');
                    }
                }
            } else {
                $this->result['code'] = 500;
                $this->result['message'] = '网络忙';
            }

            return $this->response();
        } else {
            /**同时记录UserBaseInfo**/
            $user_base_model = new UserBasicInfo();
            $user_base_data['mobile'] = $model->mobile;
            $user_base_model->insertInfo($user_base_data);
        }
        $value = 'phone';
        $mobile = $data['mobile'];
        $ident = $this->_addident($value, $mobile);
        $verificationcode = new VerificationCode();
        $verificat = $verificationcode::find()->select(['open_invitation_code', 'open_relation_community', 'mention'])->asArray()->one();
        $step = $model::find()->select(['step'])->where(['mobile'=>$data['mobile']])->asArray()->one();
        if($step['step'] != 0) {
            $date['step'] = $step['step'];
        }elseif($step['step'] == 0){
            if($verificat['open_invitation_code'] == 1) {
                $date['step'] = '1';
            }else{
                $date['step'] = '0';
            }
        }
        $token = User::find()->select(['token'])->where(['mobile'=>$mobile])->asArray()->one();
        $date['open_invitation_code'] = $verificat['open_invitation_code'];
        $date['open_relation_community'] = $verificat['open_relation_community'];
        $date['token'] = $token['token'];
        $date['mention'] = $verificat['mention'];
        $this->result['code'] = 200;
        $this->result['data'] = $date;
        $this->result['message'] = 'OK';
        return $this->response();
    }
    /**
     * 发送验证码
     *
     * Param string $mobile   手机号
     *
     * @return array
     */
    public function actionSendcode()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $type   = RequestHelper::post('type', '', '0');

        $code = UserVerifyCode::find()->select(['create_time'])->where(['mobile'=>$mobile, 'type'=>$type])->orderBy('create_time DESC')->asArray()->one();
        $expires_time = strtotime($code['create_time']) + 60;
        if($expires_time > time()){
            $this->result['code'] = 665;
            $this->result['message'] = Common::C('code', '665');
            return $this->response();
        }

        $user_model = new User();
        $user_where['mobile']     = $mobile;
       // $user_where['is_deleted'] = '2';
        $user_fields = 'id,mobile';
        $user_info = $user_model->getInfo($user_where, true, $user_fields);

        if (!in_array($type, [2,3,4])) {
            $this->result['code'] = 601;
            $this->result['message'] = '无效的验证码类型';
            return $this->response();
        }
        if ($type == 3 && !empty($user_info)) {//注册发送验证码

            /**存在该用户**/
            $this->result['code'] = 620;
            $this->result['message'] = Common::C('code', '620');
            return $this->response();
        }
        /**发送验证码**/
        $user_verify_code_model = new UserVerifyCode();
        $user_verify_code_data['mobile']     = $mobile;
        $user_verify_code_data['code']       = Common::getRandomNumber();
        $user_verify_code_data['type']       = $type;  //注册发送验证码
        $user_verify_code_data['expires_in'] = date('Y-m-d H:i:s', (time()+ Common::C('verify_code_timeout')));
        $rs = $user_verify_code_model->insertInfo($user_verify_code_data);
        if (!$rs) {
            $this->result['code'] = 400;
            $this->result['message'] = Common::C('code', '400');
        } else {
            $sms_content = Common::getSmsTemplate($type, $user_verify_code_data['code']);

            /**发送短信通道**/
            $rs = $this->sendSmsChannel($mobile, $sms_content);
            if (!$rs) {
                $this->result['code'] = 611;
                $this->result['message'] = Common::C('code', '611');
                // $this->returnJsonMsg('611', [], Common::C('code', '611'));
            } else {
                $this->result['message'] = '发送成功';
            }
        }
        return $this->response();
//        $this->result['code'] = 601;
//        $this->result['message'] = '无效的验证码类型';
//        $this->returnJsonMsg('200', ['first_login'=>'1'], "发送成功");

    }

    /**
     * 找回密码 验证短信验证码
     * @return array
     */
    public function actionFindPwdCheck()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $code = RequestHelper::post('code', '', '');
        if (empty($code)) {
            $this->returnJsonMsg('608', [], Common::C('code', '608'));
        }
        $user_verify_code_model = new UserVerifyCode();
        $user_verify_code_where['mobile'] = $mobile;
        $user_verify_code_where['code']   = $code;
        $user_verify_code_where['type']   = '2';
        $user_verify_code_fields = 'id,expires_in';
        $user_verify_code_info = $user_verify_code_model->getInfo($user_verify_code_where, true, $user_verify_code_fields, '', 'id desc');
        if ($user_verify_code_info) {
            if (strtotime($user_verify_code_info['expires_in']) < time()) {
                $this->returnJsonMsg('609', [], Common::C('code', '609'));
            }
            $this->returnJsonMsg('200', [], Common::C('code', '200'));
        } else {
            $this->returnJsonMsg('610', [], Common::C('code', '610'));
        }
    }

    /**
     * 修改密码的方法
     * @return array
     */
    public function actionModifyPwd()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $password = RequestHelper::post('password', '', '');
        if (empty($password)) {
            $this->returnJsonMsg('606', [], Common::C('code', '606'));
        }
        $user_model = new User();
        $user_where['mobile']     = $mobile;
        $user_where['is_deleted'] = '2';
        $user_fields = 'id,mobile,salt';
        $user_info = $user_model->getInfo($user_where, true, $user_fields);
        if (!empty($user_info)) {
            $user_update_data['password'] = md5($user_info['salt'].$password);
            $user_update_where['mobile']  = $mobile;
            $rs = $user_model->updateInfo($user_update_data, $user_update_where);
            if (!$rs) {
                $this->returnJsonMsg('612', [], Common::C('code', '612'));
            } else {
                $this->returnJsonMsg('200', [], Common::C('code', '200'));
            }
        } else {
            $this->returnJsonMsg('602', [], Common::C('code', '602'));
        }
    }

    /**
     * 第三方授权成功后调用
     * @return array
     */
    public function actionAuthSuccess()
    {
        $channel         = RequestHelper::post('channel', '1', '');
        $channel_user_id = RequestHelper::post('channel_user_id', '0', '');
        $source          = RequestHelper::post('dev', '1', '');
        if (empty($channel)) {
            $this->returnJsonMsg('614', [], Common::C('code', '614'));
        }
        if (!in_array($channel, ['1', '2', '3', '4'])) {
            $this->returnJsonMsg('615', [], Common::C('code', '615'));
        }
        if (empty($channel_user_id)) {
            $this->returnJsonMsg('613', [], Common::C('code', '613'));
        }
        $user_channel_model = new UserChannel();
        $user_channel_where['channel'] = $channel;
        $user_channel_where['channel_user_id'] = $channel_user_id;
        $user_channel_where['status'] = '1';
        $user_channel_info = $user_channel_model->getInfo($user_channel_where, true, 'id,mobile');
        if (empty($user_channel_info)) {
            $user_channel_data['channel'] = $channel;
            $user_channel_data['source']  = $source;
            $user_channel_data['channel_user_id'] = $channel_user_id;
            $rs = $user_channel_model->insertInfo($user_channel_data);
            if (!$rs) {
                $this->returnJsonMsg('400', [], Common::C('code', '400'));
            }
        } else {
            if (empty($user_channel_info['mobile'])) {
                $this->returnJsonMsg('617', [], Common::C('code', '617'));
            } else {
                $this->returnJsonMsg('200', ['mobile'=>$user_channel_info['mobile']], Common::C('code', '200'));
            }
        }
        $this->returnJsonMsg('617', [], Common::C('code', '617'));
    }
    /**
     * 绑定用户
     * @return array
     */
    public function actionBindUser()
    {
        $channel         = RequestHelper::post('channel', '1', '');
        $channel_user_id = RequestHelper::post('channel_user_id', '0', '');
        //@todo 20151022 绑定用户时，用户昵称规则 = 用户＋6位随机数
        $channel_nickname = RequestHelper::post('channel_nickname', '', '');
        //$channel_nickname = '用户'.str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $channel_user_avatar = RequestHelper::post('channel_user_avatar', '', '');
        $source          = RequestHelper::post('dev', '1', '');
        $mobile          = RequestHelper::post('mobile', '', '');
        $code            = RequestHelper::post('code', '', '');
        if (empty($channel)) {
            $this->returnJsonMsg('614', [], Common::C('code', '614'));
        }
        if (!in_array($channel, ['1', '2', '3', '4'])) {
            $this->returnJsonMsg('615', [], Common::C('code', '615'));
        }
        if (empty($channel_user_id)) {
            $this->returnJsonMsg('613', [], Common::C('code', '613'));
        }
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        if (empty($code)) {
            $this->returnJsonMsg('608', [], Common::C('code', '608'));
        }
        $user_verify_code_model = new UserVerifyCode();
        $user_verify_code_where['mobile'] = $mobile;
        $user_verify_code_where['code']   = $code;
        $user_verify_code_where['type']   = '4';
        $user_verify_code_fields = 'id,expires_in';
        $user_verify_code_info = $user_verify_code_model->getInfo($user_verify_code_where, true, $user_verify_code_fields);
        if ($user_verify_code_info) {
            if (strtotime($user_verify_code_info['expires_in']) < time()) {
                $this->returnJsonMsg('609', [], Common::C('code', '609'));
            }
        } else {
            $this->returnJsonMsg('610', [], Common::C('code', '610'));
        }
        $user_channel_model = new UserChannel();
        $user_channel_where['channel'] = $channel;
        $user_channel_where['channel_user_id'] = $channel_user_id;
        $user_channel_where['status'] = '1';
        $user_channel_info = $user_channel_model->getInfo($user_channel_where, true, 'id,mobile');
        if (empty($user_channel_info)) {
            $this->returnJsonMsg('616', [], Common::C('code', '616'));
        } else {
            if (!empty($user_channel_info['mobile'])) {
                $this->returnJsonMsg('618', [], Common::C('code', '618'));
            }
        }
        $user_channel_update['mobile'] = $mobile;
        $user_channel_update['source'] = $source;
        $user_channel_update_where['id'] = $user_channel_info['id'];
        $rs = $user_channel_model->updateInfo($user_channel_update, $user_channel_update_where);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $user_model = new User();
        $user_where['mobile']     = $mobile;
        //$user_where['is_deleted'] = '2';
        $user_fields = 'id,mobile,is_deleted';
        $user_info = $user_model->getInfo($user_where, true, $user_fields);
        if (empty($user_info) || $user_info['is_deleted'] == 1) {
            $user_add_data['mobile']   = $mobile;
            $user_add_data['salt']     = Common::getRandomNumber();
            $password_random = Common::getRandomNumber();
            $user_add_data['password'] = md5($user_add_data['salt'].md5($password_random));
            $res = $user_model->insertInfo($user_add_data);
            if ($res) {
                /**同时记录UserBaseInfo**/
                $user_base_model = new UserBasicInfo();
                $user_base_data['uid'] = $res;
                $user_base_data['mobile'] = $mobile;
                $user_base_data['nickname'] = $channel_nickname;
                $user_base_data['avatar'] = $channel_user_avatar;
                $user_base_model->insertInfo($user_base_data);
                /**给用户发短信**/
                $sms_content = Common::getSmsTemplate(4, $password_random);
                $user_sms_data['mobile']  = $mobile;
                $user_sms_data['content'] = $sms_content;
                if (!$this->saveUserSms($user_sms_data)) {
                    $this->returnJsonMsg('619', [], Common::C('code', '619'));
                }
                /**发送短信通道**/
                $rs = $this->sendSmsChannel($mobile, $sms_content);
                if (!$rs) {
                    $this->returnJsonMsg('619', [], Common::C('code', '619'));
                }
                /**环信注册**/
                $hx_rs = HuanXinHelper::hxRegister($mobile, Common::C('passwordCode'), $channel_nickname);
                if (empty($hx_rs)) {
                    $this->returnJsonMsg('626', ['first_login'=>'1'], Common::C('code', '626'));
                }
                $hx_rs['code'] = ArrayHelper::getValue($hx_rs, 'code', '0');
                if ($hx_rs['code'] == '101') {
                    $this->returnJsonMsg('639', [], Common::C('code', '639'));
                }
                $this->returnJsonMsg('200', ['first_login'=>'1'], '绑定成功');
            } else {
                $this->returnJsonMsg('400', [], Common::C('code', '400'));
            }


        } else {
            $this->returnJsonMsg('200', ['first_login'=>'2'], '已经绑定');
        }
    }
}
