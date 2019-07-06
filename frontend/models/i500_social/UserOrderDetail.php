<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\models\i500_social;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%i500_seek_help_order}}".
 *
 * @property integer $id
 * @property string $mobile
 * @property integer $service_id
 * @property string $service_mobile
 * @property string $order_sn
 * @property integer $service_way
 * @property string $total
 * @property string $service_title
 * @property string $service_description
 * @property string $service_image
 * @property string $service_price
 * @property string $service_unit
 * @property string $service_address
 * @property string $remark
 * @property integer $status
 * @property integer $pay_status
 * @property integer $pay_site_id
 * @property string $create_time
 * @property string $operation_time
 * @property string $pay_time
 * @property integer $user_comment_status
 * @property integer $service_comment_status
 * @property integer $source_type
 * @property string $unionpay_tn
 * @property integer $community_city_id
 * @property integer $community_id
 */
class UserOrderDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%i500_userorder_detail}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_social');
    }
    /**
     * @inheritdoc
     */


    /**
     * @inheritdoc
     */
    public function rules()
    {
      
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sid' => '服务或需求id',
            'mobile' => '服务方手机号',
            'order_sn' => '订单号',
            'price' => '单价',
            'title' => '标题',
            'content' => '订单备注',
            'qty' => '数量',
            'unit' => '单位',
            'image' => '配图',
            'community_city_id' => '城市id',
            'community_id' => '社区id',
            'category_id' => '一级分类id',
            'son_category_id' => '二级分类id',

        ];
    }
    
}