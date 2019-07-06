<?php
/**
 * WalkSubmit.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/7/31 17:06
 */

namespace frontend\models\i500_social;


use frontend\models\i500m\Community;
use frontend\models\i500m\District;

class WalkSubmit extends SocialBase
{
    /**
     * 表连接
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_walk_submit}}";
    }

    /**
     * 用户关联
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(UserBasicInfo::className(),['mobile'=>'mobile']);
    }

    /**
     * 小区关联
     * @return \yii\db\ActiveQuery
     */
    public function getCommunity()
    {
        return $this->hasOne(Community::className(),['id'=>'community_id']);
    }

    /**
     * 区域关联
     * @return \yii\db\ActiveQuery
     */
    public function getDistrict()
    {
        return $this->hasOne(District::className(),['id'=>'district_id']);
    }

    /**
     * 团队关联
     * @return \yii\db\ActiveQuery
     */
    public function getTeam()
    {
        return $this->hasOne(WalkTeam::className(),['id'=>'team_id']);
    }
}