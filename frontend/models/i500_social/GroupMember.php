<?php
namespace frontend\models\i500_social;

use common\helpers\Common;

class GroupMember extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_group_member}}';
    }

    /**
     * 获取成员信息
     * @return string
     */
    public function getUser(){
        return $this->hasOne(UserBasicInfo::className(),['mobile'=>'mobile']);
    }
    
}