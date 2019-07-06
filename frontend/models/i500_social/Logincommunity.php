<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\models\i500_social;


class Logincommunity extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_login_community}}';
    }
    
   
    public function attributeLabels()
    {
        return [
            'id' => '主键ID',
            'community_id' => '社区id',
            'mobile' => '手机号',
            'community_city_id' => '社区所属城市id',
            'is_deleted' => '是否删除 0=未删除 1=已删除',
            'modify_time' => '创建时间',
            'community_name' => '小区名称',
            'address'=>'社区详细地址',
            'lng'=>'经度',
            'lat'=>'纬度',
	    'join_in'=>'是否已选择小区'
        ];
    }
	public function getUserBasicInfo(){
        return $this->hasOne(UserBasicInfo::className(),['mobile'=>'mobile']);
    }
}
