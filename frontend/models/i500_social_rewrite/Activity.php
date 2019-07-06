<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\models\i500_social_rewrite;

use yii\db\Query;

class Activity extends SocialRewriteBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_activity}}';
    }

    /*
     * 获取活动列表
     * 
     * @params string $mobile 手机号
     * @params array  $id     活动ID
     * @return array
     */
    public function getList($mobile,$id,$page=1,$page_size=10){

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
        $field[] = "i500_activity_community.community_id";//小区id
        $field[] = "i500_activity.is_fee";
        $field[] = "i500_activity.per_money";
        $field[] = "i500_user_basic_info.avatar";
        $field[] = "i500_user_basic_info.nickname";
        $field['participate'] = (new Query())->select('count(id)')->from("i500_participate")->where("activity_id=i500_activity.id");
        $field['application'] = (new Query())->select('count(id)')->from("i500_participate")->where("activity_id=i500_activity.id")->andWhere(['mobile'=>$mobile]);
        $field[] = "i500_activity.sort";

        $condition[Activity::tableName().'.status'] = 1;
        $condition[Activity::tableName().'.audit_status'] = '2';
        $condition[Activity::tableName().'.id'] = $id;
       
        $cond = array();
        $cond[Activity::tableName().'.range'] = 0;
        $cond[Activity::tableName().'.status'] = 1;
        $cond[Activity::tableName().'.audit_status'] = '2';
        // $current_time = time();
        $result = $this->find()
                        ->select($field)
                        ->join('LEFT JOIN','i500_activity_type','i500_activity_type.id=i500_activity.active_type_id')
                        ->join('LEFT JOIN','i500_activity_community','i500_activity_community.activity_id=i500_activity.id')
                        ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_activity.mobile')
                        ->with(['photo'=>function ($query){$query->select(['activity_id','image']);}])
                        ->where($condition)
                        // ->andWhere(['>','i500_activity.active_time',$current_time])
                        ->orWhere($cond)
                        ->orderBy('i500_activity.sort desc,i500_activity.id desc')
                        ->offset(($page-1)*$page_size)
                        ->limit($page_size)
                        ->asArray()
                        ->all();
        
        return $result;
    }


    public function getCommunity()
    {
        //同样第一个参数指定关联的子表模型类名
        return $this->hasMany(ActivityCommunity::className(), ['activity_id' => 'id']);
    }
    public function getPhoto()
    {
        //同样第一个参数指定关联的子表模型类名
        return $this->hasMany(ActivityPhoto::className(), ['activity_id' => 'id']);
    }
}