<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/24
 * Time: 15:18
 */

namespace frontend\models\i500_social;


class PrivilegeSpecification extends SocialBase
{
    /**
     * 商品规格表
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_privilege_specification}}";
    }
}