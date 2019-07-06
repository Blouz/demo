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
namespace frontend\modules\v1\controllers;

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
     * Before
     * @param \yii\base\Action $action Action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }
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
        $source          = RequestHelper::post('dev', '1', '');
        $login_ua        = RequestHelper::post('json_str', '', '');
        $mobile          = RequestHelper::post('mobile', '', 'trim');
        $password        = RequestHelper::post('password', '', 'trim');
        $code            = RequestHelper::post('code', '', 'trim');
        $first_login     = RequestHelper::post('first_login', '2', '');
        if ($type != '3') {
            /**第三方登陆不传递手机号**/
            if (empty($mobile)) {
                $this->returnJsonMsg('604', [], Common::C('code', '604'));
            }
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }
        if ($type == '1') {
            /**普通登陆**/
            if (empty($password)) {
                $this->returnJsonMsg('606', [], Common::C('code', '606'));
            }
            $user_model = new User();
            $user_where['mobile']     = $mobile;
            $user_where['is_deleted'] = '2';
            $user_fields = 'id,mobile,password,salt,login_count,status';
            $user_info = $user_model->getInfo($user_where, true, $user_fields);
            if (!empty($user_info)) {
                if ($user_info['status'] == '1') {
                    $this->returnJsonMsg('601', [], Common::C('code', '601'));
                }
                $password_1 = md5($user_info['salt'].$password);
                if ($password_1 != $user_info['password']) {
                    $this->returnJsonMsg('607', [], Common::C('code', '607'));
                }
            } else {
                $this->returnJsonMsg('602', [], Common::C('code', '602'));
            }
        } elseif ($type == '2') {
            /**验证码登陆**/
            if (empty($code)) {
                $this->returnJsonMsg('608', [], Common::C('code', '608'));
            }
            $user_verify_code_model = new UserVerifyCode();
            $user_verify_code_where['mobile'] = $mobile;
            $user_verify_code_where['code']   = $code;
            $user_verify_code_where['type']   = '1';
            $user_verify_code_fields = 'id,expires_in';
            $user_verify_code_info = $user_verify_code_model->getInfo($user_verify_code_where, true, $user_verify_code_fields, '', 'id desc');
            if ($user_verify_code_info) {
                if (strtotime($user_verify_code_info['expires_in']) < time()) {
                    $this->returnJsonMsg('609', [], Common::C('code', '609'));
                }
            } else {
                $this->returnJsonMsg('610', [], Common::C('code', '610'));
            }
        } elseif ($type == '3') {
            /**第三方平台登录**/
            if (empty($channel_user_id)) {
                $this->returnJsonMsg('613', [], Common::C('code', '613'));
            }
            if (empty($channel)) {
                $this->returnJsonMsg('614', [], Common::C('code', '614'));
            }
            if (!in_array($channel, ['1', '2', '3', '4'])) {
                $this->returnJsonMsg('615', [], Common::C('code', '615'));
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
        /**环信登陆**/
        //HuanXinHelper::hxLogin($mobile, Common::C('passwordCode'));
        /**成功后记录日志**/
        $user_m = new User();
        $user_cond['mobile']     = $mobile;
        $user_cond['is_deleted'] = '2';
        $user_info = $user_m->getInfo($user_cond, true, 'id,login_count,salt,status');
        if (empty($user_info)) {
            $this->returnJsonMsg('602', [], Common::C('code', '602'));
        }
        if ($user_info['status'] == '1') {
            $this->returnJsonMsg('601', [], Common::C('code', '601'));
        }
        if ($type != '1') {
            $password_random = Common::getRandomNumber();
            if ($first_login == '1') {
                $user_update_data['password']  = md5($user_info['salt'].md5($password_random));
            }
        }
        $user_update_data['last_login_ip']      = Common::getIp();
        $user_update_data['last_login_channel'] = $channel;
        $user_update_data['last_login_source']  = $source;
        $user_update_data['last_login_time']    = date('Y-m-d H:i:s', time());
        $user_update_data['login_count']        = $user_info['login_count'] + 1;
        $user_update_where['mobile'] = $mobile;
        $user_m->updateInfo($user_update_data, $user_update_where);

        $login_log_model = new LoginLog();
        $login_log_data['uid']      = $user_info['id'];
        $login_log_data['mobile']   = $mobile;
        $login_log_data['login_ip'] = Common::getIp();
        $login_log_data['channel']  = $channel;
        $login_log_data['source']   = $source;
        $login_log_data['login_ua'] = $login_ua;
        $login_log_model->insertInfo($login_log_data);

        $user_token_model = new UserToken();
        $user_token_where['mobile'] = $mobile;
        $user_token_fields = 'id,token';
        $user_token_info = $user_token_model->getInfo($user_token_where, true, $user_token_fields);
        if ($user_token_info) {
            /**更新token**/
            $user_token_data['token']       = md5($mobile.time());
            $user_token_data['create_time'] = date('Y-m-d H:i:s', time());
            $user_token_model->updateInfo($user_token_data, $user_token_where);
        } else {
            /**插入token**/
            $user_token_data['mobile'] = $mobile;
            $user_token_data['token']  = md5($mobile.time());
            $user_token_model->insertInfo($user_token_data);
        }

        if ($first_login == '1' && $type != '1') {
            /**给用户发短信**/
            $sms_content = Common::getSmsTemplate(2, $password_random);
            $user_sms_data['mobile']  = $mobile;
            $user_sms_data['content'] = $sms_content;
            $this->saveUserSms($user_sms_data);
            /**发送短信通道**/
            $this->sendSmsChannel($mobile, $sms_content);
        }
        $rs_u_info['id']     = $user_info['id'];
        $rs_u_info['mobile'] = $mobile;
        $rs_u_info['token']  = $user_token_data['token'];
        //记录用户活跃时间
        $this->saveUserActiveTime(['mobile'=>$mobile]);
        $this->returnJsonMsg('200', $rs_u_info, Common::C('code', '200'));
    }

    /**
     * 注册
     * @return array
     */
    public function actionRegister()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $password = RequestHelper::post('password', '', 'trim');
        $code     = RequestHelper::post('code', '', 'trim');

        if (empty($code)) {
            $this->returnJsonMsg('608', [], Common::C('code', '608'));
        }
        $user_model = new User();
        $user_where['mobile']     = $mobile;
        $user_where['is_deleted'] = '2';
        $user_fields = 'id,mobile';
        $user_info = $user_model->getInfo($user_where, true, $user_fields);
        if (!empty($user_info)) {
            /**存在该用户**/
            $this->returnJsonMsg('620', [], Common::C('code', '620'));
        }
        $user_verify_code_model = new UserVerifyCode();
        $user_verify_code_where['mobile'] = $mobile;
        $user_verify_code_where['code']   = $code;
        $user_verify_code_where['type']   = '3';
        $user_verify_code_fields = 'id,expires_in';
        $user_verify_code_info = $user_verify_code_model->getInfo($user_verify_code_where, true, $user_verify_code_fields, '', 'id desc');
        if ($user_verify_code_info) {
            if (strtotime($user_verify_code_info['expires_in']) < time()) {
                $this->returnJsonMsg('609', [], Common::C('code', '609'));
            }
        } else {
            $this->returnJsonMsg('610', [], Common::C('code', '610'));
        }
        $user_model = new User();
        $user_data['mobile']   = $mobile;
        $user_data['salt']     = Common::getRandomNumber();
        $user_data['password'] = md5($user_data['salt'].$password);
        $rs = $user_model->insertInfo($user_data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        /**环信注册**/
        // $hx_rs = HuanXinHelper::hxRegister($mobile, Common::C('passwordCode'), Common::C('defaultNickName'));
        // if (empty($hx_rs)) {
        //     $this->returnJsonMsg('626', [], Common::C('code', '626'));
        // }
        // $hx_rs['code'] = ArrayHelper::getValue($hx_rs, 'code', '0');
        // if ($hx_rs['code'] == '101') {
        //     $this->returnJsonMsg('639', [], Common::C('code', '639'));
        // }
        
        //记录用户活跃时间
        $this->saveUserActiveTime(['mobile'=>$mobile]);
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
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
        switch ($type) {
            case '1' :
                /**登陆页获取验证码**/
                $this->_loginPageSendCode($mobile);
                break;
            case '2' :
                /**找回密码页获取验证码**/
                $this->_findPwdSendCode($mobile);
                break;
            case '3' :
                /**注册获取验证码**/
                $this->_regSendCode($mobile);
                break;
            case '4' :
                /**绑定用户获取验证码**/
                $this->_bindUserSendCode($mobile);
                break;
            case '5' :
                /**绑定银行卡页面获取验证码**/
                $this->_bindUserBankCardSendCode($mobile);
                break;
        }
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
        //$channel_nickname = RequestHelper::post('channel_nickname', '', '');
        $channel_nickname = '用户'.str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
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
        $user_verify_code_info = $user_verify_code_model->getInfo($user_verify_code_where, true, $user_verify_code_fields, '', 'id desc');
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
        $user_where['is_deleted'] = '2';
        $user_fields = 'id,mobile';
        $user_info = $user_model->getInfo($user_where, true, $user_fields);
        if (empty($user_info)) {
            $user_add_data['mobile']   = $mobile;
            $user_add_data['salt']     = Common::getRandomNumber();
            $password_random = Common::getRandomNumber();
            $user_add_data['password'] = md5($user_add_data['salt'].md5($password_random));
            $rs = $user_model->insertOneRecord($user_add_data);
            if ($rs['result'] != '1') {
                $this->returnJsonMsg('400', [], Common::C('code', '400'));
            }
            /**同时记录UserBaseInfo**/
            $user_base_model = new UserBasicInfo();
            $user_base_data['uid'] = $rs['data']['new_id'];
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
            $this->returnJsonMsg('200', ['first_login'=>'1'], Common::C('code', '200'));
        } else {
            $this->returnJsonMsg('200', ['first_login'=>'2'], Common::C('code', '200'));
        }
    }
    /**
     * 登陆页获取验证码
     * @param string $mobile 手机号
     * @return array
     */
    private function _loginPageSendCode($mobile = '')
    {
        $user_model = new User();
        $user_where['mobile']     = $mobile;
        $user_where['is_deleted'] = '2';
        $user_fields = 'id,mobile,salt,status';
        $user_info = $user_model->getInfo($user_where, true, $user_fields);
        $first_login = 2;
        if (empty($user_info)) {
            /**未存在该用户**/
            $first_login = 1;
            $user_add_data['mobile'] = $mobile;
            $user_add_data['salt']   = Common::getRandomNumber();
            $rs = $user_model->insertInfo($user_add_data);
            if (!$rs) {
                $this->returnJsonMsg('400', [], Common::C('code', '400'));
            }
            /**环信注册**/
            $hx_rs = HuanXinHelper::hxRegister($mobile, Common::C('passwordCode'), Common::C('defaultNickName'));
            if (empty($hx_rs)) {
                $this->returnJsonMsg('626', ['first_login'=>'1'], Common::C('code', '626'));
            }
            $hx_rs['code'] = ArrayHelper::getValue($hx_rs, 'code', '0');
            if ($hx_rs['code'] == '101') {
                $this->returnJsonMsg('639', [], Common::C('code', '639'));
            }
        }
        /**发送验证码**/
        $user_verify_code_model = new UserVerifyCode();
        $user_verify_code_data['mobile']     = $mobile;
        $user_verify_code_data['code']       = Common::getRandomNumber();
        $user_verify_code_data['type']       = '1';
        $user_verify_code_data['expires_in'] = date('Y-m-d H:i:s', (time()+ Common::C('verify_code_timeout')));
        $rs = $user_verify_code_model->insertInfo($user_verify_code_data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $sms_content = Common::getSmsTemplate(1, $user_verify_code_data['code']);
        /**保存短信数据**/
        $user_sms_data['mobile']  = $mobile;
        $user_sms_data['content'] = $sms_content;
        if (!$this->saveUserSms($user_sms_data)) {
            $this->returnJsonMsg('611', [], Common::C('code', '611'));
        }
        /**发送短信通道**/
        // $rs = $this->sendSmsChannel($mobile, $sms_content);
        // if (!$rs) {
        //     $this->returnJsonMsg('611', [], Common::C('code', '611'));
        // }
        $this->returnJsonMsg('200', ['first_login'=>$first_login,"code"=>Common::getRandomNumber()], Common::C('code', '200'));
    }

    /**
     * 找回密码页获取验证码
     * @param string $mobile 手机号
     * @return array
     */
    private function _findPwdSendCode($mobile = '')
    {
        $user_model = new User();
        $user_where['mobile']     = $mobile;
        $user_where['is_deleted'] = '2';
        $user_fields = 'id,mobile';
        $user_info = $user_model->getInfo($user_where, true, $user_fields);
        if (empty($user_info)) {
            /**未存在该用户**/
            $this->returnJsonMsg('602', [], Common::C('code', '602'));
        }
        /**发送验证码**/
        $user_verify_code_model = new UserVerifyCode();
        $user_verify_code_data['mobile']     = $mobile;
        $user_verify_code_data['code']       = Common::getRandomNumber();
        
        $user_verify_code_data['type']       = '2';
        $user_verify_code_data['expires_in'] = date('Y-m-d H:i:s', (time()+ Common::C('verify_code_timeout')));
        $rs = $user_verify_code_model->insertInfo($user_verify_code_data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $sms_content = Common::getSmsTemplate(3, $user_verify_code_data['code']);
        /**保存短信数据**/
        $user_sms_data['mobile']  = $mobile;
        $user_sms_data['content'] = $sms_content;
        if (!$this->saveUserSms($user_sms_data)) {
            $this->returnJsonMsg('611', [], Common::C('code', '611'));
        }
        /**发送短信通道**/
        // $rs = $this->sendSmsChannel($mobile, $sms_content);
        // if (!$rs) {
        //     $this->returnJsonMsg('611', [], Common::C('code', '611'));
        // }
        $this->returnJsonMsg('200', ['first_login'=>'2',"code"=>$user_verify_code_data['code']], Common::C('code', '200'));
    }

    /**
     * 注册发送验证码
     * @param string $mobile 手机号
     * @return array
     */
    private function _regSendCode($mobile = '')
    {

        $user_model = new User();
        $user_where['mobile']     = $mobile;
        $user_where['is_deleted'] = '2';
        $user_fields = 'id,mobile';
        $user_info = $user_model->getInfo($user_where, true, $user_fields);
        if (!empty($user_info)) {
            /**存在该用户**/
            $this->returnJsonMsg('620', [], Common::C('code', '620'));
        }

        /**发送验证码**/
        $user_verify_code_model = new UserVerifyCode();
        $user_verify_code_data['mobile']     = $mobile;
        $user_verify_code_data['code']       = Common::getRandomNumber();
        $user_verify_code_data['type']       = '3';  //注册发送验证码
        $user_verify_code_data['expires_in'] = date('Y-m-d H:i:s', (time()+ Common::C('verify_code_timeout')));
        $rs = $user_verify_code_model->insertInfo($user_verify_code_data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $sms_content = Common::getSmsTemplate(5, $user_verify_code_data['code']);
        /**保存短信数据**/
        $user_sms_data['mobile']  = $mobile;
        $user_sms_data['content'] = $sms_content;
        if (!$this->saveUserSms($user_sms_data)) {
            $this->returnJsonMsg('611', [], Common::C('code', '611'));
        }


        /**发送短信通道**/
        // $rs = $this->sendSmsChannel($mobile, $sms_content);
        // if (!$rs) {
        //     $this->returnJsonMsg('611', [], Common::C('code', '611'));
        // }
        
        $this->returnJsonMsg('200', ['first_login'=>'1',"code"=>Common::getRandomNumber()], Common::C('code', '200'));
    }

    /**
     * 绑定用户发送验证码
     * @param string $mobile 手机号
     * @return array
     */
    private function _bindUserSendCode($mobile = '')
    {
        /**发送验证码**/
        $user_verify_code_model = new UserVerifyCode();
        $user_verify_code_data['mobile']     = $mobile;
        $user_verify_code_data['code']       = Common::getRandomNumber();
        $user_verify_code_data['type']       = '4';  //绑定用户发送验证码
        $user_verify_code_data['expires_in'] = date('Y-m-d H:i:s', (time()+ Common::C('verify_code_timeout')));
        $rs = $user_verify_code_model->insertInfo($user_verify_code_data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $sms_content = Common::getSmsTemplate(6, $user_verify_code_data['code']);
        /**保存短信数据**/
        $user_sms_data['mobile']  = $mobile;
        $user_sms_data['content'] = $sms_content;
        if (!$this->saveUserSms($user_sms_data)) {
            $this->returnJsonMsg('611', [], Common::C('code', '611'));
        }
        /**发送短信通道**/
        $rs = $this->sendSmsChannel($mobile, $sms_content);
        if (!$rs) {
            $this->returnJsonMsg('611', [], Common::C('code', '611'));
        }
        $this->returnJsonMsg('200', ['first_login'=>'1'], Common::C('code', '200'));
    }

    /**
     * 绑定用户银行卡发送验证码
     * @param string $mobile 手机号
     * @return array
     */
    private function _bindUserBankCardSendCode($mobile = '')
    {
        /**发送验证码**/
        $user_verify_code_model = new UserVerifyCode();
        $user_verify_code_data['mobile']     = $mobile;
        $user_verify_code_data['code']       = Common::getRandomNumber();
        $user_verify_code_data['type']       = '5';  //绑定用户银行卡发送验证码
        $user_verify_code_data['expires_in'] = date('Y-m-d H:i:s', (time()+ Common::C('verify_code_timeout')));
        $rs = $user_verify_code_model->insertInfo($user_verify_code_data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $sms_content = Common::getSmsTemplate(7, $user_verify_code_data['code']);
        /**保存短信数据**/
        $user_sms_data['mobile']  = $mobile;
        $user_sms_data['content'] = $sms_content;
        if (!$this->saveUserSms($user_sms_data)) {
            $this->returnJsonMsg('611', [], Common::C('code', '611'));
        }
        /**发送短信通道**/
        $rs = $this->sendSmsChannel($mobile, $sms_content);
        if (!$rs) {
            $this->returnJsonMsg('611', [], Common::C('code', '611'));
        }
        $this->returnJsonMsg('200', ['first_login'=>'1'], Common::C('code', '200'));
    }
}
