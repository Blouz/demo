<?php
/**
 * 一行的文件介绍
 * @author    wangyanyan
 * @time      2017/07/04
 * @copyright 爱伍佰
 */
namespace frontend\modules\v11\controllers;

use common\helpers\RequestHelper;
use common\helpers\Common;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserFriends;
use frontend\models\i500_social\User;
use frontend\models\i500_social\IntegralLevel;
use frontend\models\i500_social\Integral;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\Activity;
use frontend\models\i500_social\Participate;
use frontend\models\i500_social\ActivityCommunity;
use frontend\models\i500_social\Message;
use frontend\models\i500_social\PostThumbs;
use frontend\models\i500_social\PostComments;
use frontend\models\i500_social\UserVisitors;
use frontend\models\i500_social\TradeUnionUser;
use frontend\models\i500_social\TradeUnion;

class UserController extends BaseController {
    /**
     * 获取用户信息(生活圈+星座)
     * @return array
     */
    public function actionUserInfo() {
        $mobile = RequestHelper::post('mobile', '', '');
        $user_mobile = RequestHelper::post('user_mobile', '', '');
        $user_id = RequestHelper::post('user_id', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        if (!empty($user_id)) {
           $user_mobile = User::find()->select(['mobile'])->where(['id'=>$user_id])->scalar();
        }
        if (empty($user_mobile)) {
            $user_mobile = $mobile;
        }
        if (!Common::validateMobile($user_mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $userinfo = UserBasicInfo::find()->select(['nickname','mobile','avatar', 'sex', 'personal_sign', 'backimg','birthday','is_recruit','last_community_id','address as user_address','nation'])
                    ->where(['mobile'=>$user_mobile])
                    ->with(['usercommlist'=>function ($query){$query->select(['id','mobile','community_id','community_city_id','community_name','address'])->where(['is_deleted'=>0]);}])
                    ->with(['community'=>function($query){
                      $query->select(['id','name'])->where(['status'=>1]);
                    }])
                   ->asArray()
                   ->one();
        if (empty($userinfo)) {
            $this->returnJsonMsg('602', [], Common::C('code', '602'));
        }
        $userinfo['is_friend'] = "0";
        $userinfo['is_black_list'] = "0";
        $userinfo['is_user_black_list'] = "0";
        if (!empty($mobile) && ($user_mobile != $mobile)) {
            $friend = UserFriends::find()->select(['id', 'remark'])->where(['uid'=>$mobile, 'fid'=>$user_mobile, 'status'=>1])->asArray()->one();
            if (!empty($friend)) {
                $userinfo['is_friend'] = "1";
                $userinfo['remark'] = $friend['remark']; 
                $userinfo['fid'] = $user_mobile;
            }
            $friend_status = UserFriends::find()->select(['id', 'remark'])->where(['uid'=>$mobile, 'fid'=>$user_mobile, 'status'=>4])->asArray()->one();
            if (!empty($friend_status)) {
                $userinfo['is_black_list'] = "1";
            }
            $user_friend_status = UserFriends::find()->select(['id', 'remark'])->where(['uid'=>$user_mobile, 'fid'=>$mobile, 'status'=>4])->asArray()->one();
            if (!empty($user_friend_status)) {
                $userinfo['is_user_black_list'] = "1";
            }
        }else{
            $userinfo['is_friend'] = "2";
        }
        $score = Integral::find()->select('SUM(score)')->where(['mobile'=>$user_mobile])->scalar();
        if(empty($score)) {
            $score = '0';
        }
        $level = IntegralLevel::find()->select(['gradation','level_name'])->orderBy('gradation')->asArray()->all();
        $level_name = "";
        $level_gradation = "";
        if(count($level)>0) {
            for($i=0;$i<count($level);$i++) {
                if($score>$level[$i]['gradation']){
                    continue;
                }else{
                    $level_name = $level[$i]['level_name'];
                    $level_gradation = $level[$i]['gradation'];
                    break;
                }
            }
        }else{
            $level_name = '0';
            $level_gradation = '0';
        }
        $userinfo['level_name'] = $level_name;
        $userinfo['score'] = $score;
        $userinfo['level_gradation'] = $level_gradation;
        if(empty($userinfo['level_gradation'])) {
            $level = IntegralLevel::find()->select(['gradation','level_name'])->orderBy('gradation Desc')->asArray()->one();
            $userinfo['level_name'] = $level['level_name'];
            $userinfo['level_gradation'] = $level['gradation'];
            $userinfo['score'] = $level['gradation'];
        }
        $community_id = [];
        //计算活动数量
        if (!empty($user_mobile)) {
            $user_comm_id =  UserBasicInfo::find()->select(['last_community_id'])->where(['mobile'=>$user_mobile])->scalar();
            $community_id = $user_comm_id;
        }
        $participate = Participate::find()->select(['activity_id'])->where(['mobile'=>$user_mobile])->column();

        $field[] = "i500_activity.id";
        $condition1[ActivityCommunity::tableName().'.community_id'] = $community_id;
        $condition1[Activity::tableName().'.status'] = 1;
        $condition1[Activity::tableName().'.mobile'] = $user_mobile;
        $condition2[Activity::tableName().'.range'] = 0;
        $condition2[Activity::tableName().'.status'] = 1;
        $condition2[Activity::tableName().'.mobile'] = $user_mobile;
        $condition3[ActivityCommunity::tableName().'.community_id'] = $community_id;
        $condition3[Activity::tableName().'.status'] = 1;
        $condition3[Activity::tableName().'.id'] = $participate;
        $condition4[Activity::tableName().'.range'] = 0;
        $condition4[Activity::tableName().'.status'] = 1;
        $condition4[Activity::tableName().'.id'] = $participate;
        $count = Activity::find()
                 ->select($field)
                 ->join('LEFT JOIN','i500_participate','i500_participate.activity_id=i500_activity.id')
                 ->join('LEFT JOIN','i500_activity_community','i500_activity_community.activity_id=i500_activity.id')
                 ->where($condition1)
                 ->orWhere($condition2)
                 ->orWhere($condition3)
                 ->orWhere($condition4)
                 ->groupby('i500_activity.id')
                 ->count();
        $mycondition1[ActivityCommunity::tableName().'.community_id'] = $community_id;
        $mycondition1[Activity::tableName().'.status'] = 1;
        $mycondition1[Activity::tableName().'.mobile'] = $user_mobile;
        $mycondition1[Activity::tableName().'.audit_status'] = 2;
        $mycondition2[Activity::tableName().'.range'] = 0;
        $mycondition2[Activity::tableName().'.status'] = 1;
        $mycondition2[Activity::tableName().'.mobile'] = $user_mobile;
        $mycondition2[Activity::tableName().'.audit_status'] = 2;
        $mycondition3[ActivityCommunity::tableName().'.community_id'] = $community_id;
        $mycondition3[Activity::tableName().'.status'] = 1;
        $mycondition3[Activity::tableName().'.id'] = $participate;
        $mycondition3[Activity::tableName().'.audit_status'] = 2;
        $mycondition4[Activity::tableName().'.range'] = 0;
        $mycondition4[Activity::tableName().'.status'] = 1;
        $mycondition4[Activity::tableName().'.id'] = $participate;
        $mycondition4[Activity::tableName().'.audit_status'] = 2;
        $mycount = Activity::find()
                   ->select($field)
                   ->join('LEFT JOIN','i500_participate','i500_participate.activity_id=i500_activity.id')
                   ->join('LEFT JOIN','i500_activity_community','i500_activity_community.activity_id=i500_activity.id')
                   ->where($mycondition1)
                   ->orWhere($mycondition2)
                   ->orWhere($mycondition3)
                   ->orWhere($mycondition4)
                   ->groupby('i500_activity.id')
                   ->count();
        $userinfo['activity'] = $count;
        $userinfo['my_activity'] = $mycount;
        //计算邻居说数量
        $fields = ['id'];
        $count1 = Post::find()->select($fields)->where(['mobile'=>$user_mobile,'status'=>2,'is_deleted'=>2])->count();
        $userinfo['post'] = $count1;
        $userinfo['user_id'] = User::find()->select(['id'])->where(['mobile'=>$user_mobile])->scalar();

        $message = Message::find()->select(['id'])->where(['mobile'=>$mobile,'read'=>0])->count();
        $friend = UserFriends::find()->select(['id'])->where(['fid'=>$mobile,'read'=>0,'relation_status'=>1])->count();
        $family = UserFriends::find()->select(['id'])->where(['fid'=>$user_mobile,'read'=>0,'relation_status'=>2])->count();
        $comments = PostComments::find()->select(['id'])->where(['author_mobile'=>$user_mobile,'read'=>0])->count();
        $thumbs = PostThumbs::find()->select(['id'])->where(['author_mobile'=>$user_mobile,'read'=>0])->count();
        $visitors = UserVisitors::find()->select(['id'])->where(['mobile'=>$user_mobile,'read'=>0])->count();
        $interaction = (int)$comments+(int)$thumbs;
        $userinfo['message'] = $message;
        $userinfo['friend'] = $friend;
        $userinfo['family'] = $family; 
        $userinfo['interaction'] = (string)$interaction;
        $userinfo['visitors'] = $visitors;
        $userinfo['personal_sign'] = Common::userTextDecode($userinfo['personal_sign']);
        
        $userinfo['xinzuo'] = Common::getZodiacSign($userinfo['birthday']);
        //邻居圈相册
        $query = Post::find()->select(['i500_post_photo.id','i500_post_photo.photo'])
                 ->join('RIGHT JOIN','i500_post_photo','i500_post_photo.post_id=i500_post.id')
                 ->where(['i500_post.mobile'=>$user_mobile,'i500_post.status'=>2,'i500_post.is_deleted'=>2]);
        $post_list = $query->orderBy('i500_post_photo.id DESC')->limit(5)->asArray()->all();
        $userinfo['post_photo'] = $post_list;
        //是否为工会会员
        $userinfo['trade_union'] = '';
        $unioninfo = TradeUnionUser::find()->select(['trade_union_id'])->where(["mobile"=>$mobile])->asArray()->one();
        if(!empty($unioninfo)) {
            $tradeinfo = TradeUnion::find()->select(['name'])->where(["id"=>$unioninfo["trade_union_id"]])->asArray()->one();
            if (!empty($tradeinfo)) {
                $userinfo['trade_union'] = $tradeinfo['name'].'会员';
            }
        }
        
        $this->returnJsonMsg(200, [$userinfo], Common::C('code', '200')); 
    }

}
