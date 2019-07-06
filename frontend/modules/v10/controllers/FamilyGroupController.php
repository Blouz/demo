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
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserFriends;
use frontend\models\i500_social\UserSms;
use yii\helpers\ArrayHelper;

class FamilyGroupController extends BaseController
{
    /*
     *  新家人
     *  @return array
     */
    public function actionList(){
        $mobile = RequestHelper::post('mobile','','');
        if (empty($mobile)) {
            return $this->returnJsonMsg('604',[],Common::C('code','604'));
        }
        if (!Common::validateMobile($mobile)) {
            return $this->returnJsonMsg('605',[],Common::C('code','605'));
        }

        $read = UserFriends::find()->select(['read'])->where(['fid'=>$mobile,'relation_status'=>2])->column();
        if (in_array(0, $read)) {
            $res = UserFriends::updateAll(['read'=>1],['fid'=>$mobile,'read'=>0,'relation_status'=>2]);
            if (!$res) {
                $this->returnJsonMsg('400', [], Common::C('code', '400'));
            }
        }
        $fileds = [];
        $fileds[] = UserFriends::tableName().'.id';
        $fileds[] = UserFriends::tableName().'.uid';
        $fileds[] = UserFriends::tableName().'.relation';
        $fileds[] = UserBasicInfo::tableName().'.realname';
        $fileds[] = UserFriends::tableName().'.status';

        $userFriends = UserFriends::find()->select($fileds)
                       ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_user_friends.uid')
                       ->where(['relation_status'=>2,'fid'=>$mobile])
                       ->orderBy(UserFriends::tableName().'.create_time DESC')
                       ->asArray()
                       ->all();
        
        //家人未同意但已注册完成的去掉
        if (!empty($userFriends)) {
            foreach ($userFriends as $key => $value)
            {
                //查询step
                $step = User::find()->select(['step'])->where(['mobile'=>$value['uid']])->asArray()->scalar();
                if ($step == 8) {
                    //查询是否家人同意
                    $addFamily = UserFriends::find()->select(['id'])->where(['uid'=>$mobile,'fid'=>$value['uid'],'relation_status'=>2])->asArray()->one();
                    if (empty($addFamily)) {
                        unset($userFriends[$key]);
                    }
                }
            }
            $userFriends = array_values($userFriends);
        }
        return $this->returnJsonMsg('200',$userFriends,Common::C('code','200'));
    }

    /*
     *  家庭组成员同意添加
     *  @return array
     */
    public function actionAdd(){
        $mobile = RequestHelper::post('mobile','','');
        if (empty($mobile)) {
            return $this->returnJsonMsg('604',[],Common::C('code','604'));
        }
        if (!Common::validateMobile($mobile)) {
            return $this->returnJsonMsg('605',[],Common::C('code','605'));
        }

        $fid = RequestHelper::post('fid','','');
        if(empty($fid)){
            return $this->returnJsonMsg('604',[],Common::C('code','604'));
        }

        $user_model = UserFriends::find()->select(['uid','fid','status'])->where(['fid'=>$mobile,'uid'=>$fid,'relation_status'=>2])->one();

        if ($user_model) {
            if ($re = UserFriends::updateAll(['status'=>1],['fid'=>$mobile,'uid'=>$fid,'relation_status'=>2])) {
                $fid_model = UserFriends::find()->select(['uid','fid','status'])->where(['uid'=>$mobile,'fid'=>$fid,'relation_status'=>2])->one();
                if ($fid_model) {
                    $fid_model->status = 1;
                    $fid_model->read = 0;
                    $fid_model->save();
                } else {
                    $new_model = new UserFriends();
                    $new_model->uid = $mobile;
                    $new_model->fid = $fid;
                    $new_model->status = 1;
                    $new_model->read = 0;
                    $new_model->relation_status = 2;
                    $new_model->create_time = date('Y-m-d H:i:s');
                    $new_model->agree_time = date('Y-m-d H:i:s');
                    $res = $new_model->save(false);
                    //查询家人信息
                    $user_info = UserBasicInfo::find()->select(['province_id','city_id','last_community_id','district_id','address'])->where(['mobile'=>$mobile])->asArray()->one();
                    //新注册用户信息补全
                    $res2 = UserBasicInfo::updateAll([
                                'province_id'=>$user_info['province_id'],
                                'city_id'=>$user_info['city_id'],
                                'last_community_id'=>$user_info['last_community_id'],
                                'district_id'=>$user_info['district_id'],
                                'address'=>$user_info['address'],
                                'create_time'=>date('Y-m-d H:i:s',time())
                                ],['mobile'=>$fid]);

                    if($res && $res2){
                        $sms = new UserSms();
                        $sms -> mobile = $fid;
                        $sms -> content = Common::getSmsTemplate(6);
                        $sms -> send_time = date('Y-m-d H:i:s');
                        $sms -> create_time = date('Y-m-d H:i:s');
                        $res3 =  $sms -> save(false);
                        if (!$res3) {
                            return $this->returnJsonMsg('400',[],Common::C('code', '400'));
                        } else {
                            //修改step
                            User::updateAll(['step'=>6],['mobile'=>$fid,'is_deleted'=>2,'status'=>2]);
                            $sms_content = Common::getSmsTemplate(6);
                            /**发送短信通道**/
                            $rs = $this->sendSmsChannel($fid, $sms_content);
                            if (!$rs) {
                                 return $this->returnJsonMsg('611', [], Common::C('code', '611'));
                            }
                        }

                        //推送
                        try {
                            $channel_id = User::find()->select('xg_channel_id')->where(['mobile'=>$fid])->scalar();
                            if(!empty($channel_id))
                            {
                                $channel = explode('-', $channel_id);
                                $data['device_type'] = ArrayHelper::getValue($channel, 0);
                                $data['channel_id'] = ArrayHelper::getValue($channel, 1);
                                $data['type'] = 12;//点赞  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9系统消息 10 11点亮社区  12 家庭组推送
                                $data['title'] = '您的家人已同意！';
                                $data['description'] ='恭喜您，您的家人已经同意您加入咪邻大家庭啦！';
                                $channel_url = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
                                $re = CurlHelper::post($channel_url, $data);
                            }
                        } catch( \Exception $e) {}
                    }
                }
            }
        } else {
            return $this->returnJsonMsg('404',[],'数据不存在');
        }
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }

    /*
     *  家庭组成员列表
     *  @return json
     */
    public function actionFamilyList()
    {
        $mobile = RequestHelper::post('mobile','','');
        if (empty($mobile)) {
            return $this->returnJsonMsg('604',[],Common::C('code','604'));
        }
        if (!Common::validateMobile($mobile)) {
            return $this->returnJsonMsg('605',[],Common::C('code','605'));
        }

        //登陆的人，都添加那些好友
        $friendlist = UserFriends::find()->select(['fid'])->where(['uid'=>$mobile,'status'=>1,'relation_status'=>2])->column();

        $field = [];
        $field[] = 'i500_user_basic_info.id';
        $field[] = 'i500_user_basic_info.mobile';
        $field[] = 'i500_user_basic_info.realname';
        $field[] = 'i500_user_basic_info.avatar';

        $familyList = UserBasicInfo::find()->select($field)
                      ->join('LEFT JOIN','i500_user_friends','i500_user_basic_info.mobile=i500_user_friends.fid')
                      ->where(['mobile'=>$friendlist,'relation_status'=>2])
                      ->orderBy(['id'=>'ASC'])
                      ->asArray()
                      ->all();
		if (!empty($familyList)) {
			foreach($familyList as $key =>$value){
				if ($value['avatar']) {
					if (!strstr($value['avatar'], 'http')) {
						$familyList[$key]['avatar'] = Common::C('imgHost').$value['avatar'];
					}
				}
			}
        }
        return $this->returnJsonMsg('200',$familyList,Common::C('code','200'));
    }
}