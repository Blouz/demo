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
class ServiceOrderDetail extends SocialBase
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'i500_service_order_detail';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_sn','mobile','service_mobile','service_id','service_name','service_img','service_unit','price','total','num'],'required'],
            [['total', 'price'], 'number'],
            [['mobile', 'service_mobile'], 'string', 'max' => 11],
            [['order_sn','service_unit'], 'string', 'max' => 24],
            [['service_name'], 'string', 'max' => 120],
            [['service_img'], 'url'],
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
            'price' => '服务金额',
            'total' => '服务总金额',
            'num' => '服务数量',
            'service_name' => '服务标题',
            'service_img' => '服务图片',
            'service_unit' => '服务单位元/次 元/小时',
        ];
    }
//    public function addOrderDetail($order_detail)
//    {
//        if (!empty($order_detail)) {
//            if (isset($order_detail[0])) {
//                $columns = array_keys($order_detail[0]);
//            }
//            self::getDB()->createCommand()->batchInsert(self::tableName(), $columns, );
//        }
//    }
}
