<?php
/**
 * 商家商品模型
 *
 * PHP Version 5
 * 商家商品模型
 *
 * @category  I500M
 * @package   Member
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      2015-08-25
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\models\shop;

use yii\data\Pagination;

/**
 * 商家商品表
 *
 * @category MODEL
 * @package  Social
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class ShopProducts extends ShopBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%shop_products}}';
    }

    /**
     * 带分页的商品列表
     * @param array  $map      查询条件
     * @param string $field    查询字段
     * @param int    $pageSize 每页多少条
     * @param string $order    排序
     * @param int    $sort     升序降序
     * @return array
     */
    public function getGoodsList($map = [], $field = '*', $pageSize = 20, $order = 'id', $sort = SORT_DESC)
    {
        $query = $this->find()->where($map);
        $count = $query->count();
        $query->select($field);
        $pages = new Pagination(['totalCount' =>$count, 'pageSize' => $pageSize]);
        $list = $query->orderBy([$order=>$sort])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
        //var_dump($list);
        return ['list'=>$list, 'count'=>$count, 'pageCount'=>$pages->pageCount];
    }
    /**
     * 根据商品id和商家id 获取这个商品所属活动
     */
    public function getActivity($shop_id, $product_id)
    {
        $activity = new ShopActivity();
        //获取正在进行的活动
        $a_model = new ActivityGoods();
        $activity_products = $a_model->getInfo(['shop_id'=>$shop_id, 'product_id'=>$product_id]);
        $info = $activity->getInfo(['id'=>$activity_products['activity_id'], 'status'=>1]);
        $time = date("Y-m-d H:i:s", time());
        $data = [];
        if (!empty($info)) {
            if ($info['start_time'] <= $time && $info['end_time'] > $time) {
                $data = [
                    'subtitle'=>$info['subtitle'],
                    'activity_price'=>$activity_products['price'],
                    'purchase_num'=>$activity_products['day_confine_num'],
                ];
            }
        }
        return $data;



//        if (!empty($activity_products)) {
//            foreach ($activity_products as $k => $v) {
//                $product_ids[] = $v['product_id'];
//            }
//
//        }
        return $activity_products;
    }

    /**
     * 减去库存
     * @param array  $map  查询条件
     * @param string $type 减or 加
     * @return bool
     */
    public function editNumber($map, $type = '-')
    {
        $model = $this->findOne($map);
        if ($type == '-') {
            $model->product_number = $model->product_number - 1;
        } elseif ($type == '+') {
            $model->product_number = $model->product_number + 1;
        }
        $re = $model->save();
        return $re;
    }
}
