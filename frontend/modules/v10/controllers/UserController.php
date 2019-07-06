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
namespace frontend\modules\v10\controllers;

use common\helpers\RequestHelper;
use common\helpers\Common;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserFriends;
use frontend\models\i500_social\User;
use frontend\models\i500_social\IntegralLevel;
use frontend\models\i500_social\Integral;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\Label;
use frontend\models\i500_social\UserLabel;
use frontend\models\i500_social\TagClass;
use frontend\models\i500_social\Activity;
use frontend\models\i500_social\Participate;
use frontend\models\i500_social\ActivityCommunity;
use frontend\models\i500m\Community;
use yii\db\Query;
use common\helpers\TxyunHelper;
use common\vendor\tls_sig\php\sig;
class UserController extends BaseController
{
    /**
     * 获取用户信息
     * @return array
     */
    public function actionUserInfo()
    {   
        $user_mobile = RequestHelper::post('user_mobile', '', '');
        if (empty($user_mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        
        $userinfo = UserBasicInfo::find()->select(['nickname','mobile','avatar', 'sex', 'personal_sign', 'birthday','is_recruit','last_community_id','address as user_address'])
                                         ->where(['mobile'=>$user_mobile])
                                         ->with(['community'=>function($query){
                                            $query->select(['id','name'])->where(['status'=>1]);
                                         }])
                                         ->asArray()
                                         ->one();
        $userinfo['is_friend'] = "0";
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
        }else{
            $userinfo['is_friend'] = "2";
        }
        $score = Integral::find()->select('SUM(score)')->where(['mobile'=>$user_mobile])->scalar();
        if(empty($score)) {
            $score = '0';
        }
        $level = IntegralLevel::find()->select(['gradation','level_name'])->orderBy('gradation')->asArray()->all();
        $level_name = "";
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
        //计算活动数量
        if (!empty($user_mobile)) {
            $user_comm_id =  UserBasicInfo::find()->select(['last_community_id'])->where(['mobile'=>$user_mobile])->scalar();
            if(empty($user_comm_id)) {
                return $this->returnJsonMsg('500',[],'网络繁忙');
            }
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
        $userinfo['activity'] = $count;
        //计算邻居说数量
        $fields = ['id'];
        $count1 = Post::find()->select($fields)->where(['mobile'=>$user_mobile,'status'=>2,'is_deleted'=>2])->count();
        $userinfo['post'] = $count1;
        
        $label = new Label();
        $userlabel = new UserLabel();
        $tagclass = new TagClass();

        $user = $userlabel::find()->select(['label_id'])->where(['mobile'=>$user_mobile])->column();       
        $label_model = $tagclass::find()->select(['id', 'name'])->with(['label'=>function($query) use($user){
                                                    $query->select(['id','label','classify_id'])
                                                    ->where(['is_reveal'=>1,'id'=>$user]);
                                 } ])->asArray()->all();
                  
        $userinfo['label'] = $label_model;
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
        $users  = array();
        $avatar = RequestHelper::post('avatar', '', '');
        if(!empty($avatar))
        {
            $users['avatar'] = $avatar;
        }
        $backimg = RequestHelper::post('backimg', '', '');
        if(!empty($backimg))
        {
            $users['backimg'] = $backimg;
        }
        $nickname = RequestHelper::post('nickname', '', '');
        if(!empty($nickname))
        {
            $users['nickname'] = $nickname;
            $users['realname'] = $nickname;
        }
        $sex = RequestHelper::post('sex', '', '');
        if(!empty($sex))
        {
            $users['sex'] = $sex;
        }
        $birthday = RequestHelper::post('birthday', '', '');
        if(!empty($birthday))
        {
            $users['birthday'] = $birthday;
        }
        $personal_sign = RequestHelper::post('personal_sign', '', '');
        if(!empty($personal_sign))
        {
            $users['personal_sign'] = $personal_sign;
        }
        $users['update_time'] = date("Y-m-d H:i:s");

        $user_id = User::find()->select(['id'])->where(['mobile'=>$mobile])->scalar();
        $res = TxyunHelper::Edit_userinfo($user_id,$users);
        $result = json_decode($res,true);

        $res = UserBasicInfo::updateAll($users,['mobile'=>$mobile]);
        
        $this->returnJsonMsg('200',[], Common::C('code', '200', 'data', '[]'));
    }


    /**
     * 
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
        $field[] = 'i500_user_basic_info.lng';
        $field[] = 'i500_user_basic_info.lat';
        $field[] = 'i500_user.id';
        //积分
        $field['score'] = (new Query())->select('SUM(score)')->from("i500_integral")->where("mobile=i500_user_basic_info.mobile");
        $user_list = UserBasicInfo::find()->select($field)
            ->with(['label'=>function($query){
                $query->select(['mobile','label_id'])->where(['status'=>2])->groupBy('label_id,mobile')->with(['labelName'=>function($data){
                    $data->select(['id','label','classify_id'])->where(['is_reveal'=>1])->with(['tagClass'=>function($data1){
                        $data1->select(['id','name'])->where(['is_reveal'=>1]);
                    }]);;
                }]);
            }])
            ->innerJoinWith(['user' => function ($query) {
                $query->where('i500_user.step = 8' );
              }])
            ->where(['last_community_id'=>$community_id])
//            ->andWhere(['<>','mobile',$mobile])
            ->orderBy('create_time DESC')
            ->asArray()
            ->all();
        $data = [];
        if (!empty($user_list)) {
            foreach ($user_list as $key =>$value) {
                //判断是否是朋友
                if ($key['mobile'] != $mobile) {
                    $res['mobile'] = $value['mobile'];
                    $data[$key]['is_friend'] = 0;
                }
                $user_firends = UserFriends::find()->select(['uid'])->where(['uid'=>$mobile,'fid'=>$res,'status'=>1])->asArray()->all();
                $user_firends1 = UserFriends::find()->select(['uid'])->where(['uid'=>$res,'fid'=>$mobile,'status'=>1])->asArray()->all();
                if (!empty($user_firends) && !empty($user_firends1)) {
                    $data[$key]['is_friend'] = 1;
                }

                //是不是当前登陆的用户
                $data[$key]['is_login'] = 0;
                if($value['mobile'] == $mobile){
                    $data[$key]['is_login'] = 1;
                }

                $data[$key]['nickname'] = $value['nickname'];
                $data[$key]['avatar'] = $value['avatar'];
                $data[$key]['personal_sign'] = $value['personal_sign'];
                $data[$key]['mobile'] = $value['mobile'];
                $data[$key]['sex'] = $value['sex'];
                $data[$key]['age'] = $value['age'];
                $data[$key]['lng'] = $value['lng'];
                $data[$key]['lat'] = $value['lat'];
                
                $data[$key]['label'] = [];
                //标签
                foreach($value['label'] as $k =>$v){
                    //标签名为空
                    if(empty($v['labelName']['label'])) {
                        continue;
                    }
                    //分类标签为空
                    if(empty($v['labelName']['tagClass'])) {
                        continue;
                    }
                    $data[$key]['label'][] = [
                        'label' => $v['labelName']['label'],
                        'mobile' => $v['mobile'],
                    ];
                }

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
                //过滤数据
                if($value['lng'] == '0.000000'){
                    unset($data[$key]);
                }
                if($value['lat'] == '0.000000'){
                    unset($data[$key]);
                }
                $data = array_values($data);
            }
        }
        unset($user_list);
        $this->returnJsonMsg('200', $data, Common::C('code', '200'));
    }


    /**
     * 根据时间获取当前小区邻居
     * @return array
     */
    public function actionNeighborForCommunity()
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
            $this->returnJsonMsg('606',"", '您还未认证');
        }
        $time = RequestHelper::post('time', '', '');
        if($time){
             $gettime = date('Y-m-d H:i:s', $time);
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
        $field[] = 'i500_user_basic_info.update_time';
        //积分
        $field['score'] = (new Query())->select('SUM(score)')->from("i500_integral")->where("mobile=i500_user_basic_info.mobile");
        //用户个人
        if(!empty($time))
        {
            $user_list = UserBasicInfo::find()->select($field)
                                          ->where(['last_community_id'=>$community_id])
                                          ->andWhere(['<>','mobile',$mobile])
                                          ->andWhere(['>','update_time',$gettime])
                                          ->orderBy('create_time DESC')
                                          ->asArray()
                                          ->all();
        }
        else
        {
            $user_list = UserBasicInfo::find()->select($field)
                                          ->where(['last_community_id'=>$community_id])
                                          ->andWhere(['<>','mobile',$mobile])
                                          ->orderBy('create_time DESC')
                                          ->asArray()
                                          ->all();
        }
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
                    $data[$key]['lng'] = $value['lng'];
                    $data[$key]['lat'] = $value['lat'];

                    if($value['lng'] ==null){
                        $data[$key]['lng'] = '';
                    }
                    if($value['lat'] ==null){
                        $data[$key]['lat'] = '';
                    }

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
        $result['user_list'] = $data;
        $result['current_time'] = time();
    
        $this->returnJsonMsg('200', $result, Common::C('code', '200'));
    }
}
