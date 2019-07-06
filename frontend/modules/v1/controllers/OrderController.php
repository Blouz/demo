<?php
/**
 * 订单
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Order
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      2015/8/25
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v1\controllers;

use frontend\models\i500_social\Cart;
use frontend\models\i500_social\Dispatch;
use frontend\models\i500_social\Order;
use frontend\models\i500_social\OrderDetail;
use frontend\models\i500_social\UserAddress;
use frontend\models\i500_social\UserCoupons;
use frontend\models\i500m\Product;
use frontend\models\i500m\Shop;
use frontend\models\shop\ActivityGift;
use frontend\models\shop\ActivityGoods;
use frontend\models\shop\ShopActivity;
use frontend\models\shop\ShopProducts;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use yii\helpers\ArrayHelper;

/**
 * Order
 *
 * @category Social
 * @package  Order
 * @author   renyineng <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class OrderController extends BaseController
{
    public $enableCsrfValidation = false;

    /**
     * 确认订单页面 [{"product_id":"9000","num":10},{"product_id":"10","num":10}]
     * @return json
     */
    public function actionConfirm()
    {
        $this->shop_id = RequestHelper::post('shop_id', 0, 'intval');
        $mobile = RequestHelper::post('mobile', '');
        $cart_goods = RequestHelper::post('json_str', '');
        if (empty($this->shop_id)) {
            $this->returnJsonMsg(101, [], '无效的商家id');
        }
        if (empty($mobile)) {
            $this->returnJsonMsg(102, [], '无效的用户');
        }
        if (empty($cart_goods)) {
            $this->returnJsonMsg(103, [], '无效的数据');
        }
        $data = [];
        //配送方式 商家配送
        $dispatch = new Dispatch();
        $data['dispatch_list'] = $dispatch->getList(['status'=>1], '*', 'sort asc');
        //var_dump($dispatch_list);
        //配送时间
        $data['dispatch_time'] = $dispatch->getDispatchTime($this->shop_id);
       // var_dump($dispatch_time);
        //配送地址
        $address_model = new UserAddress();
        $address = $address_model->getList(['mobile'=>$mobile,'is_deleted'=>2], 'id,consignee,sex,consignee_mobile,search_address,details_address,is_default');
        $default_address = [];
        if (!empty($address)) {
            foreach ($address as $k => $v) {
                if ($v['is_default'] == 1) {
                    $default_address = $v;
                    break;
                }
            }
            //如果没有默认收货地址 则读取第一个
            if (empty($default_address)) {
                $default_address = $address[0];
            }
        }
        $data['default_address'] = $default_address;
        //$cart_goods = urldecode($cart_goods);
        $cart_goods = htmlspecialchars_decode($cart_goods);
        $cart_goods = json_decode($cart_goods, true);
        $total =  0;
        if (empty($cart_goods)) {
            $this->returnJsonMsg(104, [], '无效的json数据');
        }
        $gift_goods = [];
        $goods_total =  0;
        $img_path = Yii::$app->params['imgHost'];
        if (substr($img_path, -1) == '/') {
            $img_path = substr($img_path, 0, -1);
        }
        if (!empty($cart_goods)) {
            $product_ids = $cart_list = $goods_lists = [];
            foreach ($cart_goods as $k => $v) {
                $product_ids[] = $v['product_id'];
                $cart_list[$v['product_id']] = $v;
            }
            if (!empty($product_ids)) {
                $s_products = new ShopProducts();
                $map = ['product_id'=>$product_ids, 'shop_id'=>$this->shop_id];
                $goods_arr = $s_products->getList($map, 'product_id, product_number, status, price');

                $p_model = new Product();
                $p_list = $p_model->getList(['id'=>$product_ids, 'status'=>1], 'id,name,image');
                $p_list = ArrayHelper::index($p_list, 'id');

                foreach ($goods_arr as $k => $v) {
                    if ($v['status'] != 1) {
                        $this->returnJsonMsg(105, [], '有商品下架');
                    }
                    $buy_num = ArrayHelper::getValue($cart_list, $v['product_id'].'.num', 0);
                    $goods_arr[$k]['buy_num'] = $buy_num;
                    if ($v['product_number'] < $buy_num) {
                        $low_products_id[] = $v['product_id'];

                    }
                    $goods_arr[$k]['name'] =  ArrayHelper::getValue($p_list, $v['product_id'].'.name', '');
                    $goods_arr[$k]['image'] =  $img_path . ArrayHelper::getValue($p_list, $v['product_id'].'.image', '');
                    $goods_arr[$k]['activity_price'] = 0;
                    //$cart_list[$v['product_id']]['total'] = $v['total'];
                    $goods_total += ($v['price'] * $buy_num);
                }
                if (!empty($low_products_id)) {
                    $this->returnJsonMsg(106, $low_products_id, '商品库存不足');
                }
               // var_dump($goods_arr);
                //判断活动商品
                //获取正在进行的活动
               // $re = $this->_checkActivity($cart_list, $this->shop_id);
                $data_activity = $this->_checkActivity($goods_arr, $this->shop_id);
                //var_dump($data_activity);
                if ($data_activity === 0) {
                    $this->returnJsonMsg(107, [], '购物车数据为空');
                } elseif ($data_activity === 1) {
                    $this->returnJsonMsg(1081, [], '库存不足');
                } else {
                    $gift_goods = $data_activity['gift_list'];
                }
                $data['item'] = $data_activity['goods_list'];
                $data['gift_goods'] = $gift_goods;
                //获取优惠劵

                $coupons = new UserCoupons();
                $coupons_max = $coupons->getMaxCoupon($mobile, $goods_total);
                //var_dump($coupons_max);
                $coupon_value = 0;
                if (!empty($coupons_max)) {
                    $coupon_value = $coupons_max['par_value'];
                }
                $data['coupon'] = $coupons_max;
                //配送费
                $data['freight'] = 0;
                $shop_model  = new Shop();
                $info = $shop_model->getInfo(['id'=>$this->shop_id], true, 'sent_fee,free_money,freight,shop_name,contact_name,mobile,address');
                if ($info['sent_fee'] > $goods_total) {
                    $this->returnJsonMsg(109, [], '不够起送费');
                }
                if ($info['free_money'] > $goods_total) {
                    $data['freight'] = $info['freight'];
                }
                //商品总价
                $data['goods_total'] = $goods_total;
//                echo "<br />";
//                //支付总价
               // echo $coupon_value;
                //echo "<br />";
                //echo $goods_total + $data['freight'];
                //echo 10.01-10;
                $data['total'] = $goods_total + $data['freight'] - $coupon_value;

                $data['total'] = round($data['total'], 2);
                if ($data['total'] < 0) {
                    $data['total'] = 0;
                }
                $data['shop_address'] = [
                    'consignee'=>$info['shop_name'],
                    'consignee_mobile'=>$info['mobile'],
                    'address'=>$info['address'],
                ];
                $this->returnJsonMsg(200, $data, 'SUCCESS');
            }

        } else {
            $this->returnJsonMsg(107, [], '购物车数据为空');
        }
        //善品总价
        //配送费
        //优惠劵
        //商品清单
        //赠品
    }
    /**
     * 检查购物车活动，返回赠品
     * @param array $goods_list 购物车商品
     * @param int   $shop_id    商家id
     * @return array|int
     */
    private function _checkActivity($goods_list, $shop_id)
    {
        //判断活动商品
        if (!empty($goods_list)) {
            foreach ($goods_list as $k => $v) {
                $product_ids[] = $v['product_id'];
            }
        } else {
            return 0;
        }
        $data['goods_list'] = $goods_list;
        $data['gift_list'] = [];
        //var_dump($goods_list);
        //获取正在进行的活动
        $shop_activity = new ShopActivity();
        $activity_list = $shop_activity->getCurrentActivity($shop_id);
        //var_dump($activity_list);
        $gift_goods = $activity_ids = []; //购物车中的商品参与的活动id
        if (!empty($activity_list)) {
            $buy_activity_ids = $full_goods = $purchase_goods = [];
            $activity = new ActivityGoods();
            //判断购物车中是否有正在进行的活动
            $activity_list = ArrayHelper::index($activity_list, 'id');
            $activity_goods = $activity->getList(['shop_id'=>$this->shop_id, 'product_id'=>$product_ids]);
            //var_dump($activity_goods);
            if (!empty($activity_goods)) {
                $new_goods = ArrayHelper::index($activity_goods, 'product_id');
                //var_dump($goods_list);
                foreach ($goods_list as $k => $v) {
                    $product_ids[] = $v['product_id'];
                    $purchase_num = ArrayHelper::getValue($new_goods, $v['product_id'].'.day_confine_num', 0);
                    $goods_list[$k]['activity_price'] = ArrayHelper::getValue($new_goods, $v['product_id'].'.price', 0);
                    $goods_list[$k]['activity_id'] = ArrayHelper::getValue($new_goods, $v['product_id'].'.activity_id', 0);

//                    if ($v['buy_num'] >= $purchase_num) {
//                        echo $v['product_id'];
//                        return 1;//库存超过最大限制
//                    }
                }
                $gift_model = new ActivityGift();
                foreach ($activity_goods as $k => $v) {
                    //$activity_goods[$k]['type'] = ArrayHelper::getValue($activity_list, $v['activity_id'].'.type', 0);
                    $type = ArrayHelper::getValue($activity_list, $v['activity_id'].'.type', 0);

                    if ($type == 1) {
                        $buy_activity_ids[] = $v['activity_id'];
                    } elseif ($type == 2) {
                        $meet_amount = ArrayHelper::getValue($activity_list, $v['activity_id'].'.meet_amount', 10);
                        //$v['meet_amount'] = $meet_amount;
                        //var_dump($v);
                        $full_goods[$v['activity_id']]['amount'] = $meet_amount;//以活动为单元的商品列表
                        $full_goods[$v['activity_id']]['list'][] = $v;//以活动为单元的商品列表
                    }

                    $activity_data[$v['activity_id']][] = $v;//以活动为单元的商品列表
                }
                //var_dump($buy_activity_ids);
                //获取买赠的赠品
                if (!empty($buy_activity_ids)) {
                    $gift_goods = $gift_model->getList(['activity_id'=>$buy_activity_ids, 'shop_id'=>$shop_id]);
                }
                //var_dump($gift_goods);
                //获取满赠的赠品
                //var_dump($full_goods);exit();
                $gift = [];
                if (!empty($full_goods)) {
                    foreach ($full_goods as $key => $item) {
                        $full_total = 0;
                        //var_dump($item['list']);exit();
                        foreach ($item['list'] as $v) {
                            $full_total +=$v['price'];
                        }
                        //echo $full_total;
                        if ($item['amount'] <= $full_total) {
                            //echo 13;exit();
                            $gift[] = $gift_model->getInfo(['activity_id'=>$key, 'shop_id'=>$shop_id]);

                        }
                    }
                }
                //var_dump($gift);
                $gift_goods = array_merge($gift_goods, $gift);
                $data['goods_list'] = $goods_list;
                $data['gift_list'] = $gift_goods;
            }
        } else {

        }
        return $data;
    }
    /**
     * 保存订单 {["product_id":"10","num":10],["product_id":"10","num":10]}
     */
    public function actionSave()
    {
        $cart_goods = RequestHelper::post('json_str', '');//购物车商品数据
        $this->shop_id = RequestHelper::post('shop_id', 0, 'intval');
        $mobile = RequestHelper::post('mobile', '', '');//手机号
        $send_time = RequestHelper::post('send_time', '');//配送时间
        $address_id = RequestHelper::post('address_id', 0, 'intval');//收货地址id
        $coupon_id = RequestHelper::post('coupon_id', '', 'intval');//优惠劵id
        //$pay_method_id = RequestHelper::post('pay_method_id', 0, 'intval');//支付方式id
        $dispatch_id = RequestHelper::post('dispatch_id', 0, 'intval');//配送方式id
        $source_type = RequestHelper::post('source_type', 0, 'intval');//配送方式id

        $community_id = RequestHelper::post('community_id', 0, 'intval');//小区id


        //$mobile = RequestHelper::post('mobile', '', '');
        if (empty($this->shop_id)) {
            $this->returnJsonMsg(101, [], '无效的商家id');
        }
        if (empty($cart_goods)) {
            $this->returnJsonMsg(102, [], '无效的数据');
        }
        if (empty($mobile)) {
            $this->returnJsonMsg(103, [], '无效的手机号');
        }
        if (empty($send_time)) {
            $this->returnJsonMsg(104, [], '配送时间为空');
        }
        //配送费
        $shop_model  = new Shop();
        $address_model = new UserAddress();
        $info = $shop_model->getInfo(['id'=>$this->shop_id], true, 'id,shop_name,contact_name,mobile,address,province,sent_fee,free_money,freight');
        if ($dispatch_id == 2) {//上门自提
            //$shop_model = new Shop();
            //$address = $shop_model->getInfo(['id'=>$this->shop_id], 'id,shop_name,contact_name,mobile,address,province');
            $address_info = [
                'id'=>$info['id'],
                'consignee'=>$info['shop_name'],
                'consignee_mobile'=>$info['mobile'],
                'province_id'=>$info['province'],
            ];
            $address = $info['address'];
        } else {
            if (empty($address_id)) {
                $this->returnJsonMsg(105, [], '请选择有效的收货地址');
            }
            //获取收货地址

            $address_info = $address_model->getInfo(['id'=>$address_id, 'mobile'=>$mobile]);
            if (empty($address_info)) {
                $this->returnJsonMsg(103, [], '收货地址不存在！');
            }
            $address = $address_info['search_address'] . ' ' . $address_info['details_address'];

        }
        if (empty($community_id)) {
            $this->returnJsonMsg(103, [], '无效的小区参数');
        }
        $cart_goods = htmlspecialchars_decode($cart_goods);
        $cart_goods = json_decode($cart_goods, true);
        if (empty($cart_goods)) {
            $this->returnJsonMsg(104, [], '无效的json数据');
        }

        $goods = [];
        $time = time();
        $m_order = new Order();
        //var_dump($m_order);
        $order_sn = $m_order->createSn($address_info['province_id'], $mobile);
        if (empty($order_sn)) {
            $this->returnJsonMsg(103, [], '订单号生成失败！');
        }
        $order = [
            'order_sn'=>$order_sn,
            'mobile'=>$mobile,
            'shop_id'=>$this->shop_id,
            'goods_total'=>0,
            'total'=>0,
            'remark'=>'',
            'status'=>0,
            'code'=>rand(1000, 9999),
            'pay_status'=>0,
            'pay_method_id'=>0,
            'ship_status'=>0,
            'create_time'=>date("Y-m-d H:i:s", $time),
            'consignee'=>$address_info['consignee'],
            'consignee_mobile'=>$address_info['consignee_mobile'],
            'address'=>$address,
            'province'=>$address_info['province_id'],
            'city'=>0,
            'district'=>0,
            'freight'=>0,
            'coupon_id'=>0,
            'dis_amount'=>0,
            'tag'=>'',
            'source_type'=>$source_type,
            'dispatch_id'=>$dispatch_id,
            'send_time'=>$send_time,
            'community_id'=>$community_id,
        ];
        $order_detail = '';
        $goods_total = 0;
        $dis_amount = 0;
        //$coupon_id = 0;
        if (!empty($cart_goods)) {
            $product_ids = $cart_list = $goods_lists = [];
            foreach ($cart_goods as $k => $v) {
                $product_ids[] = $v['product_id'];
                $cart_list[$v['product_id']] = $v;
            }
            if (!empty($product_ids)) {
                $s_products = new ShopProducts();
                $map = ['product_id'=>$product_ids, 'shop_id'=>$this->shop_id];
                $goods_arr = $s_products->getList($map, 'product_id, product_number, status, price,type');

                $p_model = new Product();
                $p_list = $p_model->getList(['id'=>$product_ids, 'status'=>1], 'id,name,image,attr_value');
                $p_list = ArrayHelper::index($p_list, 'id');

                foreach ($goods_arr as $k => $v) {
                    if ($v['status'] != 1) {
                        $this->returnJsonMsg(105, [], '有商品下架');
                    }
                    $buy_num = ArrayHelper::getValue($cart_list, $v['product_id'].'.num', 0);
                    $goods_arr[$k]['buy_num'] = $buy_num;
                    if ($v['product_number'] < $buy_num) {
                        $this->returnJsonMsg(106, [], '商品库存不足');
                    }
                    $goods_arr[$k]['name'] =  ArrayHelper::getValue($p_list, $v['product_id'].'.name', '');
                    $goods_arr[$k]['image'] = ArrayHelper::getValue($p_list, $v['product_id'].'.image', '');
                    $goods_arr[$k]['attr_value'] = ArrayHelper::getValue($p_list, $v['product_id'].'.attr_value', '');
                    $goods_arr[$k]['activity_id'] = 0;
                    //$cart_list[$v['product_id']]['total'] = $v['total'];
                    $goods_total += ($v['price'] * $buy_num);
                }
                //判断活动商品
                //获取正在进行的活动
                // $re = $this->_checkActivity($cart_list, $this->shop_id);
                $data_activity = $this->_checkActivity($goods_arr, $this->shop_id);
                if ($data_activity == 0) {
                    $this->returnJsonMsg(107, [], '购物车数据为空');
                } elseif ($data_activity == 1) {
                    $this->returnJsonMsg(108, [], '库存不足');
                } else {
                    $gift_goods = $data_activity['gift_list'];
                }
                $goods = $data_activity['goods_list'];
                $activity_products = $activity_gift = [];
                foreach ($goods as $k => $v) {
                    $order_detail[] = [
                        'order_sn'=>$order_sn,
                        'mobile'=>$mobile,
                        'shop_id'=>$this->shop_id,
                        'product_id'=>$v['product_id'],
                        'product_name'=>$v['name'],
                        'product_img'=>$v['image'],
                        'num'=>$v['buy_num'],
                        'price'=>$v['price'],
                        'attribute_str'=>$v['attr_value'],
                        'total'=>$v['price']*$v['buy_num'],
                        'remark'=>'',
                        'retread_num'=>0,
                        'goods_type'=>$v['type'],
                        'activity_id'=>$v['activity_id'],
                        'is_gift'=>0,
                    ];
                    if ($v['activity_id'] != 0) {
                        $activity_products[] = ['activity_id'=>$v['activity_id'], 'product_id'=>$v['product_id']];
                        //$activity_products_ids[] = $v['product_id'];
                    }


                }
                if (!empty($gift_goods)) {
                    foreach ($gift_goods as $k => $v) {
                        $order_detail[] = [
                            'order_sn'=>$time,
                            'mobile'=>$mobile,
                            'shop_id'=>$this->shop_id,
                            'product_id'=>$v['product_id'],
                            'product_name'=>$v['product_name'],
                            'product_img'=>$v['product_img'],
                            'num'=>1,
                            'price'=>0,
                            'attribute_str'=>$v['attr_value'],
                            //'attribute_str'=>'',
                            'total'=>0,
                            'remark'=>'',
                            'retread_num'=>0,
                            'goods_type'=>0,
                            'activity_id'=>$v['activity_id'],
                            'is_gift'=>1,
                        ];
                        $activity_gift[] = ['activity_id'=>$v['activity_id'], 'product_id'=>$v['product_id']];

                    }
                   // $order_detail = array_merge($order_detail, $gift_detail);
                }
               // var_dump($order_detail);exit();
                //获取优惠劵
                if (!empty($coupon_id)) {
                    $coupons = new UserCoupons();
                    $dis_amount = $coupons->checkCoupon($coupon_id, $goods_total);
                    //$dis_amount = $coupons->getField(['mobile'=>$mobile, 'id'=>$coupon_id], 'par_value');
                    if (!empty($dis_amount)) {
                        $order['coupon_id'] = $coupon_id;
                        $order['dis_amount'] = $dis_amount;
                    }

                }


                if ($info['sent_fee'] > $goods_total) {
                    $this->returnJsonMsg(109, [], '不够起送费');
                }
                if ($info['free_money'] > $goods_total) {
                    $order['freight'] = $info['freight'];
                }

                //商品总价
                $order['goods_total'] = $goods_total;
                //支付总价
                $order['total'] = $goods_total + $order['freight'] - $dis_amount;
                $pay_type = 'pay';
                if ($order['total'] <= 0) {
                    $order['total'] = 0;
                    $pay_type = 'nopay';
                }
                //插入订单表
                if (!empty($order)) {
                    $re = $m_order->insertInfo($order);
                    //var_dump($re);exit();
                    if ($re) {
                        //插入订单详情表
                        if (!empty($order_detail)) {

                            $m_detail = new OrderDetail();
                            $res = $m_detail->insertDetail($order_detail);
                            //var_dump($res);exit();
                            if ($res) {
                                //修改默认收货地址
                                $address_model->updateInfo(['is_default'=>0], ['mobile'=>$mobile]);
                                $address_model->updateInfo(['is_default'=>1], ['id'=>$address_id, 'mobile'=>$mobile]);
                                //减商品库存
                                if (!empty($order_detail)) {
                                    //减库存
                                    foreach ($order_detail as $v) {
                                        $s_products->editNumber(['shop_id'=>$this->shop_id, 'product_id'=>$v['product_id']]);
                                    }

                                }
                                //减活动库存
                                if (!empty($activity_products)) {
                                    $activity_product_model = new ActivityGoods();
                                    foreach ($activity_products as $v) {
                                        $activity_product_model->editNumber(['activity_id'=>$v['activity_id'], 'product_id'=>$v['product_id']]);
                                    }

                                }
                                //减赠品库存
                                if (!empty($gift_goods)) {
                                    $gift_model = new ActivityGift();
                                    foreach ($activity_gift as $v) {
                                        $gift_model->editNumber(['activity_id'=>$v['activity_id'], 'product_id'=>$v['product_id']]);
                                    }

                                }
                                //更新优惠劵信息
                                if (!empty($coupon_id) && !empty($dis_amount)) {

                                    $re = $coupons->updateInfo(['used_time'=>date("Y-m-d H:i:s"), 'status'=>1, 'shop_id'=>$this->shop_id], ['id'=>$coupon_id]);
                                    if (empty($re)) {
                                        $this->returnJsonMsg(109, [], '优惠劵信息更新失败!');
                                    }

                                }
                                if ($order['total'] == 0) {
                                    //无需支付的 修改订单状态
                                }
                                $this->returnJsonMsg(200, ['order_sn'=>$order_sn, 'type'=>$pay_type], 'SUCCESS');
                            } else {
                                $this->returnJsonMsg(108, [], '订单详情数据插入失败');
                            }
                        } else {
                            $this->returnJsonMsg(107, [], '数据不合法');
                        }
                    } else {
                        $this->returnJsonMsg(108, [], '订单数据插入失败');
                    }
                } else {
                    $this->returnJsonMsg(107, [], '数据不合法');
                }

            }

        } else {
            $this->returnJsonMsg(107, [], '购物车数据为空');
        }
    }

    /**
     * 检查收货地址是否在商家配送范围之内
     * @return json
     */
    public function actionCheckAddress()
    {
        $shop_id = RequestHelper::get('shop_id', "0");
        $mobile = RequestHelper::get('mobile', "0");
        $address_id = RequestHelper::get('address_id', "0");
        $model = new Order();
        $re = $model->checkAddress($mobile, $address_id, $shop_id);
        if ($re) {
            $this->returnJsonMsg(200, [], '收货地址在商家配送范围之内');
        } else {
            $this->returnJsonMsg(101, [], '收货地址不在商家配送范围之内');
        }

    }
    public function actionGetCoupons()
    {
        $mobile = RequestHelper::get('mobile', "0");
        $goods_total = RequestHelper::get('goods_total', 0);
        $model = new UserCoupons();
        $list = $model->getList(['mobile'=>$mobile, 'status'=>0], 'id,coupon_type_id,mobile,type_name,min_amount,par_value,expired_time,remark');
        $date = date("Y-m-d H:i:s");
        $coupons = $un_coupons = [];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if ($v['expired_time'] > $date) {
                    if ($v['min_amount'] <= $goods_total) {
                        $coupons[] = [
                            'id'=>$v['id'],
                            'type_name'=>$v['type_name'],
                            'min_amount'=>$v['min_amount'],
                            'par_value'=>$v['par_value'],
                            'expired_time'=>$v['expired_time'],
                            'remark'=>$v['remark'],
                            'is_avail'=>1,
                        ];
                    } else {
                        $un_coupons[] = [
                            'id'=>$v['id'],
                            'type_name'=>$v['type_name'],
                            'min_amount'=>$v['min_amount'],
                            'par_value'=>$v['par_value'],
                            'expired_time'=>$v['expired_time'],
                            'remark'=>$v['remark'],
                            'is_avail'=>0,
                        ];
                    }

                }

            }
        }
        $coupons = array_merge($coupons, $un_coupons);
        if ($coupons) {
            $this->returnJsonMsg(200, $coupons, '获取成功!');
        } else {
            $this->returnJsonMsg(101, [], '暂无可用优惠劵!');
        }
    }
    public function actionTest()
    {
        $model = new OrderDetail();

        $model->cancleOrder('1441010766', '13617258652');
    }
}
