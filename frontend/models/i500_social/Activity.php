<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\models\i500_social;

use common\helpers\Common;

class Activity extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_activity}}';
    }
    public function getCommunity()
    {
        //同样第一个参数指定关联的子表模型类名
        return $this->hasMany(ActivityCommunity::className(), ['activity_id' => 'id']);
    }
    public function getPhoto()
    {
        //同样第一个参数指定关联的子表模型类名
        return $this->hasMany(ActivityPhoto::className(), ['activity_id' => 'id']);
    }
}