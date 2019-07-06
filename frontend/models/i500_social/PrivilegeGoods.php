<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/24
 * Time: 15:11
 */

namespace frontend\models\i500_social;


class PrivilegeGoods extends SocialBase
{
    /**
     * 商品表
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_privilege_goods}}";
    }

    /**
     * 表关联
     * @return \yii\db\ActiveQuery
     */
    public function getPhoto()
    {
        return $this->hasMany(PrivilegeGoodsImage::className(),['g_id'=>'id']);
    }

    public function getPicture()
    {
        return $this->hasOne(PrivilegeGoodsImage::className(),['g_id'=>'id']);
    }
}