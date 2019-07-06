<?php
/**
 * 查看物流记录
 * User: wyy
 */

namespace frontend\models\i500_social;

class PrivilegeOrderLogistics extends SocialBase
{
    /**
     * 表名
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_privilege_order_logistics}}";
    }
}