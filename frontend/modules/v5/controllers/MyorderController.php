<?php
/**
 * 我的订单
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-12-10 上午9:47
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v5\controllers;

use common\libs\Account;
use frontend\controllers\RestController;
use frontend\models\i500_social\AccountDetail;
use frontend\models\i500_social\SeekOrder;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\ServiceOrderDetail;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserOrder;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\ServiceOrder;
use frontend\models\i500_social\Order;
use yii\helpers\ArrayHelper;
use yii\web\User;

/**
 * Service order
 *
 * @category Social
 * @package  Serviceorder
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class MyorderController extends RestController
{
    public static $buy_type = [
        0=>'待接单',//订单已提交 等待服务方接单
        1=>'商家接单',//商家接单 线下沟通服务时间，等待需求方确定完成
        2=>'开始服务',//进行中 todo
        3=>'已完成',//订单已完成
        4=>'拒绝接单',//订单已完成
        5=>'已取消',//订单已提交 等待服务方接单
    ];
    public static $sales_type = [
        1=>'商家已抢单',//商家接单 线下沟通服务时间，等待需求方确定完成
        2=>'开始服务',//进行中 todo
        3=>'已完成',//订单已完成
        4=>'拒绝接单',//订单已完成
        5=>'已取消',//订单已提交 等待服务方接单
    ];
    public $modelClass = 'frontend\models\i500_social\ServiceOrder';

    /**
     * 已购买的服务 需求单
     */
    public function actionBuy()
    {
        $mobile = RequestHelper::get('mobile');
        $status = RequestHelper::get('status', 0, 'intval');
        $map['mobile'] = $mobile;
        $map['status'] = $status;
        //$mobile = RequestHelper::get('mobile');
        $fields = ['id', 'order_sn', 'shop_mobile', 'total', 'pay_status', 'status', 'remark', 'create_time', 'community_city_id', 'community_id','order_info','order_type','pay_method'];
        $query = UserOrder::find()->select($fields)->where($map);
        $data = $this->getPagedRows($query,['order'=>'id desc']);
        $list = [];
        $mobile_data = [];
        if (!empty($data['item'])) {
            foreach ($data['item'] as $k => $v) {
                $mobile_data[] = $v['shop_mobile'];
            }
            //var_dump($mobile_data);exit();
            //获取分类
            $category = ServiceCategory::find()->select(['id','name', 'image'])->asArray()->all();
            $user_list = UserBasicInfo::find()->select(['mobile', 'nickname', 'avatar'])->where(['mobile'=>$mobile_data])->asArray()->all();

            $user_list = ArrayHelper::index($user_list, 'mobile');
            //var_dump($user_list);exit();
            //$category = ArrayHelper::map($category, 'id','name');
            $category = ArrayHelper::index($category, 'id');
           // var_dump($data['item']);exit();
            foreach ($data['item'] as $k => $v) {
                //$data['item'][$k]['order_info'] = json_decode($v['order_info']);
                $info[0] = [];
                $info = json_decode($v['order_info'], true);

//                    $list[$k] = $info[0];
//                    $list[$k]['category_name'] = ArrayHelper::getValue($category, $info[0]['category_id'].'.name', '');
//                    $category_image = ArrayHelper::getValue($category, $info[0]['category_id'].'.image', '');
//                    $list[$k]['category_image'] = Common::formatImg($category_image);
                $category_id = ArrayHelper::getValue($info, '0.son_category_id', 0);
                if (empty($category_id)) {
                    $category_id = ArrayHelper::getValue($info, '0.category_id', 0);
                }
                $category_image = ArrayHelper::getValue($category, $category_id.'.image', '');
                if (empty($category_image)) {
                    $category_image = ArrayHelper::getValue($category, $category_id.'.image', '');
                }
                //ArrayHelper::getValue($category, $info[0]['son_category_id'].'.name', ''),
                $category_name = ArrayHelper::getValue($category, $category_id.'.name', '');
                if (empty($category_name)) {
                    $category_name = ArrayHelper::getValue($category, $category_id.'.image', '');
                }
                $list[] = [
                    'id'=> ArrayHelper::getValue($info, '0.id'),
                    'category_id'=> ArrayHelper::getValue($info, '0.category_id'),
                    'content'=> ArrayHelper::getValue($info, '0.content'),
                    //'price'=> ArrayHelper::getValue($info, '0.price'),
                    'price'=> $v['total'],
                    //'is_receive'=> ArrayHelper::getValue($info, '0.is_receive', 0),
                    'unit'=> ArrayHelper::getValue($info, '0.unit'),
                    'image'=> ArrayHelper::getValue($info, '0.image'),
                    'category_name' => $category_name,
                    'category_image' => Common::formatImg($category_image),
//                    $list[$k]['category_image'] = Common::formatImg($category_image);
                    'community_id'=>$v['community_id'],
                    'community_city_id'=>$v['community_city_id'],
                    'order_sn'=>$v['order_sn'],
                    'status'=>$v['status'],
                    'order_type'=>$v['order_type'],
                    'pay_status'=>$v['pay_status'],
                    'shop_mobile'=>$v['shop_mobile'],
                    'create_time'=>$v['create_time'],
                    'remark'=>$v['remark'],
                    'nickname'=>ArrayHelper::getValue($user_list, $v['shop_mobile'].'.nickname', ''),
                    'avatar' => ArrayHelper::getValue($user_list, $v['shop_mobile'].'.avatar', ''),
                ];
                //var_dump($info);exit();
//                if (isset($info[0])) {
//                    $list[$k] = $info[0];
//                    $list[$k]['category_name'] = ArrayHelper::getValue($category, $info[0]['category_id'].'.name', '');
//                    $category_image = ArrayHelper::getValue($category, $info[0]['category_id'].'.image', '');
//                    $list[$k]['category_image'] = Common::formatImg($category_image);
//
//                }
//                $list[$k]['order_sn'] = $v['order_sn'];
//                $list[$k]['status'] = $v['status'];
//                $list[$k]['order_type'] = $v['order_type'];
//                $list[$k]['pay_status'] = $v['pay_status'];
//                $list[$k]['shop_mobile'] = $v['shop_mobile'];
//                $list[$k]['create_time'] = $v['create_time'];
//                $list[$k]['community_id'] = $v['community_id'];
//                $list[$k]['community_city_id'] = $v['community_city_id'];
//                $list[$k]['nickname'] = ArrayHelper::getValue($user_list, $v['shop_mobile'].'.nickname', '');
//                $list[$k]['avatar'] = ArrayHelper::getValue($user_list, $v['shop_mobile'].'.avatar', '');
            }
        }
        $data['item'] = $list;
        $this->result['data'] = $data;
        return $this->result;
        //var_dump($data);exit();
    }

    /**
     * 已出售的服务
     */
    public function actionSales()
    {
        $mobile = RequestHelper::get('mobile');
        //$mobile = RequestHelper::get('mobile');
        //$status = RequestHelper::get('status', 0, 'intval');
        $map['shop_mobile'] = $mobile;
//        $map['status'] = [1,2,3];
       // $map['status'] = $status;
        $fields = ['id', 'mobile', 'order_sn', 'total', 'pay_status', 'status', 'remark', 'create_time', 'community_city_id', 'community_id','order_info','order_type','pay_method'];
        $query = UserOrder::find()->select($fields)->where($map);
        //$data = $this->getPagedRows($query);
        $data = $this->getPagedRows($query,['order'=>'id desc']);
        $list = [];
        if (!empty($data['item'])) {
            $mobile_data = [];
            foreach ($data['item'] as $k => $v) {
                $mobile_data[] = $v['mobile'];
            }
            $user_list = UserBasicInfo::find()->select(['mobile', 'nickname', 'avatar'])->where(['mobile'=>$mobile_data])->asArray()->all();

            $user_list = ArrayHelper::index($user_list, 'mobile');
            //获取分类
            $category = ServiceCategory::find()->select(['id','name','image'])->asArray()->all();
            $category = ArrayHelper::index($category, 'id');
            $list = [];
            foreach ($data['item'] as $k => $v) {
                $info = json_decode($v['order_info'], true);
                //var_dump($info);exit();
//                if (isset($info[0])) {
//                    $list[$k] = $info[0];
//                    $list[$k]['category_name'] = ArrayHelper::getValue($category, $info[0]['category_id'].'.name', '');
//                    $category_image = ArrayHelper::getValue($category, $info[0]['category_id'].'.image', '');
//                    $list[$k]['category_image'] = Common::formatImg($category_image);
//
//                }
//                $list[$k]['order_sn'] = $v['order_sn'];
//                $list[$k]['status'] = $v['status'];
//                $list[$k]['pay_status'] = $v['pay_status'];
//                $list[$k]['order_type'] = $v['order_type'];
//                $list[$k]['mobile'] = $v['mobile'];
//                $list[$k]['create_time'] = $v['create_time'];
////                foreach ($info as $key=>$val) {
////                    $info[$key]['category_name'] = ArrayHelper::getValue($category, $val['category_id'], '');
////                }
//                //$data['item'][$k]['order_info'] = $info;
//                $list[$k]['nickname'] = ArrayHelper::getValue($user_list, $v['mobile'].'.nickname', '');
//                $list[$k]['avatar'] = ArrayHelper::getValue($user_list, $v['mobile'].'.avatar', '');
                //$category_id = isset($info[0]['son_category_id']) ? $info[0]['son_category_id']: $info[0]['category_id'];
                $category_id = ArrayHelper::getValue($info, '0.son_category_id', 0);
                if (empty($category_id)) {
                    $category_id = ArrayHelper::getValue($info, '0.category_id', 0);
                }
                $category_image = ArrayHelper::getValue($category, $category_id.'.image', '');
                if (empty($category_image)) {
                    $category_image = ArrayHelper::getValue($category, $category_id.'.image', '');
                }
                //ArrayHelper::getValue($category, $info[0]['son_category_id'].'.name', ''),
                $category_name = ArrayHelper::getValue($category, $category_id.'.name', '');
                if (empty($category_name)) {
                    $category_name = ArrayHelper::getValue($category, $category_id.'.image', '');
                }
                $list[] = [
                    'id'=> ArrayHelper::getValue($info, '0.id'),
                    'category_id'=> ArrayHelper::getValue($info, '0.category_id'),
                    'content'=> ArrayHelper::getValue($info, '0.content'),
                    //'price'=> ArrayHelper::getValue($info, '0.price'),
                    'price'=> $v['total'],
                    'unit'=> ArrayHelper::getValue($info, '0.unit'),
                    'image'=> ArrayHelper::getValue($info, '0.image'),
                    'category_name' => $category_name,
                    'category_image' => Common::formatImg($category_image),
//                    $list[$k]['category_image'] = Common::formatImg($category_image);
                    'community_id'=>$v['community_id'],
                    'community_city_id'=>$v['community_city_id'],
                    'order_sn'=>$v['order_sn'],
                    'status'=>$v['status'],
                    'remark'=>$v['remark'],
                    'order_type'=>$v['order_type'],
                    'pay_status'=>$v['pay_status'],
                    'shop_mobile'=>$v['mobile'],
                    'create_time'=>$v['create_time'],
                    'nickname'=>ArrayHelper::getValue($user_list, $v['mobile'].'.nickname', ''),
                    'avatar' => ArrayHelper::getValue($user_list, $v['mobile'].'.avatar', ''),
                ];
            }
        }
        $data['item'] = $list;
        $this->result['data'] = $data;
        return $this->result;
        //var_dump($data);exit();
    }
    /**
     * 服务方接单 拒绝 - 服务方调用
     * @return array
     */
    public function actionConfirm()
    {
        $shop_mobile = RequestHelper::post('mobile', '', '');
        $order_sn = RequestHelper::post('order_sn', '', '');
        $type = RequestHelper::post('type', 0, 'intval');
        $dev = RequestHelper::post('dev', '');

        if (!in_array($type, [1,2])) {
            $this->result['code'] = 422;
            $this->result['message'] = '必须为1或2';
            return $this->result;
        }
        if (empty($shop_mobile)) {
            $this->result['code'] = 422;
            $this->result['message'] = '无效的手机号';
        } else if (!Common::validateMobile($shop_mobile)) {
            $this->result['code'] = 422;
            $this->result['message'] = '无效的手机号';
            //$this->returnJsonMsg('605', [], Common::C('code', '605'));
        } else if (empty($order_sn)) {
            $this->result['code'] = 422;
            $this->result['message'] ='无效的订单号';
            //$this->returnJsonMsg('1042', [], Common::C('code', '1042'));
        } else {
            $map['shop_mobile'] = $shop_mobile;
            $map['order_sn'] = $order_sn;
            $model = UserOrder::findOne($map);
            //$info = $order_model->getInfo($where, true, 'status,pay_status');
            if (empty($model)) {
                $this->result['code'] = 404;
                $this->result['message'] = '订单不存在';
            } else if ($model->pay_status != '1') {
                $this->result['code'] = 422;
                $this->result['message'] = '请等待需求方支付';
            } else {
                if ($type == 1) {
                    $model->status = 1;
                } else if($type == 2) {
                    $model->status = 4;
                }
                //$model->status = 1;
                $model->operation_time = date("Y-m-d H:i:s", time());;
                $rs = $model->save(false);
                if (!$rs) {
                    $this->result['code'] = 500;
                    $this->result['message'] = '网络繁忙';
                }
                //$this->returnJsonMsg('200', [], Common::C('code', '200'));
            }
        }
        return $this->response();

    }
    /**
     * 确定完成 需求方调用
     * order_sn   mobile
     */
    public function actionComplete()
    {
        $mobile = RequestHelper::post('mobile', '', '');//需求方手机号
        $order_sn = RequestHelper::post('order_sn', '', '');
        if (empty($mobile) || !Common::validateMobile($mobile)) {
            $this->result['code'] = 422;
            $this->result['message'] = '无效的手机号';
        } else if (empty($order_sn)) {
            $this->result['code'] = 422;
            $this->result['message'] = '无效的订单号';
        } else {
            $data['mobile'] = $mobile;
            $data['order_sn'] = $order_sn;
            $account = new Account($data);
            $re = $account->complete();
            if ($re == false) {
                $this->result['code'] = 422;
                $this->result['message'] = $account->error;
            }
        }
        return $this->response();
    }

    /**
     * 需求方取消订单  只有需求方可以调用
     * @return array
     */
    public function actionCancel()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        $order_sn = RequestHelper::post('order_sn', '', '');
        if (empty($mobile)) {
            $this->result['code'] = 422;
            $this->result['message'] = '无效的手机号';
        } else if (empty($order_sn)) {
            $this->result['code'] = 422;
            $this->result['message'] = '无效的订单号';
        } else {

//            $data['mobile'] = $mobile;
//            $data['order_sn'] = $order_sn;
//            $account = new Account($data);
//            $re = $account->cancel();
//            if ($re == false) {
//                $this->result['code'] = 422;
//                $this->result['message'] = $account->error;
//            }

            $map['mobile'] = $mobile;
            $map['order_sn'] = $order_sn;
            $model = UserOrder::findOne($map);
            //$info = $order_model->getInfo($where, true, 'status,pay_status');
            if (empty($model)) {
                $this->result['code'] = 404;
                $this->result['message'] = '订单不存在';
            } else if ($model->pay_status == 1 || in_array($model->status, [2,3])) {
                $this->result['code'] = 422;
                $this->result['message'] = '暂无法取消订单';
            } else {
                $model->status = 3;
                $model->operation_time = date("Y-m-d H:i:s", time());;
                $rs = $model->save(false);
                if (!$rs) {
                    $this->result['code'] = 500;
                    $this->result['message'] = '网络繁忙';
                }
            }
        }
        return $this->response();

    }

}