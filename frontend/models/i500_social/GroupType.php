<?php
namespace frontend\models\i500_social;

use common\helpers\Common;

class GroupType extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_group_type}}';
    }
    
}