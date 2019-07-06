<?php
/**
 * 订单表
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

use frontend\models\i500m\Shop;
use common\helpers\CurlHelper;

/**
 * 订单表
 *
 * @category MODEL
 * @package  Social
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class Order extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_order}}';
    }

    /**
     * 根据商家id，和商品金额 获取运费
     * @author renyineng <renyineng@iyangpin.com>
     * @param string $mobile     手机号
     * @param int    $address_id 收货地址ID
     * @param int    $shop_id    店铺ID
     * @return bool
     */
    public function checkAddress($mobile='', $address_id=0, $shop_id=0)
    {
        if (!empty($mobile) && !empty($address_id)) {
            $model = new UserAddress();
            $address = $model->getInfo(['mobile'=>$mobile, 'id'=>$address_id], 'lng,lat');
            //var_dump($address);
            if (!empty($address)) {
                $channelUrl = \Yii::$app->params['channelHost'];
                $url = $channelUrl.'lbs/check-address?shop_id='.$shop_id.'&lng='.$address['lng'].'&lat='.$address['lat'];
                $re = CurlHelper::get($url, true);
                if ($re['code'] == 200) {
                    return true;
                }
                return false;
            }
        }
        return false;


    }

    /**
     * 创建订单号
     * @author renyineng <renyineng@iyangpin.com>
     * @param int    $province_id 省份id
     * @param string $mobile      手机号
     * @return bool
     */
    public function createSn($province_id=0, $mobile='')
    {
        $channelUrl = \Yii::$app->params['channelHost'];
        $url = $channelUrl.'order/create-order-sn?province_id='.$province_id.'&mobile='.$mobile;
        $re = CurlHelper::get($url, true);
        if (isset($re['code']) && $re['code'] == 200) {
            return $re['data'];
        } else {
            return false;
        }
    }

    /**
     * 恢复优惠券
     * @author linxinliang <linxinliang@iyangpin.com>
     * @param int    $coupon_id 优惠券id
     * @param string $mobile    手机号
     * @return bool
     */
    public function restoreCoupon($coupon_id = 0, $mobile = '')
    {
        if (!empty($coupon_id)) {
            $coupons_model = new UserCoupons();
            $coupons_where['id']     = $coupon_id;
            $coupons_where['mobile'] = $mobile;
            $coupons_update_data['status']    = '0';
            $coupons_update_data['used_time'] = '0000-00-00 00:00:00';
            $rs = $coupons_model->updateInfo($coupons_update_data, $coupons_where);
            if (!$rs) {
                return false;
            }
            return true;
        }
        return true;
    }
}
