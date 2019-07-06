<?php
/**
 * 特权商城订单
 * User: wyy
 */

namespace frontend\models\i500_social;

class PrivilegeOrder extends SocialBase
{
    /**
     * 表名
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_privilege_order}}";
    }

    /**
     * 表关联
     * @return \yii\db\ActiveQuery
     */
    public function getDetails()
    {
        return $this->hasMany(PrivilegeOrderDetail::className(),['order_sn'=>'order_sn']);
    }
}