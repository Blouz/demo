<?php
/**
 * 红包
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   HongBao
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/06
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */

namespace frontend\controllers;

use yii\web\Controller;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500m\CouponsType;
use frontend\models\i500_social\User;
use frontend\models\i500m\OrdersSendCoupons;
use frontend\models\i500_social\UserCoupons;

/**
 * HongBao
 *
 * @category Social
 * @package  HongBao
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class HongBaoController extends Controller
{
    /**
     * 红包首页
     * @return string
     */
    public function actionIndex()
    {
        $sign = RequestHelper::get('sign', '', '');
        if (empty($sign)) {
            exit;
        }
        $coupons_type_model = new CouponsType();
        $coupons_type_where['only_sign'] = $sign;
        $coupons_type_fields = 'type_id';
        $coupons_type_info = $coupons_type_model->getInfo($coupons_type_where, true, $coupons_type_fields);
        if (empty($coupons_type_info)) {
            exit;
        }
        return $this->render('index', ['sign'=>$sign]);
    }

    /**
     * 领取成功
     * @return string
     */
    public function actionSuccess()
    {
        $sign = RequestHelper::get('sign', '', '');
        if (empty($sign)) {
            exit;
        }
        $mobile = RequestHelper::get('mobile', '', '');
        if (empty($mobile)) {
            exit;
        }
        if (!Common::validateMobile($mobile)) {
            exit;
        }
        $coupons_type_model = new CouponsType();
        $coupons_type_where['only_sign'] = $sign;
        $coupons_type_fields = 'type_id';
        $coupons_type_info = $coupons_type_model->getInfo($coupons_type_where, true, $coupons_type_fields);
        if (empty($coupons_type_info)) {
            exit;
        }
        /**验证当前用户是否已经领取**/
        $user_coupons_model = new UserCoupons();
        $user_coupons_where['coupon_type_id'] = $coupons_type_info['type_id'];
        $user_coupons_where['mobile']         = $mobile;
        $user_coupons_fields = 'id,par_value';
        $user_coupons_info = $user_coupons_model->getInfo($user_coupons_where, true, $user_coupons_fields);
        if (empty($user_coupons_info)) {
            $this->redirect('/hongbao?sign='.$sign);
        }
        $res['mobile'] = $mobile;
        $res['money']  = $user_coupons_info['par_value'];
        return $this->render('success', ['data'=>$res]);
    }

    /**
     * 获取红包
     * @return string
     */
    public function actionGetHongbao()
    {
        $sign = RequestHelper::post('sign', '', '');
        if (empty($sign)) {
            die(json_encode(['code' => 'no','msg' => '缺少参数 Sign']));
        }
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            die(json_encode(['code' => 'no','msg' => '缺少参数 Mobile']));
        }
        if (!Common::validateMobile($mobile)) {
            die(json_encode(['code' => 'no','msg' => '手机号格式不正确']));
        }
        $coupons_type_model = new CouponsType();
        $coupons_type_where['only_sign'] = $sign;
        $coupons_type_fields = 'type_id,type_name,number';
        $coupons_type_info = $coupons_type_model->getInfo($coupons_type_where, true, $coupons_type_fields);
        if (empty($coupons_type_info)) {
            die(json_encode(['code' => 'no','msg' => '优惠券不存在']));
        }
        $user_model = new User();
        $user_where['mobile'] = $mobile;
        $user_fields = 'id';
        $user_info = $user_model->getInfo($user_where, true, $user_fields);
        if (empty($user_info)) {
            /**用户不存在 创建新用户**/
            $user_add_data['mobile'] = $mobile;
            $user_add_data['salt']   = Common::getRandomNumber();
            $user_add_rs = $user_model->insertInfo($user_add_data);
            if (!$user_add_rs) {
                die(json_encode(['code' => 'no','msg' => '101服务器繁忙~']));
            }
        }
        /**用户存在**/
        $send_config_model = new OrdersSendCoupons();
        $send_config_where['status'] = '1';
        $send_config_fields = 'num,min,max,validity';
        $rs = $send_config_model->getInfo($send_config_where, true, $send_config_fields);
        if (empty($rs)) {
            die(json_encode(['code' => 'no','msg' => '没有获取到发优惠券的规则']));
        }
        /**验证当前用户是否已经领取**/
        $user_coupons_model = new UserCoupons();
        $user_coupons_where['coupon_type_id'] = $coupons_type_info['type_id'];
        $user_coupons_where['mobile']         = $mobile;
        $user_coupons_info = $user_coupons_model->getInfo($user_coupons_where, true, 'id');
        if (!empty($user_coupons_info)) {
            die(json_encode(['code' => 'have','msg' => '已经领取过该优惠券']));
        }
        $user_coupons_count = $user_coupons_model->getCount(['coupon_type_id'=>$coupons_type_info['type_id']]);
        if ($user_coupons_count < $rs['num']) {
            $data['mobile'] = $mobile;
            $data['coupon_type_id'] = $coupons_type_info['type_id'];
            $data['serial_number']  = time() . '1' . rand(10000, 99999);
            $data['type_name']      = '分享红包';
            $data['par_value']      = mt_rand($rs['min'], $rs['max']);
            $data['get_time']       = date('Y-m-d H:i:s', time());
            $data['expired_time']   = date('Y-m-d H:i:s', time()+$rs['validity']*24*60*60);
            $data['is_geted']       = '1';
            $order_sn_arr = explode("_", $coupons_type_info['type_name']);
            $data['from_order_sn']  = $order_sn_arr[0];
            $rs = $user_coupons_model->insertInfo($data);
            if (!$rs) {
                die(json_encode(['code' => 'no','msg' => '102服务器繁忙~']));
            }
            die(json_encode(['code' => 'ok','msg' => '']));
        } else {
            die(json_encode(['code' => 'no','msg' => '您来晚啦，已全部领取完毕~']));
        }
    }
}
