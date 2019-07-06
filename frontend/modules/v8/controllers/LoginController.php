<?php
/**
 * 登陆
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Login
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      2015/8/05 09:21
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */
namespace frontend\modules\v8\controllers;

use frontend\models\i500m\YpUser;
use Yii;
use yii\helpers\ArrayHelper;
use common\helpers\Common;
use common\helpers\RequestHelper;
use common\helpers\LoulianHelper;
use common\helpers\CurlHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserToken;
use frontend\models\i500_social\UserChannel;
use frontend\models\i500_social\LoginLog;
use frontend\models\i500_social\Logincommunity;
use frontend\models\i500_social\UserVerifyCode;
use frontend\models\i500_social\VerificationCode;
use frontend\models\i500_social\InviteCode;
use frontend\models\i500_social\UserFriends;
use frontend\models\i500m\Community;
use frontend\models\i500m\Province;
use frontend\models\i500m\City;
use frontend\models\i500m\District;

/**
 * Login
 *
 * @category Social
 * @package  Login
 * @author   liuyanwei <liuyanwei@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     liuyanwei@i500m.com
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
        $info = $user_info_model->getInfo(['mobile'=>$mobile], true, 'nickname,avatar,personal_sign,is_recruit,realname,address,last_community_id,province_id,city_id,district_id,is_pioneer,create_time');
		$num = $user_info_model::find()->where(['last_community_id'=>$info['last_community_id']])->count();
        if($info['is_pioneer'] == 1) {
            if($num < 10) {
                $expire_time = strtotime($info['create_time']) + 86400 * 21;
                if($expire_time < time()) {
                    $end = $user_info_model::find()->where(['last_community_id'=>$info['last_community_id']])->orderBy('create_time asc')->offset(1)->limit(1)->asArray()->one();
                    
                    $pioneer = $user_info_model::updateAll(['is_pioneer'=>1], ['mobile'=>$end['mobile']]);
                    $channel_id = User::find()->select('channel_id')->where(['mobile'=>$end['mobile']])->scalar();
                    if (!empty($channel_id))
                    {

                        $channel = explode('-', $channel_id);
                        $data['device_type'] = ArrayHelper::getValue($channel, 0);
                        $data['channel_id'] = ArrayHelper::getValue($channel, 1);
                        $data['type'] = 9;//添加好友标识   3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9加入社区
                        $data['title'] = $end['nickname'].'已成功成为建设者，请在21天内邀请到满足建成小区的人数';
                        $data['description'] = $end['nickname'].'已成功成为建设者，请在21天内邀请到满足建成小区的人数';
                        $channel_url = \Yii::$app->params['channelHost'] . 'v1/push';
                        $re = CurlHelper::post($channel_url, $data);
                    }

                    $channel_id1 = User::find()->select('xg_channel_id')->where(['mobile'=>$end['mobile']])->scalar();
                    if(!empty($channel_id1))
                    {
                        $channel1 = explode('-', $channel_id1);
                        $data1['device_type'] = ArrayHelper::getValue($channel1, 0);
                        $data1['channel_id'] = ArrayHelper::getValue($channel1, 1);
                        $data1['type'] = 9;//添加好友标识   3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9加入社区
                        $data1['title'] = $end['nickname'].'已成功成为建设者，请在21天内邀请到满足建成小区的人数';
                        $data1['description'] = $end['nickname'].'已成功成为建设者，请在21天内邀请到满足建成小区的人数';
                        $channel_url1 = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
                        $re = CurlHelper::post($channel_url1, $data1);
                    }



                    $channel_id = User::find()->select('channel_id')->where(['mobile'=>$mobile])->scalar();
                    $pioneer = $user_info_model::updateAll(['is_pioneer' => 0, 'create_time'=>date('Y-m-d H:i:s', time())], ['mobile'=>$mobile]);
                    if (!empty($channel_id)) 
                    {
                        $channel = explode('-', $channel_id);
                        $data['device_type'] = ArrayHelper::getValue($channel, 0);
                        $data['channel_id'] = ArrayHelper::getValue($channel, 1);
                        $data['type'] = 9;//添加建设者标识   3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9加入社区
                        $data['title'] = $info['nickname'].'未能在21天内成功邀请到9个人，已取消建设者身份';
                        $data['description'] = $info['nickname'].'未能在21天内成功邀请到9个人，已取消建设者身份';
                        $channel_url = \Yii::$app->params['channelHost'] . 'v1/push';
                        $re = CurlHelper::post($channel_url, $data);
                    }

                    $channel_id2 = User::find()->select('xg_channel_id')->where(['mobile'=>$mobile])->scalar();
                    if(!empty($channel_id2))
                    {
                        $channel2 = explode('-', $channel_id2);
                        $data2['device_type'] = ArrayHelper::getValue($channel, 0);
                        $data2['channel_id'] = ArrayHelper::getValue($channel, 1);
                        $data2['type'] = 9;//添加建设者标识   3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9加入社区
                        $data2['title'] = $info['nickname'].'未能在21天内成功邀请到9个人，已取消建设者身份';
                        $data2['description'] = $info['nickname'].'未能在21天内成功邀请到9个人，已取消建设者身份';
                        $channel_url1 = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
                        $re = CurlHelper::post($channel_url1, $data2);
                    }
                }
            }
        }
        
        /**更新token**/
        $user_token_data['token']       = md5($mobile.time());
        $user_token_data['expired_in'] = time() + 3600 * 24 * 30 * 3;
        $user_model->updateInfo($user_token_data, ['mobile'=>$mobile]);

        $login_log_model = new LoginLog();
        $login_log_data['uid']      = $user_info['id'];
        $login_log_data['mobile']   = $mobile;
        $login_log_data['login_ip'] = Common::getIp();
        $login_log_data['channel']  = $channel;
        $login_log_model->insertInfo($login_log_data);

		//获取对应LoginCommunity表信息
		$login_community = new Logincommunity();
		$community = $login_community::find()->select(['id','community_id','community_city_id','mobile','modify_time','is_deleted','community_name','address','lng','lat','join_in'])->where(['mobile'=>$mobile, 'is_deleted'=>0, 'join_in'=>1])->asArray()->one();
		
        $verificationcode = new VerificationCode();
        $verificat = $verificationcode::find()->select(['open_invitation_code', 'open_relation_community', 'mention'])->asArray()->one();
		
        $user = new User();
        $rest = $user::find()->select(['is_verification_code', 'step'])->where(['mobile' => $mobile])->asArray()->one();

        $conm = new Community();
        $community_name = $conm::find()->select(['id','name'])->where(['id'=>$info['last_community_id']])->asArray()->one();

        $provin_name = Province::find()->select(['id','name'])->where(['id'=>$info['province_id']])->asArray()->one();

        $city_name = City::find()->select(['id', 'name'])->where(['id'=>$info['city_id']])->asArray()->one();

        $area_name = District::find()->select(['id', 'name'])->where(['id'=>$info['district_id']])->asArray()->one();

        $rs_u_info['id']     = $user_info['id'];
        $rs_u_info['mobile'] = $mobile;
        $rs_u_info['token']  = $user_token_data['token'];
        $rs_u_info['nickname']  = ArrayHelper::getValue($info, 'nickname', '');
        $rs_u_info['avatar']  = ArrayHelper::getValue($info, 'avatar', '');
        $rs_u_info['personal_sign']  = ArrayHelper::getValue($info, 'personal_sign', '');
		$rs_u_info['is_recruit']  = ArrayHelper::getValue($info, 'is_recruit', '');
        $rs_u_info['is_pioneer']  = ArrayHelper::getValue($info, 'is_pioneer', '');
        $rs_u_info['community_num'] = $num;
        $rs_u_info['register_realname']  = ArrayHelper::getValue($info, 'nickname', '');
        $rs_u_info['register_address']  = ArrayHelper::getValue($info, 'address', '');
        $rs_u_info['register_community_id']  = ArrayHelper::getValue($info, 'last_community_id', '');
        $rs_u_info['register_province_id']  = ArrayHelper::getValue($info, 'province_id', '');
        $rs_u_info['register_city_id']  = ArrayHelper::getValue($info, 'city_id', '');
        $rs_u_info['register_area_id']  = ArrayHelper::getValue($info, 'district_id', '');
        $rs_u_info['register_community_name'] = ArrayHelper::getValue($community_name, 'name', '');
        $rs_u_info['register_province_name'] = ArrayHelper::getValue($provin_name, 'name', '');
        $rs_u_info['register_city_name'] = ArrayHelper::getValue($city_name, 'name', '');
        $rs_u_info['register_area_name'] = ArrayHelper::getValue($area_name, 'name', '');
        $rs_u_info['logincommunity_id'] = ArrayHelper::getValue($community, 'id', '');
		$rs_u_info['community_id'] = ArrayHelper::getValue($community, 'community_id', '');
		$rs_u_info['community_city_id'] = ArrayHelper::getValue($community, 'city_id', '');
		$rs_u_info['logincommunity_mobile'] = ArrayHelper::getValue($community, 'mobile', '');
		$rs_u_info['modify_time'] = ArrayHelper::getValue($community, 'modify_time', '');
		$rs_u_info['is_deleted'] = ArrayHelper::getValue($community, 'is_deleted', '');
		$rs_u_info['community_name'] = ArrayHelper::getValue($community, 'community_name', '');
		$rs_u_info['address'] = ArrayHelper::getValue($community, 'address', '');
		$rs_u_info['lng'] = ArrayHelper::getValue($community, 'lng', '');
		$rs_u_info['lat'] = ArrayHelper::getValue($community, 'lat', '');
		$rs_u_info['join_in'] = ArrayHelper::getValue($community, 'join_in', '');
		$rs_u_info['open_invitation_code'] = ArrayHelper::getValue($verificat, 'open_invitation_code', '');
        $rs_u_info['open_relation_community'] = ArrayHelper::getValue($verificat, 'open_relation_community', '');
        $rs_u_info['is_verification_code'] = ArrayHelper::getValue($rest, 'is_verification_code', '');
        $rs_u_info['step'] = ArrayHelper::getValue($rest, 'step', '');
        $rs_u_info['mention'] = ArrayHelper::getValue($verificat, 'mention', '');
        $rs_u_info['fmobile'] = "";
        $rs_u_info['fname'] = "";
        $rs_u_info['relation'] = "";

        //注册露脸
        // $loulian_re = LouLianHelper::llRegister($mobile, $rs_u_info['nickname'], $rs_u_info['avatar']);
        // LouLianHelper::updateUserInfo($mobile, $rs_u_info['nickname'], $rs_u_info['avatar']);
        // $rs_u_info['loulian'] = $loulian_re;
        
        $loulian_re = [
            "error_code"=> 2000,
            "user"=>[
                "session_id"=>"caf56566d9ed4675892aeb3894930f10",
                "id"=>"13718337444"
            ]
        ];
        $rs_u_info['loulian'] = $loulian_re;
        $this->returnJsonMsg('200', $rs_u_info, Common::C('code', '200'));
    }

    /**
     * 地铁聊天室发送验证码
     *
     * Param string $mobile   手机号
     *
     * @return array
     */
    public function actionBindChatSendcode()
    {
        $mobile = RequestHelper::post('mobile', '', '');
		
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $type   = RequestHelper::post('type', '', '0');

        $user_model = new User();
        $user_where['mobile'] = $mobile;
       // $user_where['is_deleted'] = '2';
        $user_fields = 'id,mobile';
        $user_info = $user_model->getInfo($user_where, true, $user_fields);

        if ($type != 5) {
            $this->result['code'] = 601;
            $this->result['message'] = '无效的验证码类型';
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
            } else {
                $this->result['message'] = '发送成功';
            }
        }
        return $this->response();

    }

    /**
     * 地铁聊天室绑定用户
     *
     * Param string $mobile 手机号
     *
     * @return array
     */
    public function actionBindChatUser()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $openid = RequestHelper::post('openid', '', '');
        if (empty($openid)) {
            $this->returnJsonMsg('604', [], '错误参数');
        }
        $code = RequestHelper::post('code', '', '');
        if (empty($code)) {
            $this->returnJsonMsg('604', [], '错误参数');
        }

        $user_verify_code_model = new UserVerifyCode();
        $user_verify_code_where['mobile'] = $mobile;
        $user_verify_code_where['code']   = $code;
        $user_verify_code_where['type']   = '5';
        $user_verify_code_fields = 'id,expires_in';
        $user_verify_code_info = $user_verify_code_model->getInfo($user_verify_code_where, true, $user_verify_code_fields);
        if ($user_verify_code_info) {
            if (strtotime($user_verify_code_info['expires_in']) < time()) {
                $this->returnJsonMsg('609', [], Common::C('code', '609'));
            }
        } else {
            $this->returnJsonMsg('610', [], Common::C('code', '610'));
        }

        $user_model = new User();
        $user_info = $user_model->getInfo(array("openid"=>$openid), true, 'mobile',array('<>','mobile',$mobile));
        if($user_info){
            $this->returnJsonMsg('610', [], '该微信号已经绑订电话');
        } else {

            $user_model = new User();
            $user = $user_model->getInfo(array('mobile' => $mobile), true, 'mobile,id');
            if (!empty($user)) {
                $user_model = new User();
                $user_update_data['openid'] = $openid;
                $user_update_where['id']  = $user['id'];

                $rs = $user_model->updateInfo($user_update_data,$user_update_where);
                if (!$rs) {
                    $this->returnJsonMsg('612', [], Common::C('code', '612'));
                }
            } else {
				$connection = \Yii::$app->db_social;
                $transaction = $connection->beginTransaction();
                $usermodel = new User();
                $usermodel->mobile = $mobile;
                $usermodel->openid = $openid;
                $usermodel->salt = mt_rand(100000, 999999);
                $usermodel->password = md5($usermodel->salt."123456");
                $usermodel->expired_in = time() + 3600 * 24 * 30 * 3;//token过期时间
                $res = $usermodel->save(false);
                if($res){
                    /**同时记录UserBaseInfo**/
                    $user_base_model = new UserBasicInfo();
                    $user_base_model['mobile'] = $mobile;
                    $res1 = $user_base_model->save(false);
					if($res1){
                        $transaction->commit();
                    }else{
                        $transaction->rollBack();
                    }
                } else {
					$transaction->rollBack();
                    $this->returnJsonMsg('500', [], '网络忙');
                }
            }
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
    /**
     * 验证邀请码
     * @return array
     */
    public function actionValidateCode()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        //判断是否开启邀请码验证
        $verificat = VerificationCode::find()->select(['open_invitation_code', 'open_relation_community', 'valid_days'])->asArray()->one();
        if ($verificat['open_invitation_code'] == 1) {
            $this->returnJsonMsg('655', [], Common::C('code', '655'));
        }
        
        //判断用户是否已验证
        $rest = User::find()->select(['is_verification_code', 'step'])->where(['mobile' => $mobile])->asArray()->one();
        if ($rest['is_verification_code'] == 1) {
            $this->returnJsonMsg('648', [], Common::C('code', '648'));
        }
        $code = RequestHelper::post('code', '', '');
        if (empty($code)) {
            $this->returnJsonMsg('656', [], Common::C('code', '656'));
        }
        $code = strtolower($code);
        //查询邀请码相关所需值
        $invite_code_model = InviteCode::find()->select(['mobile', 'community_id', 'code', 'create_time'])->where(['code' => $code])->asArray()->one();
        //判断邀请码是否有效
        if (empty($invite_code_model)) {
            $this->returnJsonMsg('658', [], Common::C('code', '658'));
        } else {
            //判断验证码是否已失效
            $valid_day = $verificat['valid_days'] * 86400;
            $create_time = strtotime($invite_code_model['create_time']);
            $valid_time = $create_time + $valid_day;
            if ($valid_time < time()) {
                $this->returnJsonMsg('659', [], Common::C('code', '659'));
            }
            $time = date('Y-m-d H:i:s', time());
            //判断是否已是好友
            $userfriends = UserFriends::find()->where(['uid'=> $mobile, 'fid'=> $invite_code_model['mobile'], 'status' => '1'])->asArray()->all();
            //判断是否已添加好友
            if(empty($userfriends)) {
                $friend_model1 = new UserFriends();
                $friend_model1->uid = $mobile;
                $friend_model1->fid = $invite_code_model['mobile'];
                $friend_model1->status = 1;
                $friend_model1->create_time = $time;
                $friend_model1->agree_time = $time;
                $friend_model1->remark = '';
                $res = $friend_model1->save();

                $friend_model2 = new UserFriends();
                $friend_model2->uid = $invite_code_model['mobile'];
                $friend_model2->fid = $mobile;
                $friend_model2->status = 1;
                $friend_model2->create_time = $time;
                $friend_model2->agree_time = $time;
                $friend_model2->remark = '';
                $res1 = $friend_model2->save();
            }
            //验证是否关联小区        
            if ($verificat['open_relation_community'] == 0) {
                $community = Community::find()->select(['city', 'province', 'district'])->where(['id'=> $invite_code_model['community_id']])->asArray()->one();

                $pcdl['province_id'] = $community['province'];
                $pcdl['city_id'] = $community['city'];
                $pcdl['district_id'] = $community['district'];
                $pcdl['last_community_id'] = $invite_code_model['community_id'];
                //判断用户信息是否完整
                if(!empty($rest)) {
                    $ress = UserBasicInfo::find()->where(['mobile'=>$mobile])->asArray()->one();
                    if(!empty($ress)){
                        $result = UserBasicInfo::updateAll($pcdl, ['mobile'=>$mobile]);
                    }else{
                        $user_base_model = new UserBasicInfo();
                        $user_base_data['mobile'] = $mobile;
                        $ubm = $user_base_model->insertInfo($user_base_data);
                        $result = UserBasicInfo::updateAll($pcdl, ['mobile'=>$mobile]);
                    }
                }
            }
            //修改user表，将用户改成已验证邀请码
            $result = User::updateAll(['is_verification_code'=> '1'],['mobile'=>$mobile]);
            if($rest['step'] == 0){
                $result = User::updateAll(['step'=> '1'],['mobile'=>$mobile]);
            }
            $this->returnJsonMsg('200', [], Common::C('code', '200'));
        }
    }
}
