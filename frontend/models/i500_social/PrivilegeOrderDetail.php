<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/24
 * Time: 15:15
 */

namespace frontend\models\i500_social;


class PrivilegeOrderDetail extends SocialBase
{
    /**
     * 订单商品表
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_privilege_order_detail}}";
    }
}