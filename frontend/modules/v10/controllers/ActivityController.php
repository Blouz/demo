<?php

namespace frontend\modules\v10\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use yii\helpers\ArrayHelper;
use frontend\models\i500_social\Activity;
use frontend\models\i500_social\ActivityCommunity;
use frontend\models\i500_social\Participate;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\ActivityPhoto;
use frontend\models\i500_social\ActivityType;
use yii\db\Query;

class ActivityController extends BaseController
{
    /**
     * 活动详情与相关活动
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */
    public function actionActivityInfo()
    {   
        $activity_id = RequestHelper::post('activity_id', '', '');
        if (empty($activity_id)) {
            $this->returnJsonMsg('732', [],'活动id不能为空');
        }

        $type = Activity::find()->select(['active_type_id'])->where(['id'=>$activity_id])->asArray()->one();

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
        $number = RequestHelper::post('page_size', '3', 'intval');

        $field[] = "i500_activity.id";
        $field[] = "i500_activity.title";
        $field[] = "i500_activity.active_time";
        $field[] = "i500_activity.end_time";
        $field[] = "i500_activity.address";
        $field[] = "i500_activity.introduction";
        $field[] = "i500_activity.sign_limit";
        $field[] = "i500_activity.is_on_line";
        $field[] = "i500_activity.range";//范围
        $field[] = "i500_activity.mobile";//发布活动者的手机号
        $field[] = "i500_activity.audit_status";//审核状态  审核状态 0=未审核1=审核中2=审核成功3=审核失败
        $field[] = "i500_activity_type.name";//类别名称
        $field[] = "i500_user_basic_info.avatar";
        $field[] = "i500_user_basic_info.nickname";
        $field['participate'] = (new Query())->select('count(id)')->from("i500_participate")->where("activity_id=i500_activity.id");
        $field['application'] = (new Query())->select('count(id)')->from("i500_participate")->where("activity_id=i500_activity.id")->andWhere(['mobile'=>$mobile]);
      
        $condition[Activity::tableName().'.id'] = $activity_id;
        $condition[Activity::tableName().'.status'] = '1';

        $result['info'] = Activity::find()
                     ->select($field)
                     ->join('LEFT JOIN','i500_activity_type','i500_activity_type.id=i500_activity.active_type_id')
                     ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_activity.mobile')
                     ->with(['photo'=>function ($query){$query->select(['activity_id','image']);}])
                     ->where($condition)
                     ->asArray()
                     ->one();     

        $field1[] = "i500_activity.id";
        $field1[] = "i500_activity.title";
        $field1[] = "i500_activity.active_time";
        $field1[] = "i500_activity.address";
        $field1['participate'] = (new Query())->select('count(id)')->from("i500_participate")->where("activity_id=i500_activity.id");
        $field1['application'] = (new Query())->select('count(id)')->from("i500_participate")->where("activity_id=i500_activity.id")->andWhere(['mobile'=>$mobile]);
      
        $condition1[Activity::tableName().'.active_type_id'] = $type['active_type_id'];
        $condition1[Activity::tableName().'.status'] = '1';
        $condition1[Activity::tableName().'.audit_status'] = '2';
        //当前小区的
        $community_id = UserBasicInfo::find()->select(['last_community_id'])->where(['mobile'=>$this->mobile])->asArray()->scalar();
        $community_id = empty($community_id) ? -1 : $community_id;

        $result['related'] = Activity::find()
                     ->select($field1)
                     ->join('LEFT JOIN','i500_activity_type','i500_activity_type.id=i500_activity.active_type_id')
                     ->join('LEFT JOIN','i500_activity_community','i500_activity_community.activity_id=i500_activity.id')
                     ->with(['photo'=>function ($query){$query->select(['activity_id','image']);}])
                     ->where($condition1)
                     ->andWhere(['<>',Activity::tableName().'.id',$activity_id])
                     ->andWhere(['or',['i500_activity_community.community_id'=>$community_id],['range'=>0]])
                     ->orderBy('i500_activity.id desc')
                     ->limit($number)
                     ->asArray()
                     ->all();                            
        $this->returnJsonMsg('200',$result, Common::C('code','200','data','[]'));
    }
}