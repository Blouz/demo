<?php
/**
 * TradeUnionPolicyControllerController.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * User: huangdekui<huangdekui@i500m.com>
 * Time: 2017/9/26 17:04
 */

namespace frontend\models\i500_social;


class TradeUnionUser extends SocialBase
{
    /**
     * 数据表名称
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_trade_union_user}}";
    }
}