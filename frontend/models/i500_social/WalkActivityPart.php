<?php
/**
 * WalkActivityPart.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/7/31 14:33
 */

namespace frontend\models\i500_social;


class WalkActivityPart extends SocialBase
{
    /**
     * 表连接
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_walk_activity_part}}";
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