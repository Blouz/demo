<?php
/**
 * 服务时间设置表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015-09-18
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */

namespace frontend\models\i500_social;

use common\helpers\Common;

/**
 * 服务时间设置表
 *
 * @category MODEL
 * @package  Social
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class ServiceTime extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_service_time}}';
    }

    /**
     * 检验时间状态
     * @param int    $uid  用户ID
     * @param string $day  日期
     * @param string $hour 时间段
     * @return bool
     */
    public function checkTimeStatus($uid = 0, $day = '', $hour = '')
    {
        if (empty($uid) || empty($day) || empty($hour)) {
            return false;
        }
        $service_time_model = new ServiceTime();
        $service_time_where['uid'] = $uid;
        $service_time_where['day'] = $day;
        $info = $service_time_model->getInfo($service_time_where, true, 'hours');
        if (empty($info)) {
            return false;
        } else {
            $hours = json_decode(htmlspecialchars_decode($info['hours']), true);
            if (empty($hours)) {
                return false;
            }
            foreach ($hours as $k => $v) {
                if ($v['hour'] == $hour) {
                    if ($v['is_available'] == '2') {
                        return false;
                        break;
                    }
                    if ($v['appointment_num'] >= Common::C('maxAppointmentNumber')) {
                        return false;
                        break;
                    }
                }
            }
            return true;
        }
    }

    /**
     * 更新时间段为不可预约状态
     * @param int    $uid  用户ID
     * @param string $day  日期
     * @param string $hour 时间段
     * @return bool
     */
    public function updateTimeStatus($uid = 0, $day = '', $hour = '')
    {
        if (empty($uid) || empty($day) || empty($hour)) {
            return false;
        }
        $service_time_model = new ServiceTime();
        $service_time_where['uid'] = $uid;
        $service_time_where['day'] = $day;
        $info = $service_time_model->getInfo($service_time_where, true, 'hours');
        if (empty($info)) {
            return false;
        } else {
            $hours = json_decode(htmlspecialchars_decode($info['hours']), true);
            if (empty($hours)) {
                return false;
            }
            foreach ($hours as $k => $v) {
                if ($v['hour'] == $hour) {
                    if ($v['is_available'] == '2') {
                        return false;
                        break;
                    }
                    $hours[$k]['is_available'] = '3';
                    $hours[$k]['appointment_num'] = $v['appointment_num'] + 1;
                    break;
                } else {
                    return false;
                    break;
                }
            }
            $update_data['hours'] = json_encode($hours);
            $rs = $service_time_model->updateInfo($update_data, $service_time_where);
            if (!$rs) {
                return false;
            }
            return true;
        }
    }
}
