<?php
/**
 * 用户优惠券表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015-08-25
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */

namespace frontend\models\i500_social;

/**
 * 用户优惠券表
 *
 * @category MODEL
 * @package  Social
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class UserCoupons extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_user_coupons}}';
    }

    /**
     * 获取最大的没有过期的优惠劵 并且符合使用条件
     * @author renyineng <renyineng@iyangpin.com>
     * @param string $mobile 手机号
     * @param float  $total  总金额
     * @return array
     */
    public function getMaxCoupon($mobile, $total)
    {
        $map['mobile'] = $mobile;
        $map['status'] = 0;
        $time = date("Y-m-d H:i:s");
        //$andMap = ['>', 'start_time', date("Y-m-d H:i:s")];
        $andMap = ['>', 'expired_time', date("Y-m-d H:i:s")];//过期时间大于当前时间

//        $max = $this->find()
//            ->select('serial_number,min_amount,type_name,min_amount,')
//            ->where($map)->andWhere($andMap)->andWhere(['<=', 'min_amount', $total])->asArray()->one();
//        $max = $this->find()->where($map)->andWhere($andMap)->andWhere(['<=', 'min_amount', $total])->max('par_value');
        $list = $this->find()->select('id,par_value')
            ->where($map)->andWhere($andMap)->andWhere(['<=', 'min_amount', $total])->orderBy("par_value desc")
            ->asArray()->one();

        //$max = !empty($max) ? $max : 0;
        if (!empty($list)) {
            return $list;
        }
        return [];
            //->asArray()->all();
    }

    /**
     * 验证优惠券
     * @author renyineng <renyineng@iyangpin.com>
     * @param int $coupon_id 优惠券ID
     * @param int $total     订单金额
     * @return bool|mixed
     */
    public function checkCoupon($coupon_id = 0, $total = 0)
    {
        if (empty($coupon_id)) {
            return false;
        }
        $coupon = $this->getInfo(['id'=>$coupon_id], 'serial_number,min_amount,type_name,min_amount,expired_time');
        if (!empty($coupon)) {
            if ($coupon['expired_time'] > date("Y-m-d H:i:s") && $coupon['status'] == 0 && $coupon['min_amount'] <= $total) {
                return $coupon['par_value'];
            }
        } else {
            return false;
        }
    }
    public function fields()
    {
        $fields = [
            'id',
            // 字段名和属性名相同
            'serial_number',
            // 字段名为"email", 对应的属性名为"email_address"
            'type_name' => 'type_name',
            'min_amount',
            'par_value',
            'get_time',
            'expired_time',
            'used_time',
            'status',
            // 字段名为"name", 值由一个PHP回调函数定义
//            'name' => function ($model) {
//                return $model->first_name . ' ' . $model->last_name;
//            },
        ];
        //0 为 未使用 1 以使用  2 已经过期

        $fields['status'] = function ($model) {
            //var_dump($model->status);//exit();
            if ($model->expired_time <= date("Y-m-d H:i:s")) {
                return 2;
            } else {
                return $model->status;
            }
            //return \common\libs\BankCard::getBankImg($this->bank);
        };
        return $fields;

    }


}
