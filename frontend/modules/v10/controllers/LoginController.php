<?php
/**
 * 登陆
 *
 * PHP Version 9
 *
 * @category  Social
 * @package   Login
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      2015/8/05 09:21
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */
namespace frontend\modules\v10\controllers;

use frontend\models\i500_social\Group;
use frontend\models\i500_social\GroupMember;
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
use frontend\models\i500_social\Integral;
use common\helpers\TxyunHelper;
use yii\db\Query;
use common\vendor\tls_sig\php\sig;
use frontend\models\i500m\OpenUserCity;
/**
 * Login
 *
 * @category Social
 * @package  Login
 * @author   wangleilei <wangleilei@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     wangleilei@i500m.com
 */
class LoginController extends BaseController
{
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
                        $this->returnJsonMsg('422', [], '账号或密码错误');
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
        $info = $user_info_model->getInfo(['mobile'=>$mobile], true, 'nickname,avatar,personal_sign,is_recruit,realname,address,last_community_id,province_id,city_id,district_id,is_pioneer,create_time,lng,lat');
        //var_dump($info);exit;
        if($info['lng'] == 0 && $info['lat'] == 0) {
            $uploadcoor = '1';
        }else {
            $uploadcoor = '0';
        }
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
                        $data2['device_type'] = ArrayHelper::getValue($channel2, 0);
                        $data2['channel_id'] = ArrayHelper::getValue($channel2, 1);
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
        $user_token_data['last_login_time'] = date('Y-m-d H:i:s');
        $user_model->updateInfo($user_token_data, ['mobile'=>$mobile]);

        $login_log_model = new LoginLog();
        $login_log_data['uid']      = $user_info['id'];
        $login_log_data['mobile']   = $mobile;
        $login_log_data['login_ip'] = Common::getIp();
        $login_log_data['channel']  = $channel;
        $login_log_model->insertInfo($login_log_data);

	//获取对应Community_beijing表信息
//		$login_community = new Logincommunity();
//		$community = $login_community::find()->select(['id','community_id','community_city_id','mobile','modify_time','is_deleted','community_name','address','lng','lat','join_in'])->where(['mobile'=>$mobile, 'is_deleted'=>0, 'join_in'=>1])->asArray()->one();
        $dsn = Community::getDB()->dsn;
        $db = strstr($dsn,"dbname=");
        $name = str_replace("dbname=","",$db);
        $field[] = "id"; 
        $field[] = "mobile";
        $field[] = "last_community_id";
        $field[] = "last_community_city_id";
        $field[] = "address";
        $field[] = "join_in";
        $field[] = "create_time";
        $field[] = "tx_user_sig";
        $field[] = "tx_sig_expire";
        $field['open_contact'] = (new Query())->select('open_contact')->from("i500_user")->where("mobile=i500_user_basic_info.mobile");
        
//        $field['lat'] = (new Query())->select('lat')->from($name.".community_beijing")->where("id=i500_user_basic_info.last_community_id");
        $community = UserBasicInfo::find()->select($field)
        ->where(['mobile'=>$mobile])
        ->asArray()
        ->one();

        $verificationcode = new VerificationCode();
        $verificat = $verificationcode::find()->select(['open_invitation_code', 'open_relation_community', 'mention'])->asArray()->one();

        $user = new User();
        $rest = $user::find()->select(['is_verification_code', 'step'])->where(['mobile' => $mobile])->asArray()->one();

        $community_name = Community::find()->select(['id','name','lng','lat'])->where(['id'=>$info['last_community_id']])->asArray()->one();

        $provin_name = Province::find()->select(['id','name'])->where(['id'=>$info['province_id']])->asArray()->one();

        $city_name = City::find()->select(['id', 'name'])->where(['id'=>$info['city_id']])->asArray()->one();

        $area_name = District::find()->select(['id', 'name'])->where(['id'=>$info['district_id']])->asArray()->one();
        
        $is_open = OpenUserCity::find()->select(['is_open'])->where(['city_id'=>$info['city_id']])->scalar();

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
        $rs_u_info['create_time'] = ArrayHelper::getValue($community, 'create_time', '');
//        $rs_u_info['community_name'] = ArrayHelper::getValue($community, 'comm_name', '');
        $rs_u_info['lng'] = ArrayHelper::getValue($community_name, 'lng', '');
        $rs_u_info['lat'] = ArrayHelper::getValue($community_name, 'lat', '');
        $rs_u_info['join_in'] = ArrayHelper::getValue($community, 'join_in', '');
        $rs_u_info['open_invitation_code'] = ArrayHelper::getValue($verificat, 'open_invitation_code', '');
        $rs_u_info['open_relation_community'] = ArrayHelper::getValue($verificat, 'open_relation_community', '');
        $rs_u_info['is_verification_code'] = ArrayHelper::getValue($rest, 'is_verification_code', '');
        $rs_u_info['step'] = ArrayHelper::getValue($rest, 'step', '');
        $rs_u_info['mention'] = ArrayHelper::getValue($verificat, 'mention', '');
        $rs_u_info['uploadcoor'] = $uploadcoor;
        $rs_u_info['open_contact'] = ArrayHelper::getValue($community, 'open_contact', '');
        $rs_u_info['is_open'] = $is_open;
        $this->_authorized($mobile);
        
        $user_id = $user_info['id'];
        //腾讯云独立用户登录
        $nick = ArrayHelper::getValue($info, 'nickname', '');
        $avatar = ArrayHelper::getValue($info, 'avatar', '');
        $res = TxyunHelper::Regsiter($user_id, $nick, $avatar);
//        if($community['tx_user_sig']);
        $result = json_decode($res,true);
        
        if(!empty($res))
        {
            $rs_u_info['TxYun'] = json_decode($res,true);
        }
        $current_date = date("Y-m-d H:i:s", time());;
        $sig = $community['tx_user_sig'];
        if($result['ActionStatus']=='OK'&& $current_date>$community['tx_sig_expire'])
        {
            $day = date("Y-m-d H:i:s", strtotime("+179 day"));
            $sdkappid = Common::C('sdkappid');
            $private_key_path = Common::C('private_key_path');
            $generator = Common::C('generator');
            $user_sig = sig::signature($user_id,$sdkappid,$private_key_path,$generator);
            if(empty($user_sig))
            {
                $this->returnJsonMsg('70011', [], '注册腾讯云用户授权码生成失败');
            }
            $sig = $user_sig[0];
            UserBasicInfo::updateAll(['tx_user_sig'=>$sig,'tx_sig_expire'=>$day],['mobile'=>$mobile]);
        }
        $rs_u_info['user_sig'] = $sig;

        if(!empty($rest['step']) && $rest['step'] == 8) {
            //查询一下当前的用户所在小区有没有业主群
            $group = Group::find()->select(['community_id', 'group_id'])
                ->where(['owner_group' => '1', 'source' => 1, 'community_id' => $info['last_community_id']])->asArray()->one();

            if (empty($group)) {
                $data_name = '邻居议事厅';
                //创建业主群并且把当前小区的所有人都加入进来
                $model = TxyunHelper::Create_group('admin', 'Public', $data_name, Common::C('defaultGroup'));
                $model = json_decode($model, true);
                if (!empty($model) && $model['ActionStatus'] == 'OK') {
                    $group = new Group();
                    $group->community_id = $info['last_community_id'];
                    $group->name = $data_name;
                    $group->group_id = $model['GroupId'];
                    $group->image = Common::C('defaultGroup');
                    $group->desc = '';
                    $group->is_deleted = 2;
                    $group->owner_group = 1;
                    $group->create_time = date('Y-m-d H:i:s');
                    $group->source = 1;
                    $res = $group->save(false);

                    $user_mobile_data = [];
                    if ($res) {
                        $user_mobile_data[] = $user_id;
                        $join_in = TxyunHelper::Join_group($model['GroupId'], $mobile);
                        $join_in = json_decode($join_in, true);
                        if (!empty($join_in) && $join_in['ActionStatus'] == 'OK') {
                            $group_member = new GroupMember();
                            $group_member->group_id = $model['GroupId'];
                            $group_member->mobile = $mobile;
                            $group_member->nickname = $info['nickname'];
                            $group_member->role = 2;
                            $group_member->save(false);
                        }
                    }
                }
            } else {
                //当前登录用户在不在业主群
                $field = [];
                $field[] = 'i500_user_basic_info.mobile';
                $field[] = 'i500_user_basic_info.realname';
                $field[] = 'i500_user_basic_info.avatar';
                $field[] = 'i500_group_member.group_id';
                $field[] = 'i500_group_member.id';

                $group_id = GroupMember::find()->select($field)
                    ->join('LEFT JOIN', 'i500_user_basic_info', 'i500_user_basic_info.mobile=i500_group_member.mobile')
                    ->where(['group_id' => $group['group_id'], GroupMember::tableName() . '.mobile' => $mobile, 'is_deleted' => 2])
                    ->asArray()
                    ->one();
                if (empty($group_id)) {
                    $usermobile = [];
                    $usermobile[] = $user_id;
                    $join_in = TxyunHelper::Join_group($group['group_id'], $usermobile);
                    $join_in = json_decode($join_in, true);
                    if (!empty($join_in) && $join_in['ActionStatus'] == 'OK') {
                        $group_member = new GroupMember();
                        $group_member->group_id = $group['group_id'];
                        $group_member->mobile = $mobile;
                        $group_member->nickname = $info['nickname'];
                        $group_member->role = 2;
                        $group_member->save(false);
                    }
                }
            }
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
            return $this->response();
        }
        $model->attributes = $data;
        $model->expired_in = time() + 3600 * 24 * 30 * 3;//token过期时间
        if (!$model->save()) {   //validateCode方法验证保存信息失败
            if ($model->hasErrors()) { //存在验证错误信息
                $errors = $model->getFirstErrors();
                if(isset($errors)){ //是否有错误信息
                    if(isset($errors['mobile'])) { //返回手机号错误信息
                        if($errors['mobile'] == '此用户已经存在' && !isset($errors['password'])) { //用户存在,并且密码错误信息不为空
                            if(isset($errors['code'])) { //短信验证码有误，返回错误信息
                                $this->result['code'] = 601;
                                $this->result['message'] = $errors['code'];
                            }else { //验证码无误，密码错误
                                $info = User::find()->select(['salt'])->where(['mobile'=>$data['mobile']])->asArray()->one();
                                $password = md5($info['salt'].$data['password']);
                                //更新密码
                                $user = User::updateAll(['password'=> $password],['mobile'=>$data['mobile']]);
                                if($user == 0) {
                                    $this->result['code'] = 601;
                                    $this->result['message'] = '请输入新的密码';
                                }else{
                                    $verificat = VerificationCode::find()->select(['open_invitation_code', 'open_relation_community', 'mention'])->asArray()->one();
                                    
                                    $step = User::find()->select(['step'])->where(['mobile'=>$data['mobile']])->asArray()->one();
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
                                    $date['mention'] = $verificat['mention'];
                                    $this->result['code'] = 200;
                                    $this->result['data'] = $date;
                                    $this->result['message'] = 'OK';
                                }
                            }
                        }else{ //用户存在,并且密码错误信息不为空  这两个条件不同时满足
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
            } else {  //不存在验证错误信息，可能连接超时无响应
                $this->result['code'] = 500;
                $this->result['message'] = '网络忙';
            }

            return $this->response();
        } else { //帐户建立成功
            /**同时记录UserBaseInfo**/
            $user_base_model = new UserBasicInfo();
            $user_base_data['mobile'] = $model->mobile;
            $user_base_model->insertInfo($user_base_data);
            //注册成功给予相应积分
            $value = 'phone';
            $mobile = $data['mobile'];
            $ident = $this->_addident($value, $mobile);
        }
        
        $verificat = VerificationCode::find()->select(['open_invitation_code', 'open_relation_community', 'mention'])->asArray()->one();
        $step = User::find()->select(['step'])->where(['mobile'=>$mobile])->asArray()->one();
        if($step['step'] != 0) {
            $date['step'] = $step['step'];
        }elseif($step['step'] == 0){
            if($verificat['open_invitation_code'] == 1) {
                $date['step'] = '1';
            }else{
                $date['step'] = '0';
            }
        }
//        /**注册露脸**/
//        $loulian_re = LouLianHelper::llRegister($mobile,'', '');
//        LouLianHelper::updateUserInfo($mobile, '', '');
//        $loulian = $user_base_model::updateAll(['loulian_session_id' => $loulian_re['user']['session_id']], ['mobile'=>$mobile]);
//
        $date['open_invitation_code'] = $verificat['open_invitation_code'];
        $date['open_relation_community'] = $verificat['open_relation_community'];
        $date['mention'] = $verificat['mention'];
        $this->result['code'] = 200;
        $this->result['data'] = $date;
        $this->result['message'] = 'OK';
        return $this->response();
    }

    /**
     * 登陆日志
     * @param  string $mobile 手机号
     * @author huangdekui
     * @return json
     */
    public function actionLoginLog(){
        $mobile =RequestHelper::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $user_model = new User();
        $user_fields = 'id,mobile,password,salt,login_count,status,source';
        $user_info = $user_model->getInfo(['mobile'=>$mobile], true, $user_fields);

        $channel = RequestHelper::post('channel','1','intval');
        $login_log_model = new LoginLog();
        $login_log_data['uid']      = $user_info['id'];
        $login_log_data['mobile']   = $mobile;
        $login_log_data['login_ip'] = Common::getIp();
        $login_log_data['channel']  = $channel;
        $login_log_model->insertInfo($login_log_data);
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
}
