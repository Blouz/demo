<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/24
 * Time: 15:12
 */

namespace frontend\models\i500_social;


class PrivilegeGoodsImage extends SocialBase
{
    /**
     * 商品图片表
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_privilege_goods_image}}";
    }
}