<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/24
 * Time: 15:08
 */

namespace frontend\models\i500_social;


class PrivilegeCategory extends SocialBase
{
    /**
     * 商品分类表
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_privilege_category}}";
    }
}