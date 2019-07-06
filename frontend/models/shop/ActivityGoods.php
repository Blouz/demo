<?php
/**
 * 活动商品表
 *
 * PHP Version 5
 *
 * @category  I500M
 * @package   Member
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      2015-08-26
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\models\shop;

/**
 * 商家活动
 *
 * @category MODEL
 * @package  Social
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class ActivityGoods extends ShopBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%shop_activity_product}}';
    }
    /**
     * 减去库存
     *
     * @param array  $map  查询条件
     * @param string $type 减or 加
     * @param int    $num  数量
     *
     * @return bool
     */
    public function editNumber($map, $type = '-', $num = 1)
    {
        $model = $this->findOne($map);
        if ($type == '-') {
            $model->day_confine_num = $model->day_confine_num - $num;
        } elseif ($type == '+') {
            $model->day_confine_num = $model->day_confine_num + $num;
        }
        $re = $model->save();
        return $re;
    }

    /**
     * 根据商品id
     * @param int $shop_id    店铺ID
     * @param int $product_id 商品ID
     * @return array
     */
    public function getActivitygoods($shop_id = 0, $product_id = 0)
    {
        $info = $this->getInfo(['shop_id'=>$shop_id, 'product_id'=>$product_id]);
        $data = [];
        if (!empty($info)) {
            $activity = new ShopActivity();
            $act_info = $activity->getInfo(['id'=>$info['activity_id'], 'shop_id'=>$shop_id]);
            $time = date("Y-m-d H:i:s");
            if (!empty($act_info)) {
                if ($act_info['status'] == 1 && $act_info['start_time'] <= $time && $act_info['end_time'] >= $time) {
                    $data = ['price'=>$info['price'], 'num'=>$info['day_confine_num']];
                }
            }
        }
        return $data;
    }

    /**
     * 根据 商家id 商品id  活动id 获取活动信息
     * @param int $shop_id     商家id
     * @param int $product_id  商品id
     * @param int $activity_id 活动id
     * @return array
     */
    public function getActivityGood($shop_id, $product_id, $activity_id)
    {
        $now = date("Y-m-d H:i:s");
        //此活动有效
        $data = [];
        $activity = new ShopActivity();
        $activity_info = $activity->getInfo(['id'=>$activity_id]);
        if ($activity_info['status'] == 1) {
            if ($activity_info['start_time'] < $now && $activity_info['end_time'] > $now) {
                $activity_products_model = new ActivityGoods();
                $activity_products = $activity_products_model->getInfo(['shop_id'=>$shop_id, 'product_id'=>$product_id]);
                $data = [
                    'activity_id'=>$activity_id,
                    'name'=>$activity_info['name'],
                    'subtitle'=>$activity_info['subtitle'],
                    'activity_price'=>$activity_products['price'],
                    'purchase_num'=>$activity_products['day_confine_num'],
                ];
            }
        }
        return $data;

    }

    /**
     * 活动商品列表
     * @param string $activity_ids 活动IDS
     * @param string $products_id  商品IDS
     * @param int    $shop_id      店铺ID
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getActivityGoodsList($activity_ids = '', $products_id = '', $shop_id = 0)
    {
        $activity = new ShopActivity();
        $activity_id = [];
        $activity_list = $activity->getList(['id'=>$activity_ids, 'status'=>1]);
        foreach ($activity_list as $v) {
            $activity_id[] = $v['id'];
        }
        $goods_list = [];
        if (!empty($activity_id)) {
            $goods_list = $this->getList(['activity_id'=>$activity_id, 'product_id'=>$products_id, 'shop_id'=>$shop_id]);
        }
        return $goods_list;
    }
}
