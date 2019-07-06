<?php
/**
 * 服务时间设置
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Service
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/9/18
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

/**
 * Service time
 *
 * @category Social
 * @package  Servicetime
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class ServicetimeController extends BaseController
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
     * 获取时间
     * @return array
     */
    public function actionGetTime()
    {
        $type = RequestHelper::get('type', '', '');
        if ($type == '1') {
            /**服务详情中获取服务时间**/
            $service_id = RequestHelper::get('service_id', '', '');
            if (empty($service_id)) {
                $this->returnJsonMsg('1010', [], Common::C('code', '1010'));
            }
            $service_model = new Service();
            $service_where['id']               = $service_id;
            $service_where['audit_status']     = '2';
            $service_where['status']           = '1';
            $service_where['user_auth_status'] = '1';
            $service_where['is_deleted']       = '2';
            $service_info = $service_model->getInfo($service_where, true, 'uid,mobile');
            if (empty($service_info)) {
                $this->returnJsonMsg('1011', [], Common::C('code', '1011'));
            }
            $where['uid']    = $service_info['uid'];
            $where['mobile'] = $service_info['mobile'];
        } elseif ($type == '2') {
            /**商家自己设置服务时间**/
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
        } else {
            $this->returnJsonMsg('1014', [], Common::C('code', '1014'));
        }
        $where['day'] = RequestHelper::get('day', '', '');
        if (empty($where['day'])) {
            $this->returnJsonMsg('1023', [], Common::C('code', '1023'));
        }
        $service_time_model = new ServiceTime();
        $service_time_fields = 'hours';
        $info = $service_time_model->getInfo($where, true, $service_time_fields);
        if (empty($info)) {
            $this->returnJsonMsg('1024', [], Common::C('code', '1024'));
        }
        $rs_info = json_decode(htmlspecialchars_decode($info['hours']), true);
        $this->returnJsonMsg('200', $rs_info, Common::C('code', '200'));
    }

    /**
     * 获取日期
     * @return array
     */
    public function actionGetDays()
    {
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
        $list = [];
        for ($i=0; $i<7; $i++) {
            $list[$i]['day']      = date('Y-m-d', strtotime("+".($i)." day", time()));
            $list[$i]['week']     = Common::getWeek($list[$i]['day']);
            $list[$i]['show_day'] = date('m.d', strtotime($list[$i]['day']));
        }
        $rs_info['now_hour'] = date('H', time());
        $rs_info['days']     = $list;
        $this->returnJsonMsg('200', $rs_info, Common::C('code', '200'));
    }
    /**
     *【单天】设置时间
     * @return array
     */
    public function actionSetTime()
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
        $data['day'] = RequestHelper::post('day', '', '');
        if (empty($data['day'])) {
            $this->returnJsonMsg('1023', [], Common::C('code', '1023'));
        }
        $data['week'] = Common::getWeek($data['day']);
        $hours = RequestHelper::post('json_str', '', '');
        $check_json = json_decode(htmlspecialchars_decode($hours), true);
        if (empty($check_json)) {
            $this->returnJsonMsg('1030', [], Common::C('code', '1030'));
        }
        $data['hours'] = $hours;
        $service_time_model = new ServiceTime();
        /**判断是否存在 存在执行更新 不存在执行添加**/
        $service_time_where['uid']    = $data['uid'];
        $service_time_where['mobile'] = $data['mobile'];
        $service_time_where['day']    = $data['day'];
        $update_data['hours']         = json_encode($data['hours']);

        $check_repeat = $this->_checkRepeatTime($data['hours']);
        if (!$check_repeat) {
            $this->returnJsonMsg('1040', [], Common::C('code', '1040'));
        }
        $info = $service_time_model->getInfo($service_time_where, true, 'id');
        if (empty($info)) {
            $rs = $service_time_model->insertInfo($data);
        } else {
            $update_data['update_time'] = date('Y-m-d H:i:s', time());
            $rs = $service_time_model->updateInfo($update_data, $service_time_where);
        }
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 批量设置服务时间
     * @return array
     */
    public function actionBatchSetTime()
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
        $service_time_model = new ServiceTime();
        $hours = RequestHelper::post('json_str', '', '');
        $hours = json_decode(htmlspecialchars_decode($hours), true);
        if (empty($hours)) {
            $this->returnJsonMsg('1030', [], Common::C('code', '1030'));
        }
        $rs = false;
        foreach ($hours as $k => $v) {
            $data_add[$k]['uid']    = $data['uid'];
            $data_add[$k]['mobile'] = $data['mobile'];
            $data_add[$k]['day']    = $v['day'];
            $data_add[$k]['week']   = Common::getWeek($data_add[$k]['day']);
            $data_add[$k]['hours']  = json_encode($v['hours']);

            $service_time_where[$k]['uid']    = $data['uid'];
            $service_time_where[$k]['mobile'] = $data['mobile'];
            $service_time_where[$k]['day']    = $v['day'];
            $update_data[$k]['hours']         = json_encode($v['hours']);

            $check_repeat = $this->_checkRepeatTime($v['hours']);
            if (!$check_repeat) {
                $this->returnJsonMsg('1040', [], Common::C('code', '1040'));
                break;
            }
            $info = $service_time_model->getInfo($service_time_where[$k], true, 'id');
            if (empty($info)) {
                $rs = $service_time_model->insertInfo($data_add[$k]);
            } else {
                $update_data[$k]['update_time'] = date('Y-m-d H:i:s', time());
                $rs = $service_time_model->updateInfo($update_data[$k], $service_time_where[$k]);
            }
        }
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 检验时间
     * @return array
     */
    public function actionCheckTime()
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
        $where['day'] = RequestHelper::post('day', '', '');
        if (empty($where['day'])) {
            $this->returnJsonMsg('1023', [], Common::C('code', '1023'));
        }
        $hour = RequestHelper::post('hour', '', '');
        if (empty($hour)) {
            $this->returnJsonMsg('1025', [], Common::C('code', '1025'));
        }
        $status = RequestHelper::post('status', '', '');  //1=启用 2=禁用
        if (empty($status)) {
            $this->returnJsonMsg('1026', [], Common::C('code', '1026'));
        }
        $service_time_model = new ServiceTime();
        $info = $service_time_model->getInfo($where, true, 'hours');
        if (empty($info)) {
            $this->returnJsonMsg('1024', [], Common::C('code', '1024'));
        }
        $hours = json_decode(htmlspecialchars_decode($info['hours']), true);
        if (!empty($hours)) {
            $have = 0;
            foreach ($hours as $k => $v) {
                $hours[$k]['hour'] = $v['hour'];
                if ($v['hour'] == $hour) {
                    $have += 1;
                    if ($status == '1') {
                        /**启用**/
                        if ($v['is_available'] == '1') {
                            $this->returnJsonMsg('1027', [], Common::C('code', '1027'));
                        } else {
                            $hours[$k]['is_available'] = '1';
                            $v['is_available'] = '1';
                        }
                    } else {
                        /**禁用**/
                        if ($v['is_available'] == '3') {
                            $this->returnJsonMsg('1041', [], Common::C('code', '1041'));
                        } elseif ($v['is_available'] == '2') {
                            $this->returnJsonMsg('1028', [], Common::C('code', '1028'));
                        } else {
                            $hours[$k]['is_available'] = '2';
                        }
                    }
                    break;
                }
            }
            if ($have == 0) {
                $this->returnJsonMsg('1029', [], Common::C('code', '1029'));
            }
        }
        $service_time_model = new ServiceTime();
        $update_data['hours'] = json_encode($hours);
        $rs = $service_time_model->updateInfo($update_data, $where);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 检验时间是否重复
     * @param array $time_array 时间数组
     * @return bool
     */
    private function _checkRepeatTime($time_array = [])
    {
        $rs = true;
        if (!empty($time_array)) {
            $hours = [];
            foreach ($time_array as $k => $v) {
                $hours[] = $v['hour'];
            }
            if (count($hours) != count(array_unique($hours))) {
                return false;
            }
        }
        return $rs;
    }
}
