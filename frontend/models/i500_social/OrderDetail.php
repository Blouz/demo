<?php
/**
 * 订单详情表
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

use frontend\models\shop\ActivityGoods;
use frontend\models\shop\ShopProducts;

/**
 * 订单详情表
 *
 * @category MODEL
 * @package  Social
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class OrderDetail extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_order_detail}}';
    }

    /**
     * 插入订单详情数据
     * @author renyineng <renyineng@iyangpin.com>
     * @param array $order_detail 商品输数据
     * @return int
     */
    public function insertDetail($order_detail)
    {
        if (!empty($order_detail)) {
            $fields = [
                'order_sn',
                'mobile','shop_id',
                'product_id',
                'product_name',
                'product_img',
                'num',
                'price',
                'attribute_str',
                'total',
                'remark',
                'retread_num',
                'goods_type',
                'activity_id',
                'is_gift'
            ];

            $re = self::getDB()->createCommand()->batchInsert('i500_order_detail', $fields, $order_detail)->execute();
            return $re;
        }
    }

    /**
     * 根据订单号取消订单
     * @author renyineng <renyineng@iyangpin.com>
     * @param string $order_sn 订单号号
     * @param string $mobile   手机号
     * @return bool;
     */
    public function cancleOrder($order_sn, $mobile)
    {
        if (!empty($order_sn) && !empty($mobile)) {
            $order_detail = $this->getList(['order_sn'=>$order_sn, 'mobile'=>$mobile]);
            if (!empty($order_detail)) {
                $model = new ShopProducts();
                $activity_model = new ActivityGoods();
                foreach ($order_detail as $k => $v) {

                    $model->editNumber(['product_id'=>$v['product_id'], 'shop_id'=>$v['shop_id']], '+', $v['num']);
                    if ($v['activity_id'] != 0) {
                        //活动的库存增加
                        $activity_model->editNumber(['product_id'=>$v['product_id'], 'shop_id'=>$v['shop_id']], '+', $v['num']);
                    }
                    if ($v['is_gift'] == 1) {
                        $activity_gift = new ActivityGoods();
                        $activity_gift->editNumber(['product_id'=>$v['product_id'], 'shop_id'=>$v['shop_id']], '+', $v['num']);
                    }

                }
            }
            return true;
        } else {
            return false;
        }

    }
}
