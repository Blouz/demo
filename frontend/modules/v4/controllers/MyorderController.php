<?php
/**
 * 我的订单
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Myorder
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/06
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v4\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Order;
use frontend\models\i500_social\OrderDetail;
use frontend\models\i500_social\Exchange;
use frontend\models\i500_social\ShopGrade;
use frontend\models\i500m\RefundOrder;
use frontend\models\i500m\Shop;
use frontend\models\i500m\PaySite;
use frontend\models\i500m\OrdersSendCoupons;
use frontend\models\i500m\CouponsType;
use yii\helpers\ArrayHelper;
use yii\db\Query;
/**
 * 我的订单
 *
 * @category Social
 * @package  Myorder
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class MyorderController extends BaseController
{
    public static $pay_method = [
        'alipay'=>'支付宝',
        'wxpay'=>'微信',
    ];
    /**
     * 我的订单列表
     * @return array
     */
    public function actionList()
    {
        $mobile = RequestHelper::get('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $order_status = RequestHelper::get('order_status', '1', '');
        if (!in_array($order_status, ['1','2','3','4'])) {
            $this->returnJsonMsg('804', [], Common::C('code', '804'));
        }
        $page      = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '6', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        $new_detail = array();
        $dns = Shop::getDB()->dsn;
        $db = strstr($dns,"dbname=");
        $name = str_replace("dbname=","",$db);
        if ($order_status != '4') {
            $order_model = new Order();
            $order_where['mobile']  = $mobile;
            $fields[] = 'order_sn';
            $fields[] = 'create_time';
            $fields[] = 'total';
            $fields[] = 'status';
            $fields[] = 'pay_status';
            $fields[] = 'ship_status';
            $fields[] = 'consignee';
            $fields[] = 'consignee_mobile';
            $fields[] = 'address';
            $fields[] = 'dispatch_id';
//            $fields['freight'] = (new Query())->select('freight')->from($name.".shop")->where("id=i500_order.shop_id")->offset(0)->limit(1);
            $order_and_where = '';
            if ($order_status == '1') {
                /**待支付**/
                $order_where['pay_status'] = '0';
                $order_where['status']     = '0';
            }
            if ($order_status == '2') {
                /**待收货**/
                $order_and_where = ['or', ['=', 'status', '4'], ['=', 'status', '1']];
            }
            if ($order_status == '3') {
                /**已完成**/
//                $order_and_where = ['or', ['=', 'ship_status', '2'], ['=', 'status', '2'], ['=', 'status', '5']];
                $order_and_where = ['or', ['=', 'ship_status', '2'],['=', 'status', '5']];
            }
            $order_list = $order_model->getPageList($order_where, $fields, 'id desc', $page, $page_size, $order_and_where);

            if (!empty($order_list)) {
                $order_detail_model = new OrderDetail();

                foreach ($order_list as $k => $v) {
                    $order_sn_arr[] = $v['order_sn'];
//                    $rs = $this->_getOrderGoodsInfo($mobile, $v['order_sn']);
//                    $info[$k]['goods_info'] = $rs['order_detail_info'];
                }
                if (!empty($order_sn_arr)) {
                    $order_detail_fields = 'shop_id,order_sn,product_id,product_name,product_img,num,price,total,activity_price,goods_type,attribute_str,goods_type';
                    $order_detail = $order_detail_model->getList(['order_sn'=>$order_sn_arr], $order_detail_fields);
                    //$order_detail = ArrayHelper::index($order_detail, 'order_sn');
                    foreach ($order_detail as $key=>$val) {
                        $new_detail[$val['order_sn']][] = $val;
                    }
                }
                //var_dump($order_detail);exit();
                foreach ($order_list as $k => $v) {
                    $order_list[$k]['goods_info'] = ArrayHelper::getValue($new_detail, $v['order_sn']);
//                    $rs = $this->_getOrderGoodsInfo($mobile, $v['order_sn']);
//                    $info[$k]['goods_info'] = $rs['order_detail_info'];
                    $order_list[$k]['freight'] = "0.00";
                }
                

            }
        }
        $this->returnJsonMsg('200', $order_list, Common::C('code', '200'));
    }

    /**
     * 订单详情
     * @return array
     */
    public function actionDetails()
    {
        $mobile = RequestHelper::get('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $order_sn = RequestHelper::get('order_sn', '', '');
        if (empty($order_sn)) {
            $this->returnJsonMsg('805', [], Common::C('code', '805'));
        }
        $order_model = new Order();
        $order_where['mobile']   = $mobile;
        $order_where['order_sn'] = $order_sn;
        $order_fields = ['status','order_sn','address','freight','goods_total'=>'total','shop_id','pay_status','create_time','consignee','consignee_mobile','pay_method_name'=>'pay_method'];
        $info = $order_model->getInfo($order_where, true, $order_fields);
        if (!empty($info)) {
            $info['pay_method_name'] = ArrayHelper::getValue(self::$pay_method, $info['pay_method_name'], '');
            if (!empty($info['shop_id'])) {
                $shop_model = new Shop();
                $shop_fields = 'contact_name,mobile';
                $shop_info = $shop_model->getInfo(['id'=>$info['shop_id']], true, $shop_fields);
                $info['delivery_man']        = $shop_info['contact_name'];
                $info['delivery_man_mobile'] = $shop_info['mobile'];
            }
            $order_detail_model = new OrderDetail();
            $order_detail_where['mobile']   = $mobile;
            $order_detail_where['order_sn'] = $order_sn;
            $order_detail_fields = 'shop_id,product_id,product_name,product_img,num,price,total,activity_price,goods_type,attribute_str,goods_type';
            $info['goods_info'] = $order_detail_model->getList($order_detail_where, $order_detail_fields, 'id desc');

            unset($info['id']);
            unset($info['mobile']);
        }
        $this->returnJsonMsg('200', $info, Common::C('code', '200'));
    }

    /**
     * 取消订单
     * @return array
     */
    public function actionCancel()
    {
        $mobile = RequestHelper::get('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $order_sn = RequestHelper::get('order_sn', '', '');
        if (empty($order_sn)) {
            $this->returnJsonMsg('805', [], Common::C('code', '805'));
        }
        $order_model = new Order();
        $order_where['mobile']   = $mobile;
        $order_where['order_sn'] = $order_sn;
        $order_fields = 'order_sn,status,pay_status,total,dis_amount,unionpay_tn,coupon_id';
        $order_info = $order_model->getInfo($order_where, true, $order_fields);
        $order_detail_model = new OrderDetail();
        if (empty($order_info)) {
            $this->returnJsonMsg('816', [], Common::C('code', '816'));
        }
        if ($order_info['pay_status'] == '0') {
            /**未支付**/
            $order_update_data['status'] = '2';
            $rs = $order_model->updateInfo($order_update_data, $order_where);
            //@todo 更新库存
            $order_detail_rs   = $order_detail_model->cancleOrder($order_sn, $mobile);
            //@todo 更新优惠券
            $restore_coupon_rs = $order_model->restoreCoupon($order_info['coupon_id'], $mobile);
        } elseif ($order_info['pay_status'] == '1') {
            /**已支付**/
            $connection = \Yii::$app->db_social;
            $transaction = $connection->beginTransaction();
            try {
                //@todo 这块是CRM的退款表
                $refund_order_model = new RefundOrder();
                $refund_order_add_data['order_sn']    = $order_sn;
                $refund_order_add_data['type']        = '1';
                $refund_order_add_data['add_time']    = date('Y-m-d H:i:s', time());
                $refund_order_add_data['money']       = $order_info['total'];
                $refund_order_add_data['code_money']  = $order_info['dis_amount'];
                $refund_order_add_data['unionpay_tn'] = $order_info['unionpay_tn'];
                $refund_order_add_data['from_data']   = '1';
                $rs = $refund_order_model->insertInfo($refund_order_add_data);
                $order_update_data['status'] = '2';
                $rs = $order_model->updateInfo($order_update_data, $order_where);
                $transaction->commit();
                //@todo 更新库存
                $order_detail_rs   = $order_detail_model->cancleOrder($order_sn, $mobile);
                //@todo 更新优惠券
                $restore_coupon_rs = $order_model->restoreCoupon($order_info['coupon_id'], $mobile);
            } catch (\Exception $e) {
                $transaction->rollBack();
                $this->returnJsonMsg('400', [], Common::C('code', '400'));
                //throw $e;
            }
        } else {
            /**已退款**/
            $this->returnJsonMsg('807', [], Common::C('code', '807'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 确认收货
     * @return array
     */
    public function actionConfirmGoods()
    {
        $mobile = RequestHelper::get('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $order_sn = RequestHelper::get('order_sn', '', '');
        if (empty($order_sn)) {
            $this->returnJsonMsg('805', [], Common::C('code', '805'));
        }
        $order_model = new Order();
        $order_where['mobile']   = $mobile;
        $order_where['order_sn'] = $order_sn;
        $order_fields = 'order_sn,status,ship_status';
        $rs = $order_model->getInfo($order_where, true, $order_fields);
        if (empty($rs)) {
            $this->returnJsonMsg('816', [], Common::C('code', '816'));
        }
        /**只有发货中才能确认收货**/
        if ($rs['ship_status'] != '1') {
            $this->returnJsonMsg('806', [], Common::C('code', '806'));
        }
        $order_update_data['status']        = '5';  //订单完成
        $order_update_data['ship_status']   = '2';  //确定收货
        $order_update_data['pay_status']    = '1';  //已支付
        $order_update_data['delivery_time'] = date('Y-m-d H:i:s', time());
        $rs = $order_model->updateInfo($order_update_data, $order_where);
        if (empty($rs)) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 商家评分
     * @return array
     */
    public function actionEvaluate()
    {
        $uid = RequestHelper::post('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $data['uid']    = $uid;
        $data['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($data['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($data['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $data['shop_id'] = RequestHelper::post('shop_id', '', '');
        if (empty($data['shop_id'])) {
            $this->returnJsonMsg('803', [], Common::C('code', '803'));
        }
        $data['order_sn'] = RequestHelper::post('order_sn', '', '');
        if (empty($data['order_sn'])) {
            $this->returnJsonMsg('805', [], Common::C('code', '805'));
        }
        $data['grade']       = RequestHelper::post('grade', '0', '');
        $data['content']     = RequestHelper::post('content', '0', '');
        $data['create_time'] = date('Y-m-d H:i:s', time());
        $model = new ShopGrade();
        $rs = $model->insertInfo($data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 订单售后
     * @return array
     */
    public function actionAfterSales()
    {
        $uid = RequestHelper::post('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $data['uid']    = $uid;
        $data['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($data['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($data['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $data['product_id'] = RequestHelper::post('product_id', '', '');
        if (empty($data['product_id'])) {
            $this->returnJsonMsg('808', [], Common::C('code', '808'));
        }
        $data['shop_id'] = RequestHelper::post('shop_id', '', '');
        if (empty($data['shop_id'])) {
            $this->returnJsonMsg('803', [], Common::C('code', '803'));
        }
        $data['order_sn'] = RequestHelper::post('order_sn', '', '');
        if (empty($data['order_sn'])) {
            $this->returnJsonMsg('805', [], Common::C('code', '805'));
        }
        $data['type'] = RequestHelper::post('type', '', '');
        if (empty($data['type'])) {
            $this->returnJsonMsg('810', [], Common::C('code', '810'));
        }
        $data['product_name'] = RequestHelper::post('product_name', '', '');
        if (empty($data['product_name'])) {
            $this->returnJsonMsg('809', [], Common::C('code', '809'));
        }
        $data['product_img'] = RequestHelper::post('product_img', '', '');
        $data['number']      = RequestHelper::post('number', '1', '');
        $data['price']       = RequestHelper::post('price', '0', '');
        if (empty($data['price'])) {
            $this->returnJsonMsg('811', [], Common::C('code', '811'));
        }
        $data['remark']      = RequestHelper::post('remark', '', '');
        $data['apply_time']  = date('Y-m-d H:i:s', time());
        $model = new Exchange();
        //@todo 判断数据库中是否存在相同商品的退换货单
        $where['shop_id']    = $data['shop_id'];
        $where['order_sn']   = $data['order_sn'];
        $where['uid']        = $data['uid'];
        $where['mobile']     = $data['mobile'];
        $where['product_id'] = $data['product_id'];
        $info = $model->getInfo($where, true, 'id,number');
        $have_number = 0;
        if (!empty($info)) {
            $have_number += $info['number'];
        }
        //@todo 退换货数量的限制 与 订单表的数量进行比较
        $number = $data['number'] + $have_number;
        $order_model = new OrderDetail();
        $order_d_where['order_sn']   = $data['order_sn'];
        $order_d_where['product_id'] = $data['product_id'];
        $order_detail_info = $order_model->getInfo($order_d_where, true, 'num');
        if (empty($order_detail_info)) {
            $this->returnJsonMsg('818', [], Common::C('code', '818'));
        }
        if ($number > $order_detail_info['num']) {
            $this->returnJsonMsg('819', [], Common::C('code', '819'));
        }
        $rs = $model->insertInfo($data);
        //更新订单详情表当前商品的退货数量
        $order_detail_model = new OrderDetail();
        $order_detail_where['order_sn']   = $data['order_sn'];
        $order_detail_where['mobile']     = $data['mobile'];
        $order_detail_where['product_id'] = $data['product_id'];
        $order_detail_update['is_exchange'] = '1';
        $order_detail_update['retread_num'] = $data['number'];
        $order_detail_model->updateInfo($order_detail_update, $order_detail_where);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 分享发福利(红包)
     * @return array
     */
    public function actionShareWelfare()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $type = RequestHelper::post('type', '', '');
        if (empty($type)) {
            $this->returnJsonMsg('813', [], Common::C('code', '813'));
        }
        $order_sn = RequestHelper::post('order_sn', '', '');
        if (empty($order_sn)) {
            $this->returnJsonMsg('805', [], Common::C('code', '805'));
        }
        $send_config_model = new OrdersSendCoupons();
        $send_config_where['status'] = '1';
        $send_config_fields = 'num,min,max,validity';
        $rs = $send_config_model->getInfo($send_config_where, true, $send_config_fields);
        if (empty($rs)) {
            $this->returnJsonMsg('814', [], Common::C('code', '814'));
        }
        $type_name = '';
        if ($type == '1') {
            $type_name = $order_sn.'_pay';
        } elseif ($type == '2') {
            $type_name = $order_sn.'_evaluation';
        }
        if (empty($type_name)) {
            $this->returnJsonMsg('813', [], Common::C('code', '813'));
        }
        $coupons_type_model = new CouponsType();
        $coupons_type_where['type_name'] = $type_name;
        $coupons_type_fields = 'type_id';
        $coupons_type_rs = $coupons_type_model->getInfo($coupons_type_where, true, $coupons_type_fields);
        if (!empty($coupons_type_rs)) {
            $this->returnJsonMsg('815', [], Common::C('code', '815'));
        }
        $data['type_name']  = $type_name;
        $data['send_type']  = '2';
        $data['number']     = $rs['num'];
        $data['add_time']   = date('Y-m-d H:i:s', time());
        $data['use_system'] = '2';
        $data['only_sign']  = md5(time().mt_rand(1000, 9999));
        $rs = $coupons_type_model->insertInfo($data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $res['url']   = Common::C('hongBaoHost').$data['only_sign'];
        $res['img']   = Common::C('hongBaoShareImg');
        $res['title'] = Common::C('hongBaoShareTitle');
        $res['text']  = Common::C('hongBaoShareText');
        $this->returnJsonMsg('200', $res, Common::C('code', '200'));
    }
    /**
     * 获取订单的商品信息
     * @param string $mobile   手机号
     * @param string $order_sn 订单号
     * @return array
     */
    private function _getOrderGoodsInfo($mobile = '', $order_sn = '')
    {
        if (empty($order_sn)) {
            $this->returnJsonMsg('805', [], Common::C('code', '805'));
        }
        $order_detail_model = new OrderDetail();
        $order_detail_where['mobile']   = $mobile;
        $order_detail_where['order_sn'] = $order_sn;
        $order_detail_fields = 'shop_id,product_id,product_name,product_img,num,price,activity_price,type,is_exchange,goods_type,retread_num,attribute_str,goods_type,remark';
        $order_detail_info = $order_detail_model->getList($order_detail_where, $order_detail_fields, 'id desc');
        $goods_total = 0;
        if (!empty($order_detail_info)) {
            foreach ($order_detail_info as $k => $v) {
                $order_detail_info[$k]['product_img'] = $this->_formatImg($v['product_img']);
                $goods_total += $v['price'] * $v['num'];
            }
        }
        $rs['goods_total'] = $goods_total;
        $rs['order_detail_info'] = $order_detail_info;
        return $rs;
    }

    /**
     * 获取商家信息
     * @param int $shop_id 商家ID
     * @return array
     */
    private function _getShopInfo($shop_id = 0)
    {
        if (empty($shop_id)) {
            $this->returnJsonMsg('803', [], Common::C('code', '803'));
        }
        $shop_model = new Shop();
        $shop_where['id'] = $shop_id;
        $shop_fields = 'contact_name,mobile';
        $rs = $shop_model->getInfo($shop_where, true, $shop_fields);
        return $rs;
    }

    /**
     * 获取支付信息
     * @param int $pay_type_id 支付方式ID
     * @return array
     */
    private  function _getPayInfo($pay_type_id = 0)
    {
        if (empty($pay_type_id)) {
            $this->returnJsonMsg('812', [], Common::C('code', '812'));
        }
        $pay_site_model = new PaySite();
        $pay_site_where['id'] = $pay_type_id;
        $pay_site_fields = 'name';
        $rs = $pay_site_model->getInfo($pay_site_where, true, $pay_site_fields);
        return $rs;
    }
    /**
     * 格式化图片
     * @param string $img 图片
     * @return array
     */
    private function _formatImg($img = '')
    {
        $img_data = [];
        if (!empty($img)) {
            $img_arr = @explode(",", $img);
            foreach ($img_arr as $key => $value) {
                if (!empty($value)) {
                    if (!strstr($value, 'http')) {
                        $img_data[]= Common::C('imgHost').$value;
                    }
                }
            }
        }
        return $img_data;
    }

    /**
     * 获取商品图片
     * @param array $where 条件
     * @return array
     */
    private function _getProductImg($where = [])
    {
        $model = new OrderDetail();
        $info = $model->getInfo($where, true, 'product_img');
        if (!empty($info) && !empty($info['product_img'])) {
            return $this->_formatImg($info['product_img']);
        }
        return [];
    }
}
