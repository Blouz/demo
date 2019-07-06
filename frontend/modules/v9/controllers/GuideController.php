<?php
/**
 *
 *
 * PHP Version 5
 *
 * @category  PHP
 * @filename  GuideController.php
 * @author    xuxiaoyu <xuxiaoyu@i500m.com>
 * @copyright 2015 www.i500m.com
 * @license   http://www.i500m.com/ i500m license
 * @datetime  17/2/16
 * @version   SVN: 1.0
 * @link      http://www.i500m.com/
 */

namespace frontend\modules\v9\controllers;

use frontend\models\i500_social\User;
use frontend\models\i500_social\Guide;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500m\Community;
use common\helpers\LoulianHelper;
use frontend\models\i500_social\Message;
use frontend\models\i500_social\Group;
use frontend\models\i500_social\GroupMember;
use common\helpers\RequestHelper;
use common\helpers\Common;
use common\helpers\CurlHelper;
use yii;


/**
 * Class GuideController
 * @category  PHP
 * @author    xuxiaoyu <xuxiaoyu@i500m.com>
 * @license   http://www.i500m.com/ i500m license
 * @link      http://www.i500m.com/
 */
class GuideController extends BaseController
{
    public function actionIndex()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if(empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if(!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $user = new UserBasicInfo();
        $uid = $user::find()->select(['id','mobile','nickname','avatar','last_community_id'])->where(['mobile'=> $mobile])->asArray()->one();
        if(empty($uid)) {
            $this->returnJsonMsg('732', [], '用户未加入小区');
        }

        $community = Community::find()->select(['name'])->where(['id'=>$uid['last_community_id']])->scalar();
        //注册露脸
        //$loulian_re = LouLianHelper::llRegister ($uid[ 'mobile' ] , $uid[ 'nickname' ] , $uid[ 'avatar' ]);
        //if ( !empty( $loulian_re ) && $loulian_re[ 'error_code' ] == 2000 ) {
        //     $Group = Group::find()->select(['group_id'])->where(['community_id'=>$uid['last_community_id'],'is_deleted'=>2,'owner_group'=>1])->asArray()->one();
        //    if (!empty($Group)) {
        //        $groupMember = GroupMember::find()->select(['id'])->where(['group_id'=>$Group['group_id'],'mobile'=>$mobile])->asArray()->one();
        //        if (empty($groupMember)) {
        //             //如果此小区已存在群组，则加入这个群
        //            $joinGroup =  LouLianHelper::InsertGroup($Group['group_id'],$mobile);
        //            if (!empty($joinGroup) && $joinGroup['error_code'] == 2000) {
        //                $group_member = new GroupMember();
        //                $group_member -> group_id = $Group['group_id'];
        //                $group_member -> mobile = $mobile;
        //                $group_member -> nickname = $uid['nickname'];
        //                $group_member -> role = '2';
        //                $group_member -> is_deleted = '2';
        //                $res = $group_member -> save(false);
        //                if (!$res) {
        //                    return $this->returnJsonMsg('733',[],'插入数据库失败');
        //                }
        //            }
        //            else {
        //                return $this->returnJsonMsg('516',[],'加入群失败');
        //            }
        //        }
        //    } else  {
        //        //创建群组
        //        $loulian_group = LouLianHelper::CreateGroup($mobile,$mobile,$community,'');
        //        if (!empty($loulian_group) && $loulian_group['error_code'] == 2000) {
        //            $group = new Group();
        //            $group -> community_id = $uid['last_community_id'];
        //            $group -> name = $loulian_group['name'];
        //            $group -> group_id = $loulian_group['id'];
        //            $group -> desc = $loulian_group['desc'];
        //            $group -> owner_mobile = $mobile;
        //            $group -> is_deleted = '2';
        //            $group -> owner_group = '1';
        //            $res = $group -> save(false);

       //            $group_member = new GroupMember();
        //            $group_member -> group_id = $loulian_group['id'];
        //            $group_member -> mobile = $mobile;
        //            $group_member -> nickname = $loulian_group['member']['0']['nickname'];
        //            $group_member -> role = '1';
        //            $group_member -> is_deleted = '2';
        //            $re = $group_member -> save(false);
        //            if (!$res || !$re) {
        //                return $this->returnJsonMsg('733',[],'插入数据库失败');
        //            }
        //        }
        //        else {
        //            return $this->returnJsonMsg('515',[],'创建群失败');
        //        }
        //    }
        //} else {
        //    return $this->returnJsonMsg('517',[],'注册露脸失败');
        //}
        $user_id = $uid['id'];
        $user_model = $user::find()->where(['<=', 'id', $user_id])->andWhere(['last_community_id'=> $uid['last_community_id']])->asArray()->count();
        if($user_model == 1) {
            $res = $user::updateAll(['join_in'=>$user_model, 'is_pioneer' => '1'],['mobile'=>$mobile]);
        }else {
            $res = $user::updateAll(['join_in'=>$user_model],['mobile'=>$mobile]);
        }
        $is_pioneer = $user::find()->select(['is_pioneer'])->where(['mobile'=>$mobile])->asArray()->one();
        $step = User::find()->select(['step'])->where(['mobile'=>$mobile])->asArray()->one();

        $data = Guide::find()->select(['id','content'])->asArray()->all();//status 1显示 0不显示
        foreach ($data as $k => $v) {
            $content = $v['content'];
            $array['guide'] = explode('&', htmlspecialchars_decode($content));  
        }
        //当小区有10个人时，给前9个推送
        if($user_model == 10) {
            try {
                $userBasicInfo = UserBasicInfo::find()->select(['mobile'])
                    ->where(['last_community_id' => $uid['last_community_id']])
                    ->orderBy('create_time ASC')
                    ->limit(9)
                    ->column();

                    for ($i = 0; $i < count($userBasicInfo) ; $i++) { 
                        $message = new Message();
                        $message -> mobile = $userBasicInfo[$i];
                        $message -> title = '小区点亮通知';
                        $message -> content = '恭喜你,该小区已被点亮';
                        $message -> type = '1';
                        $message -> status = '1';
                        $message -> message_type = '0';
                        $message -> create_time = date('Y-m-d H:i:s');
                        $re = $message -> save(false);
                        if (!$re) {
                            return $this->returnJsonMsg('733',[],'添加数据库失败');
                        }
                    }

                //群推
                $channel_id = User::find()->select('channel_id')->where(['mobile'=>$userBasicInfo])->column();
                $ios = [];
                $and = [];
                if (!empty($channel_id)) {
                    for ($i = 0; $i < count($channel_id); $i++) {
                        $channel = [];
                        $channel = explode("-", $channel_id[$i]);
                        if ($channel[0] == '1') {
                            $ios[] = $channel[1];
                        }
                        if ($channel[0] == '2') {
                            $and[] = $channel[1];
                        }
                    }
                }
                $iosarr = implode(",", $ios);
                $andarr = implode(",", $and);

                $data['type'] = 11;//点赞  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9系统消息 11 点亮社区
                $data['title'] = '小区已成功点亮';
                $data['description'] ='恭喜你,该小区已被点亮';
                $data['device_type'] = 1;
                $data['channel_id'] = $iosarr;
                $url = \Yii::$app->params['channelHost'] . 'v1/push/many';
                if (!empty($iosarr)) {
                    $ios_result = CurlHelper::post($url, $data);
                }
                if (!empty($andarr)) {
                    $data['channel_id'] = $andarr;
                    $data['device_type'] = 2;
                    $and_result = CurlHelper::post($url, $data);
                }


                $channel_id1 = User::find()->select('xg_channel_id')->where(['mobile'=>$userBasicInfo])->column();
                $ios1 = [];
                $and1 = [];
                if (!empty($channel_id1)) {
                    for ($i = 0; $i < count($channel_id1); $i++) {
                        $channel1 = [];
                        $channel1 = explode("-", $channel_id1[$i]);
                        if ($channel1[0] == '1') {
                            $ios1[] = $channel1[1];
                        }
                        if ($channel1[0] == '2') {
                            $and1[] = $channel1[1];
                        }
                    }
                }
                $iosarr1 = implode(",", $ios1);
                $andarr1 = implode(",", $and1);

                $data1['type'] = 11;//点赞  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9系统消息 11 点亮社区
                $data1['title'] = '小区已成功点亮'; 
                $data1['description'] ='恭喜你,该小区已被点亮';
                $data1['device_type'] = 1;
                $data1['channel_id'] = $iosarr1;
                $url1 = \Yii::$app->params['channelHost']. 'v1/xg-push/many';
                if (!empty($iosarr1)) {
                    $ios_result = CurlHelper::post($url1, $data1);
                }
                if (!empty($andarr1)) {
                    $data['channel_id'] = $andarr1;
                    $data['device_type'] = 2;
                    $and_result = CurlHelper::post($url1, $data1);
                }


            } catch (\Exception $e) {}
        }
        $array['join_in'] = $user_model;
        $array['is_pioneer'] = $is_pioneer['is_pioneer'];
        $array['step'] = $step['step'];
        $info[] = $array;
        if (!empty($array)) {
        	$this->returnJsonMsg('200', $info, Common::C('code','200','data','[]'));
        }else{
        	$this->returnJsonMsg('2000', [], 'error');
        }
    }
}

      