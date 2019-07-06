<?php
namespace frontend\models\i500_social;

use common\helpers\Common;

class Group extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_group}}';
    }
    
    /**
     * 获取类别
     * @return \yii\db\ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne(GroupType::className(), ['id'=>'group_type_id']);
    }

    /**
     * 获取成员
     * @return \yii\db\ActiveQuery
     */
    public function getMember()
    {
        return $this->hasMany(GroupMember::className(), ['group_id'=>'group_id']);
    }
}