<?php
/**
 * 一行的文件介绍
 *
 * PHP Version 5
 * 可写多行的文件相关说明
 *
 * @category  I500M
 * @package   Member
 * @author    xuxiaoyu <xuxiaoyu@i500m.com>
 * @time      16/10/13
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      xuxiaoyu@i500m.com
 */
namespace frontend\modules\v9\controllers;

use common\helpers\RequestHelper;
use common\helpers\Common;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserFriends;
use frontend\models\i500_social\User;
use frontend\models\i500_social\Logincommunity;
use frontend\models\i500_social\IntegralLevel;
use frontend\models\i500_social\Integral;
use frontend\models\i500_social\IntegralRules;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\Activity;
use frontend\models\i500_social\Participate;
use frontend\models\i500_social\ActivityCommunity;
use frontend\models\i500m\Community;
use yii\db\Query;
use frontend\models\i500_social\GroupMember;
use frontend\models\i500_social\Group;
use frontend\models\i500_social\Message;
use frontend\models\i500_social\PostThumbs;
use frontend\models\i500_social\PostComments;
use frontend\models\i500_social\UserVisitors;
use frontend\models\i500_social\TradeUnionUser;
use frontend\models\i500_social\TradeUnion;
class UserController extends BaseController
{
    /**
     * 获取用户信息
     * @return array
     */
    public function actionUserInfo()
    {   
        $user_mobile = RequestHelper::post('user_mobile', '', '');
        $user_id = RequestHelper::post('user_id', '', '');
        if (!empty($user_id)) {
           $user_mobile = User::find()->select(['mobile'])->where(['id'=>$user_id])->scalar();
        }
        
        $userinfo = UserBasicInfo::find()->select(['nickname','mobile','avatar', 'sex', 'personal_sign', 'backimg','birthday','is_recruit','last_community_id','address as user_address','nation'])
                                         ->where(['mobile'=>$user_mobile])
                                         ->with(['usercommlist'=>function ($query){$query->select(['id','mobile','community_id','community_city_id','community_name','address'])->where(['is_deleted'=>0]);}])
                                         ->with(['community'=>function($query){
                                            $query->select(['id','name'])->where(['status'=>1]);
                                         }])
                                         ->asArray()
                                         ->one();
        $userinfo['is_friend'] = "0";
        $userinfo['is_black_list'] = "0";
        $userinfo['is_user_black_list'] = "0";
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
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
        if(count($level)>0)
        {
            for($i=0;$i<count($level);$i++)
            {
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
        //是否为工会会员
        $userinfo['trade_union'] = '';
        $unioninfo = TradeUnionUser::find()->select(['trade_union_id'])->where(["mobile"=>$mobile])->asArray()->one();
        if(!empty($unioninfo)) {
            $tradeinfo = TradeUnion::find()->select(['name'])->where(["id"=>$unioninfo["trade_union_id"]])->asArray()->one();
            if (!empty($tradeinfo)) {
                $userinfo['trade_union'] = $tradeinfo['name'].'会员';
            }
        }
        
        $this->returnJsonMsg(200, $userinfo, 'SUCCESS'); 
    }

    public function actionEditUserInfo()
    {
         $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $data  = array();
        $avatar = RequestHelper::post('avatar', '', '');
        if(!empty($avatar))
        {
            $data['avatar'] = $avatar;
        }
        $backimg = RequestHelper::post('backimg', '', '');
        if(!empty($backimg))
        {
            $data['backimg'] = $backimg;
        }
        $nickname = RequestHelper::post('nickname', '', '');
        if(!empty($nickname))
        {
            $data['nickname'] = $nickname;
            $data['realname'] = $nickname;
        }
        $sex = RequestHelper::post('sex', '', '');
        if(!empty($sex))
        {
            $data['sex'] = $sex;
        }
        $birthday = RequestHelper::post('birthday', '', '');
        if(!empty($birthday))
        {
            $data['birthday'] = $birthday;
        }
        $personal_sign = RequestHelper::post('personal_sign', '', '');
        if(!empty($personal_sign))
        {
            $data['personal_sign'] = Common::userTextEncode($personal_sign);           
        }
//        $res = UserBasicInfo::updateAll(['avatar'=>$avatar,'backimg'=>$backimg,'nickname'=>$nickname,
//            'sex'=>$sex,'birthday'=>$birthday,'personal_sign'=>$personal_sign],['mobile'=>$mobile]);
        $res = UserBasicInfo::updateAll($data,['mobile'=>$mobile]);
        $this->returnJsonMsg('200', [], Common::C('code', '200', 'data', '[]'));
    }
    /**
     * 设置好友备注
     * @return array
     */
    public function actionSetRemark()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $fid = RequestHelper::post('fid', '', '');
        $user_id = RequestHelper::post('user_id','','intval');
        if (empty($fid) && empty($user_id)) {
            $this->returnJsonMsg('2004', [], '参数不合法');
        }

        if(!empty($user_id)){
            $fid = User::find()->select(['mobile'])->where(['id'=>$user_id])->scalar();
            if (empty($fid)) {
                return  $this->returnJsonMsg('604', [], Common::C('code', '604'));
            }
        }

        $remark = RequestHelper::post('remark', '', '');
        if (empty($remark)) {
            $this->returnJsonMsg('404', [], Common::C('code', '404'));
        }else{
            $res = UserFriends::updateAll(['remark'=>$remark],['uid'=>$mobile, 'fid'=>$fid]);
            $this->returnJsonMsg('200', [], Common::C('code', '200', 'data', '[]'));
        }
    }
    /**
     * 更换个人主页背景
     * @return array
     */
    public function actionEditBackimg()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $userinfo = UserBasicInfo::find()->select(['backimg'])->where(['mobile'=>$mobile])->asArray()->one();
        if (empty($userinfo)) {
            $this->returnJsonMsg('404', [], Common::C('code', '404'));
        }else{
            $res = UserBasicInfo::updateAll(['backimg'=>$userinfo['backimg']], ['mobile'=>$mobile]);
            $this->returnJsonMsg('200', [], Common::C('code', '200', 'data', '[]'));
        }
    }

    
    
    /**
     * 获取当前小区邻居
     * @return array
     */
    public function actionUserlistForCommunity()
    {   
        $community_id = RequestHelper::post('community_id', 0, 'intval');
        if (empty($community_id)) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $page = RequestHelper::post('page', 1, 'intval');   
        //查询用户step
        $step = User::find()->select(['step'])->where(['mobile'=>$mobile])->scalar();
        if((int)$step !== 8)
        {
            $this->returnJsonMsg('606',[], '您还未认证');
        }
        
        $user_list = NULL;  

        $level = IntegralLevel::find()->select(['gradation','level_name'])->orderBy('gradation')->asArray()->all();
        
        $field[] = 'i500_user_basic_info.mobile';
        $field[] = 'i500_user_basic_info.nickname';
        $field[] = 'i500_user_basic_info.avatar';
        $field[] = 'i500_user_basic_info.sex';
        $field[] = 'i500_user_basic_info.personal_sign';
        $field[] = 'i500_user_basic_info.age';
        $field[] = 'i500_user_basic_info.create_time';
        $field[] = 'i500_user_basic_info.is_pioneer';
        $field[] = 'i500_user.id';
        //积分
        $field['score'] = (new Query())->select('SUM(score)')->from("i500_integral")->where("i500_integral.mobile=i500_user_basic_info.mobile");
        //用户个人
        if($page>0)
        {
            $user_list = UserBasicInfo::find()->select($field)
                                          ->innerJoinWith(['user' => function ($query) {
                                            $query->where('i500_user.step = 8' );
                                          }])
                                          ->where(['i500_user_basic_info.last_community_id'=>$community_id])
                                          ->andWhere(['<>','i500_user_basic_info.mobile',$mobile])
                                          ->orderBy('i500_user_basic_info.create_time DESC')
                                          ->offset(($page-1)*10)
                                          ->limit(10)
                                          ->asArray()
                                          ->all();
        }
        else
        {
            $user_list = UserBasicInfo::find()->select($field)
                                          ->innerJoinWith(['user' => function ($query) {
                                            $query->where('i500_user.step = 8' );
                                          }])
                                          ->where(['last_community_id'=>$community_id])
                                          ->andWhere(['<>','i500_user_basic_info.mobile',$mobile])
                                          ->orderBy('create_time DESC')
                                          ->asArray()
                                          ->all();
        }
        //var_dump($user_list);exit;
        $data = [];
        foreach($user_list as $k =>$v){
            if ($v['mobile'] != $mobile) {
                $res['mobile'] = $v['mobile'];
                $data[$k]['is_friend'] = 0;
            }
            $user_firends = UserFriends::find()->select(['uid'])->where(['uid'=>$mobile,'fid'=>$res,'status'=>1])->asArray()->all();
            $user_firends1 = UserFriends::find()->select(['uid'])->where(['uid'=>$res,'fid'=>$mobile,'status'=>1])->asArray()->all();
            if (!empty($user_firends) && !empty($user_firends1)) {
                $data[$k]['is_friend'] = 1;
            }
        }
        if (!empty($user_list)) {
            foreach ($user_list as $key =>$value) {
                if ($value['mobile'] != $mobile) {
                    $data[$key]['nickname'] = $value['nickname'];
                    $data[$key]['avatar'] = $value['avatar'];
                    $data[$key]['personal_sign'] = $value['personal_sign'];
                    $data[$key]['mobile'] = $value['mobile'];
                    $data[$key]['sex'] = $value['sex'];
                    $data[$key]['age'] = $value['age'];
                    $data[$key]['is_pioneer'] = $value['is_pioneer'];
                    /*if (empty($value['personal_sign'])) {
                        $1[$key]['personal_sign'] = '太懒了!!';
                    }*/
                    if(count($level)>0)
                    {
                        for($i=0;$i<count($level);$i++)
                        {
                            if($value['score']>$level[$i]['gradation'])
                            {
                                continue;
                            }
                            else
                            {
                                $data[$key]['level_name'] = $level[$i]['level_name'];
                                break;
                            }
                        }
                    }
                    else
                    {
                        $data[$key]['level_name'] = "0";
                    }
                }
            }
        }
        unset($user_list);
        $this->returnJsonMsg('200', $data, Common::C('code', '200'));
    }

    /**
     * 删除好友
     * @return array
     */
    public function actionDelete(){
        $mobile = RequestHelper::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $fid = RequestHelper::post('fid','','');
        if (empty($fid)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($fid)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        
        $userfriends = UserFriends::findOne(['uid'=>$mobile,'fid'=>$fid,'status'=>1]);
        $userfriends1 = UserFriends::findOne(['uid'=>$fid,'fid'=>$mobile,'status'=>1]);
        if (empty($userfriends) && empty($userfriends1)) {
            return $this->returnJsonMsg('422',[],Common::C('code','422'));
        }
        $userfriends -> status = 3;
        $res = $userfriends -> save();
        
        $userfriends1 -> status = 3;
        $res1 = $userfriends1 ->save();
        if ($res && $res1) {
            return $this->returnJsonMsg('200',[],Common::C('code','200'));
        } else {
            return $this->returnJsonMsg('500',[],Common::C('code','500'));
        }
    }

    /**
     * 批量获取用户信息
     * @param string  $mobile    电话
     * @param string $json_mobiles    电话json
     * @return array
     * @author liuyanwei <liuyanwei@i500m.com>
     */
    
    public function actionUserinfoForList()
    {   
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $json_user_mobiles = $_POST['json_user_mobiles'];
        $json_user_mobiles = json_decode($json_user_mobiles,true);
        
        $user_mobiles = $json_user_mobiles['mobile'];
        
        $field[] = "i500_user_basic_info.id";
        $field[] = "i500_user_basic_info.mobile";
        $field[] = "i500_user_basic_info.nickname";
        $field[] = "i500_user_basic_info.avatar";
        $field[] = "i500_user_basic_info.sex";
        $field[] = "i500_user_basic_info.personal_sign";
        $field[] = "i500_user_basic_info.backimg";
        $userinfo = UserBasicInfo::find()->select($field)
//                    ->join('left join','i500_group_member','i500_group_member.mobile=i500_user_basic_info.mobile')
//                    ->with(['group'=>function ($query){$query->select(['i500_group_member.id','i500_group_member.mobile','i500_group.name','i500_group.image'])->join('left join','i500_group','i500_group.group_id=i500_group_member.group_id');}])
                    ->where(['i500_user_basic_info.mobile'=>$user_mobiles])
                    ->asArray()
                    ->all();
        $group_id = $json_user_mobiles['id'];
        if(!empty($group_id))
        {
             $group = Group::find()->select(['id','group_id','name','image'])->where(['group_id'=>$group_id])->asArray()->all();
        }
        $category = array();
        
        if(!empty($group))
        {
            foreach($group as $g)
            {
                $category[$g['group_id']] = $g;
            }
        }
        if(!empty($userinfo))
        {
            foreach ($userinfo as $value) {
                $category[$value['mobile']] = $value;
            }
        }
        $this->returnJsonMsg(200, $category, 'SUCCESS');
    }
}
