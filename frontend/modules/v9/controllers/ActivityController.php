<?php
/**
 * 活动
 *
 * PHP Version 9
 *
 * @category  Social
 * @package   Service
 * @author    wangleilei <wangleilei@i500m.com>
 * @time      2016/12/01
 * @copyright 2017 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wangleilei@i500m.com
 */
namespace frontend\modules\v9\controllers;

use Yii;

use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\ServiceCategory;
use yii\helpers\ArrayHelper;
use frontend\models\i500_social\Activity;
use frontend\models\i500_social\ActivityCommunity;
use frontend\models\i500_social\Participate;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\Logincommunity;
use frontend\models\i500_social\ActivityPhoto;
use frontend\models\i500_social\ActivityType;
use frontend\models\i500_social\Message;
use yii\db\Query;

class ActivityController extends BaseController
{
    public function actionIndex()
    {   
        // $community_id = RequestHelper::post('community_id', '', '');
        $type = RequestHelper::post('type', '', '');//0所有活动 1自己已参加的活动
        $mobile = RequestHelper::post('usermobile', '', '');
        //获取小区id
        $community_id = 0;
        if (!empty($mobile)) {

            $user_comm_id =  UserBasicInfo::find()->select(['last_community_id'])->where(['mobile'=>$mobile])->scalar();
            if(empty($user_comm_id)) {
                return $this->returnJsonMsg('500',[],'网络繁忙');
            }
            $community_id = $user_comm_id;
        }
        $activity_id = RequestHelper::post('activity_id', '', '');
        $page_start = RequestHelper::post('page', '', '');        //列表起始位置
        $page = (int)$page_start;
        if($page==""||$page==0)
    	{
            $page=0;
    	}
        else
        {
            $page = ($page - 1)*10;
        }
        $field[] = "i500_activity.id";
        $field[] = "i500_activity.title";
        //$field[] = "i500_activity.image";
        $field[] = "i500_activity.active_time";
        $field[] = "i500_activity.end_time";
        $field[] = "i500_activity.address";
        $field[] = "i500_activity.introduction";
        $field[] = "i500_activity.fee";
        $field[] = "i500_activity.sign_limit";
        $field[] = "i500_activity.is_on_line";
        $field[] = "i500_activity.range";//范围
        $field[] = "i500_activity.active_type_id";//类别id
        $field[] = "i500_activity.lat";//纬度
        $field[] = "i500_activity.lng";//经度
        $field[] = "i500_activity.mobile";//发布活动者的手机号
        $field[] = "i500_activity.audit_status";//审核状态  审核状态 0=未审核1=审核中2=审核成功3=审核失败
        $field[] = "i500_activity_type.name";//类别名称
        $field[] = "i500_activity_community.community_id";//小区id
        $field[] = "i500_activity.is_fee";
        $field[] = "i500_activity.per_money";
        //$field[] = "i500_participate.attend";
        $field[] = "i500_user_basic_info.avatar";
        $field[] = "i500_user_basic_info.nickname";
        $field['participate'] = (new Query())->select('count(id)')->from("i500_participate")->where("activity_id=i500_activity.id");
        $field['application'] = (new Query())->select('count(id)')->from("i500_participate")->where("activity_id=i500_activity.id")->andWhere(['mobile'=>$mobile]);
        $field[] = "i500_activity.sort";
        if((int)$community_id>0)
        {
            $condition[ActivityCommunity::tableName().'.community_id'] = $community_id;
        }
        $condition[Activity::tableName().'.status'] = 1;
        $condition[Activity::tableName().'.audit_status'] = '2';
       
        if(!empty($activity_id))
        {
           $condition[Activity::tableName().'.id'] = $activity_id;
        }
        $cond = array();
        //if($type==0){
        $cond[Activity::tableName().'.range'] = 0;
        $cond[Activity::tableName().'.status'] = 1;
        $cond[Activity::tableName().'.audit_status'] = '2';
        //}
        $current_time = date('Y-m-d H:i:s');
        $result = Activity::find()
                     ->select($field)
                     ->join('LEFT JOIN','i500_activity_type','i500_activity_type.id=i500_activity.active_type_id')
                     //->join('LEFT JOIN','i500_participate','i500_participate.activity_id=i500_activity.id')
                     ->join('LEFT JOIN','i500_activity_community','i500_activity_community.activity_id=i500_activity.id')
                     ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_activity.mobile')
                     ->with(['photo'=>function ($query){$query->select(['activity_id','image']);}])
                     //->with(['community'=>function ($query){$query->select(['id','activity_id','community_id','city_id']);}])
                     ->where($condition)
                     ->andWhere(['>','i500_activity.end_time',$current_time])
                     ->orWhere($cond)
                     ->andWhere(['>','i500_activity.end_time',$current_time])
                     ->orderBy('i500_activity.sort desc,i500_activity.id desc')
                     ->offset($page)
                     ->limit(10)
                     //->createCommand()->getRawSql();
                     ->asArray()
                     ->all();
        
        
        $this->returnJsonMsg('200',$result, Common::C('code','200','data','[]'));
    }
    //活动置顶
    public function actionSetTop()
    {
        $activity_id = RequestHelper::post('activity_id', '', '');
        $mobile = RequestHelper::post('mobile', '', '');
        $res = "";
        if (empty($mobile)) 
        {
            return $this->returnJsonMsg('99',[],'手机号不能为空');
        }

        $step = User::find()->select(['step'])->where(['mobile'=>$mobile])->scalar();
        if ((int)$step != 8) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }

        $is_pioneer = UserBasicInfo::find()->select(['is_pioneer'])->where(['mobile'=>$mobile])->scalar();
        if((int)$is_pioneer == 1)
        {
            $reset = Activity::updateAll(['sort'=>0]);

            $res = Activity::updateAll(['sort'=>1],['id'=>$activity_id]);
            
        } else {
            $this->returnJsonMsg('741',[], Common::C('code','741'));
        }
        $this->returnJsonMsg('200',$res, Common::C('code','200','data','[]'));
    }
    //活动报名
    public function actionApply()
    {
        $activity_id = RequestHelper::post('activity_id', '', '');
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) 
        {
            return $this->returnJsonMsg('99',[],'手机号不能为空');
        }

        $step = User::find()->select(['step'])->where(['mobile'=>$mobile])->scalar();
        $step = 8;
        if ((int)$step != 8) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }

        $act_arr = Activity::find()->select(['mobile','end_time'])->where(['id'=>$activity_id])->asArray()->one();
        //活动不存在
        if (empty($act_arr)) {
            $this->returnJsonMsg('7005',[], '活动不存在');
        }
        //自己的活动不可参与
        if ($act_arr['mobile'] == $mobile) {
            $this->returnJsonMsg('7005',[],'自己的活动不需要报名');
        }
        //活动已过期
        if ($act_arr['end_time'] < date('Y-m-d H:i:s')) {
            $this->returnJsonMsg('7005',[], '活动已过期');
        }
        $uid = User::find()->select('id')->where(['mobile'=>$mobile])->scalar();
        $id = Participate::find()->select(['id'])->where(['activity_id'=>$activity_id,'mobile'=>$mobile])->scalar();
        $apply = Participate::find()->select('count(id)')->where(['activity_id'=>$activity_id])->scalar();
        $limit = Activity::find()->select(['sign_limit'])->where(['id'=>$activity_id])->scalar();
        $places = $limit-$apply;
        
        if($id>0)
        {
            $this->returnJsonMsg('7001',[], '每人只限报名一次');
        }
        else
        {
            if($places>0||(int)$limit==0)
            {
                $party = new Participate();

                $party->activity_id = $activity_id;
                $party->uid = $uid;
                $party->mobile = $mobile;
                $result = $party->save(false);
                if($result)
                {
                    $res = "1";
                }
                else 
                {
                    $res = "0";
                }
            }
            else
            {
                $this->returnJsonMsg('7002',[], '名额已满');
            }
            if($res=="1")
            {
                $value = "act_j";
                $this->_addident($value,$mobile); 
            }
            
        }
        $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
    }
    //活动签到
    public function actionSignup()
    {
       $mobile = RequestHelper::post('mobile', '', '');
       if(empty($mobile))
       {
           $this->returnJsonMsg('7003',[], '用户手机号不能为空');
       }

        $step = User::find()->select(['step'])->where(['mobile'=>$mobile])->scalar();
        if ((int)$step != 8) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }

       $activity_id = RequestHelper::post('activity_id', '', '');
       if(empty($activity_id))
       {
            $this->returnJsonMsg('7004',[], '活动id不能为空');
       }
       $sign_time = date('Y-m-d H:i:s', time());
       $res = Participate::updateAll(array('attend'=>'1','sign_time'=>$sign_time),'mobile=:mobile AND activity_id=:id',array(':mobile'=>$mobile,':id'=>$activity_id));
       $result="";
       if($res==1)
       {
            $result="1";
       }
       else
       {
            $result="0";
       }
       $this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));
    }
    
    //活动参与者
    public function actionPartner()
    {
       $activity_id = RequestHelper::post('activity_id', '', '');
       $page = RequestHelper::post('page', 1, 'intval');
       $res = UserBasicInfo::find()->select(['i500_user_basic_info.id','i500_user_basic_info.avatar','i500_user_basic_info.nickname','i500_user_basic_info.personal_sign','i500_user_basic_info.mobile'])
                            ->join('LEFT JOIN','i500_participate','i500_participate.mobile=i500_user_basic_info.mobile')
                            ->join('LEFT JOIN','i500_user','i500_user.mobile=i500_user_basic_info.mobile')
                            ->where(['i500_participate.activity_id'=>$activity_id])
                            ->offset(($page-1)*10)
                            ->limit(10)
                            ->asArray()
                            ->all();
       $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
       
    }



     /**
     * 发布活动
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */
    public function actionAdd()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $step = User::find()->select(['step'])->where(['mobile'=>$mobile])->scalar();
        if ((int)$step != 8) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }
        // $community_id = RequestHelper::post('community_id', 0, 'intval');
        // if (empty($community_id)) {
        //     $this->returnJsonMsg('642', [], Common::C('code', '642'));
        // }

        // $community_city_id = RequestHelper::post('community_city_id', 0, 'intval');
        // if (empty($community_city_id)) {
        //     $this->returnJsonMsg('645', [], Common::C('code', '645'));
        // }

        $user = UserBasicInfo::find()->select(['city_id','last_community_id'])->where(['mobile'=>$mobile])->asArray()->one();
        $community_id = $user['last_community_id'];
        $community_city_id = $user['city_id'];
        if ($community_city_id == "" || $community_id == "") {
            $this->returnJsonMsg('732', [], '用户未加入小区');
        }

        $title = RequestHelper::post('title', '', '');
        if (empty($title)) {
            $this->returnJsonMsg('702', [],'活动标题不能为空');
        }

        //活动图片
        $image = RequestHelper::post('image', '', '');

        $active_time = RequestHelper::post('active_time', '', '');
        if (empty($active_time)) {
            $this->returnJsonMsg('723', [], '活动开始时间不能为空');
        } 

        $end_time = RequestHelper::post('end_time', '', '');
        if (empty($end_time)) {
            $this->returnJsonMsg('724', [], '活动结束时间不能为空');
        }
        if (strtotime($end_time) <= strtotime($active_time)) {
            $this->returnJsonMsg('731', [], '活动结束时间不能小于开始时间');
        }

        $is_on_line = RequestHelper::post('is_on_line', '', '');
        if ($is_on_line == "") {
            $this->returnJsonMsg('726', [],'活动地点不能为空');
        }

        $address = RequestHelper::post('address', '', '');
        if ($is_on_line == 1 && empty($address)) {
            $this->returnJsonMsg('727', [],'活动地点不能为空');
        }

        $lat =  RequestHelper::post('lat', '', '');
        if (!empty($address) && empty($lat)) {
            $this->returnJsonMsg('728', [],'活动地点纬度不能为空');
        } 

        $lng =  RequestHelper::post('lng', '', '');
        if (!empty($address) && empty($lng)) {
            $this->returnJsonMsg('729', [],'活动地点经度不能为空');
        }

        //活动介绍
        $introduction = RequestHelper::post('introduction', '', '');

        //活动人数上限
        $sign_limit = RequestHelper::post('sign_limit', '0', '');
        if($sign_limit == ""){
            $sign_limit = 0;
        }

        //活动类型
        $active_type_id = RequestHelper::post('active_type_id', '', '');

        if (empty($active_type_id)) {
            $this->returnJsonMsg('730', [],'活动类型不能为空');
        }

        $is_fee = RequestHelper::post('is_fee', '0', '');

        $per_money = RequestHelper::post('per_money', '0', '');

        if($is_fee == 1){
            if($per_money == ''){
                $this->returnJsonMsg('1500', [],'人均金额不能为空');
            }
        }

        $active = new Activity;
        $active -> mobile = $mobile;
        $active -> title = $title;
        $active -> active_time = $active_time;
        $active -> end_time = $end_time;
        $active -> range = '1';
        $active -> is_on_line = $is_on_line;
        $active -> address = $address;
        $active -> lat = $lat;
        $active -> lng = $lng;
        $active -> introduction = $introduction;
        $active -> sign_limit = $sign_limit;
        $active -> active_type_id = $active_type_id;
        $active -> audit_status = '2';
        $active -> is_fee = $is_fee;
        if($per_money != ""){
            $active -> per_money = $per_money;
        }

        $act = $active->save(false);

        $aid = \Yii::$app->db_social->getLastInsertId();
        $active_community = new ActivityCommunity;
        $active_community -> activity_id = $aid;
        $active_community -> community_id = $community_id;
        $active_community -> city_id = $community_city_id;
        $com = $active_community -> save(false);

        $active_photo = new ActivityPhoto;
        $active_photo -> activity_id = $aid;
        $active_photo -> image = $image;
        $photo = $active_photo -> save(false);

        if (!$act || !$com || !$photo) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }


        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    } 


    /**
     * 编辑活动
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */
    public function actionEdit()
    {
        

        $id = RequestHelper::post('id', '', '');
        if (empty($id)) {
            $this->returnJsonMsg('732', [],'活动id不能为空');
        }

        $activity = Activity::find()->select(['id'])->where(['id'=>$id])->asArray()->one();
        if (empty($activity)) {
            $this->returnJsonMsg('733', [],'活动不存在');
        }

        $title = RequestHelper::post('title', '', '');
        if (empty($title)) {
            $this->returnJsonMsg('702', [],'活动标题不能为空');
        }

        //活动图片
        $image = RequestHelper::post('image', '', '');

        $active_time = RequestHelper::post('active_time', '', '');
        if (empty($active_time)) {
            $this->returnJsonMsg('723', [], '活动开始时间不能为空');
        } 

        $end_time = RequestHelper::post('end_time', '', '');
        if (empty($end_time)) {
            $this->returnJsonMsg('724', [], '活动结束时间不能为空');
        }
        if (strtotime($end_time) <= strtotime($active_time)) {
            $this->returnJsonMsg('731', [], '活动结束时间不能小于开始时间');
        }

        $is_on_line = RequestHelper::post('is_on_line', '', '');
        if ($is_on_line == "") {
            $this->returnJsonMsg('726', [],'活动地点不能为空');
        }

        $address = RequestHelper::post('address', '', '');
        if ($is_on_line == 1 && empty($address)) {
            $this->returnJsonMsg('727', [],'活动地点不能为空');
        }

        $lat =  RequestHelper::post('lat', '', '');
        if (!empty($address) && empty($lat)) {
            $this->returnJsonMsg('728', [],'活动地点纬度不能为空');
        } 

        $lng =  RequestHelper::post('lng', '', '');
        if (!empty($address) && empty($lng)) {
            $this->returnJsonMsg('729', [],'活动地点经度不能为空');
        }

        //活动介绍
        $introduction = RequestHelper::post('introduction', '', '');

        //活动人数上限
        $sign_limit = RequestHelper::post('sign_limit', '', '');

        //活动类型
        $active_type_id = RequestHelper::post('active_type_id', '', '');
        if (empty($active_type_id)) {
            $this->returnJsonMsg('730', [],'活动类型不能为空');
        }

        $is_fee = RequestHelper::post('is_fee', '0', '');

        $per_money = RequestHelper::post('per_money', '0', '');

        if($is_fee == 1){
            if($per_money == ''){
                $this->returnJsonMsg('1500', [],'人均金额不能为空');
            }
        }


        $act = Activity::updateAll(['title'=>$title,'active_time'=>$active_time,'end_time'=>$end_time,
            'is_on_line'=>$is_on_line,'address'=>$address,'lat'=>$lat,'lng'=>$lng,'introduction'=>$introduction,'sign_limit'=>$sign_limit,'active_type_id'=>$active_type_id,'audit_status'=>0,'is_fee'=>$is_fee,'per_money'=>$per_money],['id'=>$id]);

        $photo = ActivityPhoto::updateAll(['image'=>$image],['activity_id'=>$id]);

        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 活动详情
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */
    public function actionActivityInfo()
    {   
        $id = RequestHelper::post('id', '', '');
        if (empty($id)) {
            $this->returnJsonMsg('732', [],'活动id不能为空');
        }

        $mobile = RequestHelper::post('mobile', '', '');//当前登录者手机号
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $step = User::find()->select(['step'])->where(['mobile'=>$mobile])->scalar();
        if ((int)$step != 8) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }

        $field[] = "i500_activity.id";
        $field[] = "i500_activity.title";
        $field[] = "i500_activity.active_time";
        $field[] = "i500_activity.end_time";
        $field[] = "i500_activity.address";
        $field[] = "i500_activity.introduction";
        $field[] = "i500_activity.fee";
        $field[] = "i500_activity.sign_limit";
        $field[] = "i500_activity.is_on_line";
        $field[] = "i500_activity.range";//范围
        $field[] = "i500_activity.active_type_id";//类别id
        $field[] = "i500_activity.lat";//纬度
        $field[] = "i500_activity.lng";//经度
        $field[] = "i500_activity.mobile";//发布活动者的手机号
        $field[] = "i500_activity.audit_status";//审核状态  审核状态 0=未审核1=审核中2=审核成功3=审核失败
        $field[] = "i500_activity_type.name";//类别名称
        $field[] = "i500_activity.is_fee";//收费类型0.未付费1.已付费
        $field[] = "i500_activity.per_money";//人均金额
        $field[] = "i500_user_basic_info.avatar";
        $field[] = "i500_user_basic_info.nickname";
        $field[] = "i500_user.id as user_id";
        $field['participate'] = (new Query())->select('count(id)')->from("i500_participate")->where("activity_id=i500_activity.id");
        $field['application'] = (new Query())->select('count(id)')->from("i500_participate")->where("activity_id=i500_activity.id")->andWhere(['mobile'=>$mobile]);
      
        $condition[Activity::tableName().'.id'] = $id;
        $condition[Activity::tableName().'.status'] = '1';
        $result = Activity::find()
                             ->select($field)
                             ->join('LEFT JOIN','i500_activity_type','i500_activity_type.id=i500_activity.active_type_id')
                             ->join('LEFT JOIN','i500_participate','i500_participate.activity_id=i500_activity.id')
                             ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_activity.mobile')
                             ->join('LEFT JOIN','i500_user','i500_user.mobile=i500_activity.mobile')
                             ->with(['photo'=>function ($query){$query->select(['activity_id','image']);}])
                             ->where($condition)
                             ->asArray()
                             ->one();        
        $this->returnJsonMsg('200',$result, Common::C('code','200','data','[]'));
    }

     /**
     * 相关活动
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */
    public function actionActivityRelated()
    {   
        $id = RequestHelper::post('id', '', '');
        if (empty($id)) {
            $this->returnJsonMsg('732', [],'活动id不能为空');
        }
        $type = Activity::find()->select(['active_type_id'])->where(['id'=>$id])->andWhere(['status'=>1])->asArray()->one();

        $mobile = RequestHelper::post('mobile', '', '');//当前登录者手机号
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $step = User::find()->select(['step'])->where(['mobile'=>$mobile])->scalar();
        if ((int)$step != 8) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }

        $number = RequestHelper::post('number', '', '');//显示个数
        if (empty($number)) {
            $number = '3';
        }

        $field[] = "i500_activity.id";
        $field[] = "i500_activity.title";
        $field[] = "i500_activity.active_time";
        $field[] = "i500_activity.end_time";
        $field[] = "i500_activity.address";
        $field[] = "i500_activity.sign_limit";
        $field[] = "i500_activity.active_type_id";//类别id
        $field[] = "i500_activity.lat";//纬度
        $field[] = "i500_activity.lng";//经度
        $field[] = "i500_activity.audit_status";//审核状态  审核状态 0=未审核1=审核中2=审核成功3=审核失败
        $field[] = "i500_activity_type.name";//类别名称
        $field['participate'] = (new Query())->select('count(id)')->from("i500_participate")->where("activity_id=i500_activity.id");
        $field['application'] = (new Query())->select('count(id)')->from("i500_participate")->where("activity_id=i500_activity.id")->andWhere(['mobile'=>$mobile]);
      
        $condition[Activity::tableName().'.active_type_id'] = $type['active_type_id'];
        $condition[Activity::tableName().'.status'] = '1';
        $condition[Activity::tableName().'.audit_status'] = '2';
        $current_time = date('Y-m-d H:i:s');
        $result = Activity::find()
                     ->select($field)
                     ->join('LEFT JOIN','i500_activity_type','i500_activity_type.id=i500_activity.active_type_id')
                     ->join('LEFT JOIN','i500_participate','i500_participate.activity_id=i500_activity.id')
                     ->with(['photo'=>function ($query){$query->select(['activity_id','image']);}])
                     ->where($condition)
                     ->andWhere(['<>',Activity::tableName().'.id',$id])
                     ->andWhere(['>','i500_activity.end_time',$current_time])
                     ->orderBy('i500_activity.id desc')
                     ->limit($number)
                     ->asArray()
                     ->all();        
        $this->returnJsonMsg('200',$result, Common::C('code','200','data','[]'));
    }


    /**
     * 我的活动
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */
     public function actionMyActivity()
    {   
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $user_mobile = RequestHelper::post('user_mobile', '', '');
        if (!empty($user_mobile)) {
            if (!Common::validateMobile($user_mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }
        $uid = RequestHelper::post('uid', '', '');
        if(!empty($uid)) {
            $user_mobile = User::find()->select(['mobile'])->where(['id'=>$uid])->scalar();
        }
        $step = User::find()->select(['step'])->where(['mobile'=>$user_mobile])->scalar();
        if ((int)$step != 8) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }

        
        $page = RequestHelper::post('page', '1', 'intval');
        $size = 10;
        //获取小区id
        if (!empty($user_mobile)) {
            $user_comm_id =  UserBasicInfo::find()->select(['last_community_id'])->where(['mobile'=>$user_mobile])->scalar();
            if(empty($user_comm_id)) {
                return $this->returnJsonMsg('500',[],'网络繁忙');
            }
            $community_id = $user_comm_id;
        }

        $participate = Participate::find()->select(['activity_id'])->where(['mobile'=>$user_mobile])->column();

        $field[] = "i500_activity.id";
        $field[] = "i500_activity.title";
        $field[] = "i500_activity.mobile";
        //$field[] = "i500_activity.image";
        $field[] = "i500_activity.active_time";
        $field[] = "i500_activity.end_time";
        $field[] = "i500_activity.address";
        $field[] = "i500_activity.introduction";
        $field[] = "i500_activity.fee";
        $field[] = "i500_activity.sign_limit";
        $field[] = "i500_activity.is_on_line";
        $field[] = "i500_activity.range";//范围
        $field[] = "i500_activity.active_type_id";//类别id
        $field[] = "i500_activity.lat";//纬度
        $field[] = "i500_activity.lng";//经度
        $field[] = "i500_activity.audit_status";//审核状态  审核状态 0=未审核1=审核中2=审核成功3=审核失败
        $field[] = "i500_activity.remark";//审核理由
        $field[] = "i500_activity.is_fee";
        $field[] = "i500_activity.per_money";
        $field[] = "i500_activity_type.name";//类别名称
        $field[] = "i500_activity_community.community_id";//小区id
        //$field[] = "i500_participate.attend";
        $field['participate'] = (new Query())->select('count(id)')->from("i500_participate")->where("activity_id=i500_activity.id");
        $field['application'] = (new Query())->select('count(id)')->from("i500_participate")->where("activity_id=i500_activity.id")->andWhere(['mobile'=>$mobile]); 


        if ($mobile != $user_mobile) {
             $condition1[Activity::tableName().'.audit_status'] = 2;
             $condition2[Activity::tableName().'.audit_status'] = 2;
             $condition3[Activity::tableName().'.audit_status'] = 2;
             $condition4[Activity::tableName().'.audit_status'] = 2;
        }

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

        $result = Activity::find()
                     ->select($field)
                     ->join('LEFT JOIN','i500_activity_type','i500_activity_type.id=i500_activity.active_type_id')
                     // ->join('LEFT JOIN','i500_participate','i500_participate.activity_id=i500_activity.id')
                     ->join('LEFT JOIN','i500_activity_community','i500_activity_community.activity_id=i500_activity.id')//
                     ->with(['photo'=>function ($query){$query->select(['activity_id','image']);}])
                     ->where($condition1)
                     ->orWhere($condition2)
                     ->orWhere($condition3)
                     ->orWhere($condition4)
                     ->orderBy('i500_activity.id desc')
                     ->offset(($page-1)*$size)
                     ->limit($size)
                     ->asArray()
                     ->all();
        foreach ($result as $k => $v) {
            if ($v['mobile'] == $user_mobile) {
                $result[$k]['own'] = 1;
            }  else {
                $result[$k]['own'] = 2;
            }     
        }             
        $this->returnJsonMsg('200',$result, Common::C('code','200','data','[]'));
    }


     /**
     * 我的活动数量
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */
     public function actionActivityNumber()
    {   
        $user_mobile = RequestHelper::post('user_mobile', '', '');
        if (empty($user_mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($user_mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $step = User::find()->select(['step'])->where(['mobile'=>$user_mobile])->scalar();
        if ((int)$step != 8) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }
        //获取小区id
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
        $result['count'] = $count;             
        $this->returnJsonMsg('200',$result, Common::C('code','200','data','[]'));
    }

     /**
     * 删除我的活动
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */

    public function actionActivityDeleted()
    {   
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $step = User::find()->select(['step'])->where(['mobile'=>$mobile])->scalar();
        if ((int)$step != 8) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }

        $activity_id = RequestHelper::post('activity_id', '', '');
        if (empty($activity_id)) {
            $this->returnJsonMsg('732', [],'活动id不能为空');
        }
        $act = Activity::updateAll(['status'=>0],['id'=>$activity_id]);
        if ($act) {
            $this->returnJsonMsg('200', [], Common::C('code', '200'));
        }

    }
    
    /**
     * 活动类别
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */

    public function actionActivityType()
    {   
        $result = ActivityType::find()->select(['id','name'])->where(['status'=>1])->orderBy('sort ASC')->asArray()->all();     
        $this->returnJsonMsg('200',$result, Common::C('code','200','data','[]'));
    }
}