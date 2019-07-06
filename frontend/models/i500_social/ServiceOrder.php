<?php

namespace frontend\models\i500_social;

use Yii;

/**
 * This is the model class for table "i500_service_order".
 *
 * @property integer $id
 * @property integer $uid
 * @property string $mobile
 * @property integer $service_id
 * @property integer $service_uid
 * @property string $service_mobile
 * @property string $order_sn
 * @property integer $service_way
 * @property string $total
 * @property string $service_info_title
 * @property string $service_info_description
 * @property string $service_info_image
 * @property string $service_info_price
 * @property integer $service_info_unit
 * @property string $appointment_service_time
 * @property string $appointment_service_address
 * @property string $remark
 * @property integer $status
 * @property integer $pay_status
 * @property integer $pay_site_id
 * @property string $create_time
 * @property string $confirm_time
 * @property string $start_time
 * @property string $cancel_time
 * @property string $pay_time
 * @property string $user_complete_time
 * @property string $servicer_complete_time
 * @property integer $user_evaluation_status
 * @property integer $servicer_evaluation_status
 * @property integer $source_type
 * @property string $unionpay_tn
 * @property integer $community_city_id
 * @property integer $community_id
 */
class ServiceOrder extends SocialBase
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'i500_service_order';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['source_type'],'required'],
            [['service_id', 'service_way', 'status', 'pay_status', 'pay_site_id', 'source_type', 'community_city_id', 'community_id'], 'integer'],
            [['total', 'service_info_price'], 'number'],
            [['appointment_service_time', 'create_time', 'confirm_time', 'start_time', 'cancel_time', 'pay_time', 'user_complete_time', 'servicer_complete_time'], 'safe'],
            [['mobile', 'service_mobile'], 'string', 'max' => 11],
            [['order_sn','service_info_unit'], 'string', 'max' => 24],
            [['service_info_title'], 'string', 'max' => 120],
            [['service_info_description', 'appointment_service_address', 'remark'], 'string', 'max' => 255],
            [['service_info_image'], 'url'],
            [['unionpay_tn'], 'string', 'max' => 32]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID主键递增',
            'mobile' => '手机号(用户唯一标示)',
            'service_id' => '服务ID',
            'service_mobile' => '提供服务的手机号',
            'order_sn' => '订单号',
            'service_way' => '服务方式 1=到家服务2=买家到店',
            'total' => '订单总金额',
            'service_info_title' => '服务信息的标题',
            'service_info_description' => '服务信息的描述',
            'service_info_image' => '服务信息的图片(多个用,分割)',
            'service_info_price' => '服务信息的价格',
            'service_info_unit' => '服务信息的单位元/次 元/小时',
            'appointment_service_address' => '预约服务地址',
            'remark' => '订单备注',
            'status' => '订单状态 0=未确认 1=已经确认 2=已经取消 3=进行中 4=等待体验方确认 5=待评价',
            'pay_status' => '支付状态 0=未支付 1=已支付 2=已退款，3=退款中',
            'pay_site_id' => '支付方式ID',
            'create_time' => '订单创建时间',
            'confirm_time' => '商家确认服务时间',
            'pay_time' => '订单支付时间',
            'user_evaluation_status' => '体验方评价状态 0=未评价 1=已评价',
            'servicer_evaluation_status' => '服务方评价状态 0=未评价 1=已评价',
            'source_type' => '来源',
            'unionpay_tn' => '支付交易流水号',
            'community_city_id' => '小区城市ID',
            'community_id' => '小区ID',
        ];
    }
}
