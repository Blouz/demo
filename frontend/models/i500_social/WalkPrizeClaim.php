<?php
/**
 * WalkPrizeClaim.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/8/1 9:59
 */

namespace frontend\models\i500_social;


use frontend\models\i500m\Community;
use frontend\models\i500m\District;

class WalkPrizeClaim extends SocialBase
{
    /**
     * 表连接
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_walk_prize_claim}}";
    }

    /**
     * 用户关联
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(UserBasicInfo::className(),['mobile'=>'type_key']);
    }

    /**
     * 小区关联
     * @return \yii\db\ActiveQuery
     */
    public function getCommunity()
    {
        return $this->hasOne(Community::className(),['id'=>'type_key']);
    }

    /**
     * 区域关联
     * @return \yii\db\ActiveQuery
     */
    public function getDistrict()
    {
        return $this->hasOne(District::className(),['id'=>'type_key']);
    }
}