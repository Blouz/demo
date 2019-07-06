<?php
/**
 * 购物车
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Cart
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      2015/8/25
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v1\controllers;

use frontend\models\i500_social\Cart;
use frontend\models\i500m\Product;
use frontend\models\shop\ActivityGoods;
use frontend\models\shop\ShopActivity;
use frontend\models\shop\ShopProducts;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use yii\helpers\ArrayHelper;

/**
 * Cart
 *
 * @category Social
 * @package  Cart
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class CartController extends BaseController
{
    public $enableCsrfValidation = false;
    public function actionList()
    {
        $mobile = RequestHelper::get('mobile', 0, 'intval');
        $shop_id = RequestHelper::get('shop_id', 0, 'intval');
        if (empty($user_id) || empty($shop_id)) {
            $this->returnJsonMsg('101', [], '缺少参数');
        }
        $cart = new Cart();
        $cartList = $cart->getList(['mobile'=>$mobile, 'shop_id'=>$shop_id, 'status'=>1]);
        if (!empty($cartList)) {
            $product_ids = [];
            foreach ($cartList as $k => $v) {
                $product_ids[] = $v['product_id'];
            }
            if (!empty($product_ids)) {
                $s_products = new ShopProducts();
                $goods_list = $s_products->getList(['product_id'=>$product_ids, 'shop_id'=>$shop_id], 'product_id');
                foreach ($goods_list as $k => $v) {

                }
                $m_goods = new Product();
                $m_goods->getList(['id'=>$product_ids], 'id,');
            }

        }
    }
    /**
     * 添加购物车 {["product_id":"10","num":10],}
     * @return array
     */
    public function actionCheckCart()
    {
        $cart = RequestHelper::post('json_str', '');
        $shop_id = RequestHelper::post('shop_id', 0, 'intval');
        if (empty($shop_id)) {
            $this->returnJsonMsg(101, [], '无效的商家id');
        }
        if (empty($cart)) {
            $this->returnJsonMsg(102, [], '无效的数据');
        }
        $cart = htmlspecialchars_decode($cart);
        $list = json_decode($cart, true);
        if (empty($list)) {
            $this->returnJsonMsg(102, [], '无效的json数据');
        }
        $goods = [];
        if (!empty($list)) {
            $product_ids = $goods_lists = [];
            foreach ($list as $k => $v) {
                $product_ids[] = $v['product_id'];
            }
            if (!empty($product_ids)) {
                $s_products = new ShopProducts();
                $map = ['product_id'=>$product_ids, 'shop_id'=>$shop_id];
                $goods_arr = $s_products->getList($map, 'product_id, product_number, status');

                foreach ($goods_arr as $k => $v) {
                    $goods_lists[$v['product_id']] = $v;
                }
                foreach ($list as $k => $v) {
                    $status = ArrayHelper::getValue($goods_lists, $v['product_id'].'.status', 0);
                    $goods[$k]['product_id'] = $v['product_id'];
                    $goods[$k]['message'] = '';
                    if ($status != 1) {
                        $goods[$k]['is_avail'] = 0;
                        $goods[$k]['message'] = '此商品已经下架';
                        continue;
                    }
                    $product_number = ArrayHelper::getValue($goods_lists, $v['product_id'].'.product_number', 0);
                    if ($v['num'] > $product_number) {
                        $goods[$k]['is_avail'] = 0;
                        $goods[$k]['message'] = '库存仅剩'.$product_number;
                    }
                }
            }
            $this->returnJsonMsg(200, $goods, 'SUCCESS');
        } else {
            $this->returnJsonMsg(101, [], '购物车数据为空');
        }

    }

    /**
     * 判断购物车合法性
     * @return json
     */
    public function actionAddCartCheck()
    {
        $product_id = RequestHelper::get('product_id', 0, 'intval');
        $shop_id = RequestHelper::get('shop_id', 0, 'intval');
        $num = RequestHelper::get('num', 0, 'intval');
        if (empty($product_id)) {
            $this->returnJsonMsg(101, [], '无效的商品id');
        }

        if (empty($shop_id)) {
            $this->returnJsonMsg(102, [], '无效的商家id');
        }

        if (empty($num)) {
            $this->returnJsonMsg(103, [], '购物车数量不合法');
        }
        $s_products = new ShopProducts();
        $info = $s_products->getInfo(['shop_id'=>$shop_id, 'product_id'=>$product_id], 'product_number,status');
        //var_dump($info);
        //判断这个商品是否属于活动
        $activity_model = new ActivityGoods();
        $re = $activity_model->getActivitygoods($shop_id, $product_id);
        $data['num'] = $info['product_number'];
        $data['status'] = $info['status'];
        if (!empty($re)) {
            //$data['num'] = $re['num'];
        }

        if (!empty($info)) {
            if ($info['status'] != 1) {
                $this->returnJsonMsg(104, $data, '此商品已经下架或者删除');
            }
            if ($info['product_number'] < $num) {
                $this->returnJsonMsg(105, $data, '库存不足');
            }
            //判断是否达到限购数量
            $shop_model = new ShopProducts();
            $activity = $shop_model->getActivity($this->shop_id, $product_id);
            if (!empty($activity)) {
                if ($activity['purchase_num'] < $num) {
                    $this->returnJsonMsg(106, $data, '已经达到限购数量!');
                }
            }
        } else {
            $this->returnJsonMsg(107, [], '此商品不存在');
        }
        $this->returnJsonMsg(200, [], '购物车合法');
    }
}
