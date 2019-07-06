<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/24
 * Time: 15:07
 */

namespace frontend\models\i500_social;


class PrivilegeAddress extends SocialBase
{
    /**
     * 收货地址表
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_privilege_address}}";
    }
}