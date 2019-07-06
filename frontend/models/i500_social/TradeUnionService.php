<?php
/**
 * 工会会员服务信息
 * User: wyy
 * Date: 2017/10/8
 */

namespace frontend\models\i500_social;


class TradeUnionService extends SocialBase
{
    /**
     * 表连接
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_trade_union_service}}';
    }
}