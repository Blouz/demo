<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "i500_service".
 *
 * @property integer $id
 * @property integer $uid
 * @property string $mobile
 * @property integer $category_id
 * @property integer $son_category_id
 * @property string $image
 * @property string $title
 * @property string $price
 * @property integer $unit
 * @property integer $service_way
 * @property string $description
 * @property integer $audit_status
 * @property integer $status
 * @property integer $user_auth_status
 * @property integer $servicer_info_status
 * @property integer $community_city_id
 * @property integer $community_id
 * @property integer $is_deleted
 * @property string $create_time
 * @property string $update_time
 */
class Service extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'i500_service';
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
    public function rules()
    {
        return [
            [['uid', 'category_id', 'son_category_id', 'unit', 'service_way', 'audit_status', 'status', 'user_auth_status', 'servicer_info_status', 'community_city_id', 'community_id', 'is_deleted'], 'integer'],
            [['price'], 'number'],
            [['create_time', 'update_time'], 'safe'],
            [['mobile'], 'string', 'max' => 11],
            [['image', 'description'], 'string', 'max' => 255],
            [['title'], 'string', 'max' => 120]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID主键递增',
            'uid' => '用户ID(用户唯一标示)',
            'mobile' => '手机号(用户唯一标示)',
            'category_id' => '分类ID',
            'son_category_id' => '子分类ID',
            'image' => '服务图片',
            'title' => '标题',
            'price' => '价格',
            'unit' => '单位 1=元 2=元/次 3=元/小时',
            'service_way' => '服务方式 1=上门服务2=到店体验',
            'description' => '服务描述',
            'audit_status' => '审核状态 0=未审核1=审核中2=审核成功3=审核失败',
            'status' => '上/下架状态 1=上架2=下架',
            'user_auth_status' => '用户认证状态 1=认证成功 2=认证失败',
            'servicer_info_status' => '服务人信息审核状态 1=审核成功 2=审核失败',
            'community_city_id' => '小区城市ID',
            'community_id' => '小区ID',
            'is_deleted' => '是否删除1=已删除2=未删除',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
        ];
    }
}
