<?php
/**
 * 服务订单
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Service
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/9/20
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v1\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\ServiceTime;
use frontend\models\i500_social\ServiceOrder;
use frontend\models\i500_social\Order;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\ServiceUnit;
use frontend\models\i500_social\ServiceSetting;
use frontend\models\i500_social\ServiceOrderEvaluation;

/**
 * Service time
 *
 * @category Social
 * @package  Servicetime
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class ServiceorderController extends BaseController
{
    /**
     * Before
     * @param \yii\base\Action $action Action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    /**
     * 预约
     * @return array
     */
    public function actionAdd()
    {
        $data['uid'] = RequestHelper::post('uid', '', '');
        if (empty($data['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $data['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($data['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($data['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $data['service_id'] = RequestHelper::post('service_id', '', '');
        if (empty($data['service_id'])) {
            $this->returnJsonMsg('1010', [], Common::C('code', '1010'));
        }
        $data['appointment_service_time'] = RequestHelper::post('appointment_service_time', '', '');
//        if (empty($data['appointment_service_time'])) {
//            $this->returnJsonMsg('1031', [], Common::C('code', '1031'));
//        }
        $data['appointment_service_address'] = RequestHelper::post('appointment_service_address', '', '');
        if (empty($data['appointment_service_address'])) {
            $this->returnJsonMsg('1032', [], Common::C('code', '1032'));
        }
        $data['source_type'] = RequestHelper::post('source_type', '', '');
        if (empty($data['source_type'])) {
            $this->returnJsonMsg('1033', [], Common::C('code', '1033'));
        }
        $data['remark'] = RequestHelper::post('remark', '', '');
        $data['community_id'] = RequestHelper::post('community_id', '0', 'intval');
        if (empty($data['community_id'])) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        $data['community_city_id'] = RequestHelper::post('community_city_id', '0', 'intval');
        if (empty($data['community_city_id'])) {
            $this->returnJsonMsg('645', [], Common::C('code', '645'));
        }
        /**获取服务信息**/
        $service_model = new Service();
        $service_where['id']                   = $data['service_id'];
        $service_where['audit_status']         = '2';
        $service_where['status']               = '1';
        $service_where['user_auth_status']     = '1';
        $service_where['servicer_info_status'] = '1';
        $service_where['is_deleted']           = '2';
        $service_fields = 'uid,mobile,image,title,price,unit,service_way,description';
        $service_info = $service_model->getInfo($service_where, true, $service_fields);
        if (empty($service_info)) {
            $this->returnJsonMsg('1011', [], Common::C('code', '1011'));
        }
        if ($data['uid'] == $service_info['uid']) {
            $this->returnJsonMsg('1045', [], Common::C('code', '1045'));
        }
        $order_model = new Order();
        //@todo 确定创建订单号为什么用省份？35=全国
        $data['order_sn']                 = $order_model->createSn('35', $data['mobile']);
        if (empty($data['order_sn'])) {
            $this->returnJsonMsg('1053', [], Common::C('code', '1053'));
        }
        $data['service_uid']              = $service_info['uid'];
        $data['service_mobile']           = $service_info['mobile'];
        $data['service_way']              = $service_info['service_way'];
        $data['service_info_title']       = $service_info['title'];
        $data['service_info_image']       = $service_info['image'];
        $data['service_info_price']       = $service_info['price'];
        $data['service_info_unit']        = $service_info['unit'];
        $data['service_info_description'] = $service_info['description'];
        $data['total']                    = $service_info['price'];
        $day  = date('Y-m-d', strtotime($data['appointment_service_time']));
        $hour = date('H', strtotime($data['appointment_service_time']));
        $service_time_model = new ServiceTime();
        if (!$service_time_model->checkTimeStatus($service_info['uid'], $day, $hour)) {
            $this->returnJsonMsg('1036', [], Common::C('code', '1036'));
        }
        $service_order_model = new ServiceOrder();
        $rs = $service_order_model->insertInfo($data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        //@todo 预约成功后，当前预约数量+1
        if (!empty($day) && !empty($hour)) {
            $time_status = $service_time_model->updateTimeStatus($service_info['uid'], $day, $hour);
        }
        $rs_info['order_sn'] = $data['order_sn'];
        //@todo 应该用事务 判断这两个逻辑。
        $this->returnJsonMsg('200', $rs_info, Common::C('code', '200'));
    }

    /**
     * 预约列表
     * @return array
     */
    public function actionList()
    {
        $where = [];
        $type = RequestHelper::get('type', '0', 'intval');
        if (empty($type)) {
            $this->returnJsonMsg('1037', [], Common::C('code', '1037'));
        }
        if ($type !='1' && $type !='2') {
            $this->returnJsonMsg('1014', [], Common::C('code', '1014'));
        }
        $order_status = RequestHelper::get('order_status', '0', 'intval');
        if (empty($order_status)) {
            $this->returnJsonMsg('1046', [], Common::C('code', '1046'));
        }
        $and_where = '';
        if ($type == '1') {
            /**我预约的服务**/
            $where['uid'] = RequestHelper::get('uid', '', '');
            if (empty($where['uid'])) {
                $this->returnJsonMsg('621', [], Common::C('code', '621'));
            }
            $where['mobile'] = RequestHelper::get('mobile', '', '');
            if (empty($where['mobile'])) {
                $this->returnJsonMsg('604', [], Common::C('code', '604'));
            }
            if (!Common::validateMobile($where['mobile'])) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
            $fields = 'service_info_title,service_mobile as mobile,appointment_service_time,appointment_service_address,status,pay_status,order_sn,total,service_id,service_info_image';
            $data = $this->_getStatus($type, $order_status);
            if (!empty($data['and_where'])) {
                $and_where = $data['and_where'];
            }
            if (!empty($data['where'])) {
                if (!empty($data['where']['status'])) {
                    $where['status']     = $data['where']['status'];
                }
                if (!empty($data['where']['pay_status'])) {
                    $where['pay_status'] = $data['where']['pay_status'];
                }
                if (isset($data['where']['user_evaluation_status'])) {
                    $where['user_evaluation_status'] = $data['where']['user_evaluation_status'];
                }
                if (isset($data['where']['servicer_evaluation_status'])) {
                    $where['servicer_evaluation_status'] = $data['where']['servicer_evaluation_status'];
                }
            }
        } else {
            /**别人预约我的服务**/
            $where['service_uid'] = RequestHelper::get('uid', '', '');
            if (empty($where['service_uid'])) {
                $this->returnJsonMsg('621', [], Common::C('code', '621'));
            }
            $where['service_mobile'] = RequestHelper::get('mobile', '', '');
            if (empty($where['service_mobile'])) {
                $this->returnJsonMsg('604', [], Common::C('code', '604'));
            }
            if (!Common::validateMobile($where['service_mobile'])) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
            $fields = 'service_info_title,mobile,appointment_service_time,appointment_service_address,status,pay_status,order_sn,total,service_id,service_info_image';
            $data = $this->_getStatus($type, $order_status);
            if (!empty($data['and_where'])) {
                $and_where = $data['and_where'];
            }
            if (!empty($data['where'])) {
                if (!empty($data['where']['status'])) {
                    $where['status']     = $data['where']['status'];
                }
                if (!empty($data['where']['pay_status'])) {
                    $where['pay_status'] = $data['where']['pay_status'];
                }
                if (isset($data['where']['user_evaluation_status'])) {
                    $where['user_evaluation_status'] = $data['where']['user_evaluation_status'];
                }
                if (isset($data['where']['servicer_evaluation_status'])) {
                    $where['servicer_evaluation_status'] = $data['where']['servicer_evaluation_status'];
                }
            }
        }
        $page      = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '6', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        $service_order_model = new ServiceOrder();
        $list = $service_order_model->getPageList($where, $fields, 'id desc', $page, $page_size, $and_where);
        if (empty($list)) {
            $this->returnJsonMsg('1034', [], Common::C('code', '1034'));
        }
        $rs_info = [];
        foreach ($list as $k => $v) {
            $rs_info[$k]['day']                = date('Y-m-d', strtotime($v['appointment_service_time']));
            $rs_info[$k]['week']               = "周".Common::getWeek($rs_info[$k]['day']);
            $rs_info[$k]['hour']               = date('H', strtotime($v['appointment_service_time']));
            $rs_info[$k]['title']              = $v['service_info_title'];
            $rs_info[$k]['mobile']             = $v['mobile'];
            $rs_info[$k]['name']               = $this->_getUserInfo($v['mobile'], 'nickname');
            $rs_info[$k]['address']            = $v['appointment_service_address'];
            $rs_info[$k]['status']             = $v['status'];
            $rs_info[$k]['pay_status']         = $v['pay_status'];
            $rs_info[$k]['order_sn']           = $v['order_sn'];
            $rs_info[$k]['total']              = $v['total'];
            $rs_info[$k]['service_id']         = $v['service_id'];
            $rs_info[$k]['service_info_image'] = $this->_formatImg($v['service_info_image']);
        }
        $this->returnJsonMsg('200', $rs_info, Common::C('code', '200'));
    }

    /**
     * 订单详情
     * @return array
     */
    public function actionDetail()
    {
        $type = RequestHelper::get('type', '0', 'intval');  //1=体验方 2=服务方
        if (empty($type)) {
            $this->returnJsonMsg('1008', [], Common::C('code', '1008'));
        }
        if ($type !='1' && $type !='2') {
            $this->returnJsonMsg('1014', [], Common::C('code', '1014'));
        }
        if ($type == '1') {
            $where['uid'] = RequestHelper::get('uid', '', '');
            if (empty($where['uid'])) {
                $this->returnJsonMsg('621', [], Common::C('code', '621'));
            }
            $where['mobile'] = RequestHelper::get('mobile', '', '');
            if (empty($where['mobile'])) {
                $this->returnJsonMsg('604', [], Common::C('code', '604'));
            }
            if (!Common::validateMobile($where['mobile'])) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
            $fields = 'service_id,service_mobile,service_way,total,service_info_title,service_info_price,service_info_image,service_info_unit,appointment_service_time,appointment_service_address,remark,status,pay_status,user_evaluation_status,servicer_evaluation_status';
        } else {
            $where['service_uid'] = RequestHelper::get('uid', '', '');
            if (empty($where['service_uid'])) {
                $this->returnJsonMsg('621', [], Common::C('code', '621'));
            }
            $where['service_mobile'] = RequestHelper::get('mobile', '', '');
            if (empty($where['service_mobile'])) {
                $this->returnJsonMsg('604', [], Common::C('code', '604'));
            }
            if (!Common::validateMobile($where['service_mobile'])) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
            $fields = 'service_id,mobile,service_way,total,service_info_title,service_info_price,service_info_image,service_info_unit,appointment_service_time,appointment_service_address,remark,status,pay_status,user_evaluation_status,servicer_evaluation_status';
        }
        $where['order_sn'] = RequestHelper::get('order_sn', '', '');
        if (empty($where['order_sn'])) {
            $this->returnJsonMsg('1042', [], Common::C('code', '1042'));
        }
        $service_order_model = new ServiceOrder();
        $info = $service_order_model->getInfo($where, true, $fields);
        if (empty($info)) {
            $this->returnJsonMsg('1043', [], Common::C('code', '1043'));
        }
        $info['service_info_price'] = $info['service_info_price'].$this->_getServiceUnit($info['service_info_unit']);
        if (!empty($info['service_info_image'])) {
            $info['service_info_image'] = Common::C('imgHost').$info['service_info_image'];
        }
        if ($type == '1') {
            $info['contact'] = $this->_getUserInfo($info['service_mobile'], 'realname');
            $info['contact_mobile'] = $info['service_mobile'];
            unset($info['service_mobile']);
        } else {
            $info['contact'] = $this->_getUserInfo($info['mobile'], 'realname');
            $info['contact_mobile'] = $info['mobile'];
            unset($info['mobile']);
        }
        unset($info['service_info_unit']);
        $this->returnJsonMsg('200', $info, Common::C('code', '200'));
    }

    /**
     * 开始服务 - 服务方调用
     * @return array
     */
    public function actionStartService()
    {
        $where['service_uid'] = RequestHelper::post('uid', '', '');
        if (empty($where['service_uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['service_mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($where['service_mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['service_mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['order_sn'] = RequestHelper::post('order_sn', '', '');
        if (empty($where['order_sn'])) {
            $this->returnJsonMsg('1042', [], Common::C('code', '1042'));
        }
        $order_model = new ServiceOrder();
        $info = $order_model->getInfo($where, true, 'status,pay_status');
        if (empty($info)) {
            $this->returnJsonMsg('1043', [], Common::C('code', '1043'));
        }
        if ($info['status'] != '1' || $info['pay_status'] != '1') {
            $this->returnJsonMsg('1048', [], Common::C('code', '1048'));
        }
        $update_data['status'] = '3';
        $update_data['start_time'] = date("Y-m-d H:i:s", time());
        $rs = $order_model->updateInfo($update_data, $where);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 确认服务 - 服务方调用
     * @return array
     */
    public function actionConfirmService()
    {
        $where['service_uid'] = RequestHelper::post('uid', '', '');
        if (empty($where['service_uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['service_mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($where['service_mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['service_mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['order_sn'] = RequestHelper::post('order_sn', '', '');
        if (empty($where['order_sn'])) {
            $this->returnJsonMsg('1042', [], Common::C('code', '1042'));
        }
        $order_model = new ServiceOrder();
        $info = $order_model->getInfo($where, true, 'status,pay_status');
        if (empty($info)) {
            $this->returnJsonMsg('1043', [], Common::C('code', '1043'));
        }
        if ($info['status'] != '0' || $info['pay_status'] != '1') {
            $this->returnJsonMsg('1049', [], Common::C('code', '1049'));
        }
        $update_data['status'] = '1';
        $update_data['confirm_time'] = date("Y-m-d H:i:s", time());
        $rs = $order_model->updateInfo($update_data, $where);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 完成服务
     * @return array
     */
    public function actionCompleteService()
    {
        $type = RequestHelper::post('type', '0', 'intval');  //1=体验方 2=服务方
        if (empty($type)) {
            $this->returnJsonMsg('1008', [], Common::C('code', '1008'));
        }
        if ($type !='1' && $type !='2') {
            $this->returnJsonMsg('1014', [], Common::C('code', '1014'));
        }
        if ($type == '1') {
            $where['uid'] = RequestHelper::post('uid', '', '');
            if (empty($where['uid'])) {
                $this->returnJsonMsg('621', [], Common::C('code', '621'));
            }
            $where['mobile'] = RequestHelper::post('mobile', '', '');
            if (empty($where['mobile'])) {
                $this->returnJsonMsg('604', [], Common::C('code', '604'));
            }
            if (!Common::validateMobile($where['mobile'])) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        } else {
            $where['service_uid'] = RequestHelper::post('uid', '', '');
            if (empty($where['service_uid'])) {
                $this->returnJsonMsg('621', [], Common::C('code', '621'));
            }
            $where['service_mobile'] = RequestHelper::post('mobile', '', '');
            if (empty($where['service_mobile'])) {
                $this->returnJsonMsg('604', [], Common::C('code', '604'));
            }
            if (!Common::validateMobile($where['service_mobile'])) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }
        $where['order_sn'] = RequestHelper::post('order_sn', '', '');
        if (empty($where['order_sn'])) {
            $this->returnJsonMsg('1042', [], Common::C('code', '1042'));
        }
        $order_model = new ServiceOrder();
        $info = $order_model->getInfo($where, true, 'status,pay_status');
        if (empty($info)) {
            $this->returnJsonMsg('1043', [], Common::C('code', '1043'));
        }
        if (($info['status'] != '3' && $info['status'] != '4') || $info['pay_status'] != '1') {
            $this->returnJsonMsg('1051', [], Common::C('code', '1051'));
        }
        if ($type == '1') {
            $update_data['status'] = '5';
            $update_data['user_complete_time'] = date("Y-m-d H:i:s", time());
        } else {
            $update_data['status'] = '4';
            $update_data['servicer_complete_time'] = date("Y-m-d H:i:s", time());
        }
        $rs = $order_model->updateInfo($update_data, $where);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 取消服务 - 体验方调用
     * @return array
     */
    public function actionCancelService()
    {
        $where['uid'] = RequestHelper::post('uid', '', '');
        if (empty($where['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['order_sn'] = RequestHelper::post('order_sn', '', '');
        if (empty($where['order_sn'])) {
            $this->returnJsonMsg('1042', [], Common::C('code', '1042'));
        }
        $order_model = new ServiceOrder();
        $info = $order_model->getInfo($where, true, 'status,pay_status');
        if (empty($info)) {
            $this->returnJsonMsg('1043', [], Common::C('code', '1043'));
        }
        if ($info['status'] != '0' || $info['pay_status'] != '0') {
            $this->returnJsonMsg('1050', [], Common::C('code', '1050'));
        }
        $update_data['status'] = '2';
        $update_data['cancel_time'] = date("Y-m-d H:i:s", time());
        $rs = $order_model->updateInfo($update_data, $where);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 评价接口
     * @return array
     */
    public function actionEvaluation()
    {
        $where['type']     = RequestHelper::post('type', '0', 'intval');
        if (empty($where['type'])) {
            $this->returnJsonMsg('1008', [], Common::C('code', '1008'));
        }
        if ($where['type'] !='1' && $where['type'] !='2') {
            $this->returnJsonMsg('1014', [], Common::C('code', '1014'));
        }
        $where['uid'] = RequestHelper::post('uid', '', '');
        if (empty($where['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['order_sn'] = RequestHelper::post('order_sn', '', '');
        if (empty($where['order_sn'])) {
            $this->returnJsonMsg('1042', [], Common::C('code', '1042'));
        }
        $star = RequestHelper::post('star', '0', 'intval');
        $content = RequestHelper::post('content', '', '');
        $evaluation_model = new ServiceOrderEvaluation();
        $info = $evaluation_model->getInfo($where, true, 'id');
        if (!empty($info)) {
            $this->returnJsonMsg('1047', [], Common::C('code', '1047'));
        }
        $add_data = $where;
        $add_data['star']    = $star;
        $add_data['content'] = $content;
        $rs = $evaluation_model->insertInfo($add_data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        /**更新订单状态**/
        if ($where['type'] == '1') {
            /**体验方**/
            $update_data['user_evaluation_status'] = '1';
        } else {
            /**服务方**/
            $update_data['servicer_evaluation_status'] = '1';
        }
        $order_model = new ServiceOrder();
        $order_where['order_sn'] = $where['order_sn'];
        //@todo 评价成功后更新订单状态
        $order_model->updateInfo($update_data, $order_where);
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 获取设置信息
     * @param string $mobile 手机号
     * @param string $params 参数名
     * @param int    $type   表示 1=一个参数 返回一个字段值 2=多个参数 返回数组
     * @return string
     */
    private function _getSettingInfo($mobile = '', $params = '', $type = 1)
    {
        if (!empty($mobile) && !empty($params)) {
            $service_setting_model = new ServiceSetting();
            $where['mobile'] = $mobile;
            $fields = $params;
            $info = $service_setting_model->getInfo($where, true, $fields);
            if (!empty($info)) {
                if ($type == 1) {
                    return $info[$params];
                } else {
                    return $info;
                }
            }
        }
        return ($type == 1) ? '' : [] ;
    }

    /**
     * 获取用户信息
     * @param string $mobile 电话
     * @param string $param  参数
     * @return array
     */
    private function _getUserInfo($mobile = '', $param = '')
    {
        $user_base_info_model = new UserBasicInfo();
        $user_base_info_where['mobile'] = $mobile;
        $user_base_info_fields = $param;
        $rs[$param] = '';
        $rs = $user_base_info_model->getInfo($user_base_info_where, true, $user_base_info_fields);
        return $rs[$param];
    }

    /**
     * 获取服务单位
     * @param int $unit_id 单位ID
     * @return string
     */
    private function _getServiceUnit($unit_id = 0)
    {
        $unit = '';
        if (!empty($unit_id)) {
            $unit_model = new ServiceUnit();
            $unit_where['status'] = '2';
            $unit_list = $unit_model->getList($unit_where, 'id,unit', 'id asc');
            if (!empty($unit_list)) {
                foreach ($unit_list as $k => $v) {
                    if ($unit_list[$k]['id'] == $unit_id) {
                        $unit = $unit_list[$k]['unit'];
                        break;
                    }
                }
            }
        }
        return $unit;
    }


    /**
     * 获取状态
     * @param int $type   标识值
     * @param int $status 状态值
     * @return array
     */
    private function _getStatus($type = 0, $status = 0)
    {
        $data = [];
        $data['where'] = [];
        $data['and_where'] = '';
        if ($type == '1') {
            /**体验方**/
            switch ($status) {
                case 1:
                    $data['and_where'] = ['or', ['=', 'status', '0' ], ['=', 'status', '1']];
                    break;
                case 2 :
                    $data['and_where'] = ['or', ['=', 'status', '3' ], ['=', 'status', '4'], ['=', 'status', '2']];
                    $data['where']['pay_status'] = '1';
                    break;
                case 3 :
                    $data['where']['status']                 = '5';
                    $data['where']['user_evaluation_status'] = '0';
                    $data['where']['pay_status']             = '1';
                    break;
                case 4 :
                    $data['where']['user_evaluation_status'] = '1';
                    $data['where']['pay_status']             = '1';
                    break;
            }
        } else {
            /**服务方**/
            switch ($status) {
                case 1:
                    $data['and_where'] = ['or', ['=', 'status', '0' ], ['=', 'status', '1']];
                    $data['where']['pay_status'] = '1';
                    break;
                case 2 :
                    $data['and_where'] = ['or', ['=', 'status', '3' ], ['=', 'status', '4'], ['=', 'status', '2']];
                    $data['where']['pay_status'] = '1';
                    break;
                case 3 :
                    $data['where']['status']                     = '5';
                    $data['where']['servicer_evaluation_status'] = '0';
                    $data['where']['pay_status']                 = '1';
                    break;
                case 4 :
                    $data['where']['servicer_evaluation_status'] = '1';
                    $data['where']['pay_status']                 = '1';
                    break;
            }
        }
        return $data;
    }

    /**
     * 格式化图片
     * @param string $image 图片地址
     * @return string
     */
    private function _formatImg($image = '')
    {
        if (!empty($image)) {
            if (!strstr($image, 'http')) {
                return Common::C('imgHost').$image;
            }
        }
        return '';
    }
}
