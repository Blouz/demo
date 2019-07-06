<?php

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
class UserOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%i500_user_order}}';
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
    public function behaviors()
    {
//        return [
//            TimestampBehavior::className(),
//        ];
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'create_time',
                'updatedAtAttribute' => false,
                'value' => function() { return date('Y-m-d H:i:s');}
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status','order_type', 'pay_status', 'user_comment_status', 'service_comment_status', 'source_type', 'community_city_id', 'community_id'], 'integer'],
            [['total'], 'number'],
            [['pay_method'],'in', 'range'=>['account', 'wxpay', 'alipay']],
            [['mobile', 'shop_mobile','community_city_id', 'community_id'], 'required'],
            [['create_time', 'operation_time', 'pay_time'], 'safe'],
            [['mobile', 'shop_mobile'], 'string', 'max' => 11],
            [['order_sn','pay_method'], 'string', 'max' => 24],
            [['order_info'], 'string', 'max' => 1000],
            [['remark'], 'string', 'max' => 60],
            [['unionpay_tn'], 'string', 'max' => 32]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mobile' => '手机号',
            'shop_mobile' => '服务方手机号',
            'order_sn' => '订单号',
            'total' => '订单总金额',
            'remark' => '订单备注',
            'status' => '订单状态 1 已经抢单 2= 已完成 3=已经取消',
            'pay_status' => '支付状态 0=未支付 1=已支付 2=已退款，3=退款中',
            'pay_method' => '无效的支付方式',
            'create_time' => '订单创建时间',
            'operation_time' => '商家确认服务时间',
            'pay_time' => '订单支付时间',
            'user_comment_status' => '体验方评价状态 0=未评价 1=已评价',
            'service_comment_status' => '服务方评价状态 0=未评价 1=已评价',
            'source_type' => '来源 1=pc 2=wap 3=ios 4=android',
            'unionpay_tn' => '支付交易流水号',
            'community_city_id' => '小区城市ID',
            'community_id' => '小区ID',
        ];
    }
    public function getOrderdetail()
    {
        //同样第一个参数指定关联的子表模型类名
        return $this->hasMany(UserOrderDetail::className(), ['order_sn' => 'order_sn']);
    }
}
