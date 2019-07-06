<?php
/**
 * 服务一周时间设置表
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
 * 服务一周时间设置表
 *
 * @category MODEL
 * @package  Social
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class ServiceWeekTime extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_service_week_time}}';
    }

    /**
     * 验证时间状态
     * @param int    $uid      用户ID
     * @param int    $week_int 星期标识
     * @param string $hour     小时
     * @return bool
     */
    public function checkTimeStatus($uid = 0, $week_int = 0, $hour = '')
    {
        $service_week_time_model = new ServiceWeekTime();
        $service_week_time_where['uid'] = $uid;
        $service_week_time_where['week_int'] = $week_int;
        $info = $service_week_time_model->getInfo($service_week_time_where, true, 'hours');
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
                }
            }
            return true;
        }
    }

    /**
     * 更新时间状态
     * @param int    $uid      用户ID
     * @param int    $week_int 星期标识
     * @param string $hour     小时
     * @return bool
     */
    public function updateTimeStatus($uid = 0, $week_int = 0, $hour = '')
    {
        $service_week_time_model = new ServiceWeekTime();
        $service_week_time_where['uid'] = $uid;
        $service_week_time_where['week_int'] = $week_int;
        $info = $service_week_time_model->getInfo($service_week_time_where, true, 'hours');
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
                    break;
                } else {
                    return false;
                    break;
                }
            }
            $update_data['hours'] = json_encode($hours);
            $rs = $service_week_time_model->updateInfo($update_data, $service_week_time_where);
            if (!$rs) {
                return false;
            }
            return true;
        }
    }

    /**
     * 设置默认时间
     * @param int    $uid    用户ID
     * @param string $mobile 手机号
     * @return array
     */
    public function setDefaultTime($uid = 0, $mobile = '')
    {
        /**开店时设置默认时间，初始化每一天的服务时间**/
        $default[0]['week'] = '一';
        $default[0]['week_int'] = '1';
        $default[0]['hours'][0]['hour'] = '19';
        $default[0]['hours'][0]['is_available'] = '1';
        $default[0]['hours'][1]['hour'] = '20';
        $default[0]['hours'][1]['is_available'] = '1';
        $default[0]['hours'][2]['hour'] = '21';
        $default[0]['hours'][2]['is_available'] = '1';

        $default[1]['week'] = '二';
        $default[1]['week_int'] = '2';
        $default[1]['hours'][0]['hour'] = '19';
        $default[1]['hours'][0]['is_available'] = '1';
        $default[1]['hours'][1]['hour'] = '20';
        $default[1]['hours'][1]['is_available'] = '1';
        $default[1]['hours'][2]['hour'] = '21';
        $default[1]['hours'][2]['is_available'] = '1';

        $default[2]['week'] = '三';
        $default[2]['week_int'] = '3';
        $default[2]['hours'][0]['hour'] = '19';
        $default[2]['hours'][0]['is_available'] = '1';
        $default[2]['hours'][1]['hour'] = '20';
        $default[2]['hours'][1]['is_available'] = '1';
        $default[2]['hours'][2]['hour'] = '21';
        $default[2]['hours'][2]['is_available'] = '1';

        $default[3]['week'] = '四';
        $default[3]['week_int'] = '4';
        $default[3]['hours'][0]['hour'] = '19';
        $default[3]['hours'][0]['is_available'] = '1';
        $default[3]['hours'][1]['hour'] = '20';
        $default[3]['hours'][1]['is_available'] = '1';
        $default[3]['hours'][2]['hour'] = '21';
        $default[3]['hours'][2]['is_available'] = '1';

        $default[4]['week'] = '五';
        $default[4]['week_int'] = '5';
        $default[4]['hours'][0]['hour'] = '19';
        $default[4]['hours'][0]['is_available'] = '1';
        $default[4]['hours'][1]['hour'] = '20';
        $default[4]['hours'][1]['is_available'] = '1';
        $default[4]['hours'][2]['hour'] = '21';
        $default[4]['hours'][2]['is_available'] = '1';

        $default[5]['week'] = '六';
        $default[5]['week_int'] = '6';
        $default[5]['hours'][0]['hour'] = '10';
        $default[5]['hours'][0]['is_available'] = '1';
        $default[5]['hours'][1]['hour'] = '11';
        $default[5]['hours'][1]['is_available'] = '1';
        $default[5]['hours'][2]['hour'] = '12';
        $default[5]['hours'][2]['is_available'] = '1';
        $default[5]['hours'][3]['hour'] = '13';
        $default[5]['hours'][3]['is_available'] = '1';
        $default[5]['hours'][4]['hour'] = '14';
        $default[5]['hours'][4]['is_available'] = '1';
        $default[5]['hours'][5]['hour'] = '15';
        $default[5]['hours'][5]['is_available'] = '1';
        $default[5]['hours'][6]['hour'] = '16';
        $default[5]['hours'][6]['is_available'] = '1';
        $default[5]['hours'][7]['hour'] = '17';
        $default[5]['hours'][7]['is_available'] = '1';
        $default[5]['hours'][8]['hour'] = '18';
        $default[5]['hours'][8]['is_available'] = '1';
        $default[5]['hours'][9]['hour'] = '19';
        $default[5]['hours'][9]['is_available'] = '1';
        $default[5]['hours'][10]['hour'] = '20';
        $default[5]['hours'][10]['is_available'] = '1';
        $default[5]['hours'][11]['hour'] = '21';
        $default[5]['hours'][11]['is_available'] = '1';

        $default[6]['week'] = '日';
        $default[6]['week_int'] = '7';
        $default[6]['hours'][0]['hour'] = '10';
        $default[6]['hours'][0]['is_available'] = '1';
        $default[6]['hours'][1]['hour'] = '11';
        $default[6]['hours'][1]['is_available'] = '1';
        $default[6]['hours'][2]['hour'] = '12';
        $default[6]['hours'][2]['is_available'] = '1';
        $default[6]['hours'][3]['hour'] = '13';
        $default[6]['hours'][3]['is_available'] = '1';
        $default[6]['hours'][4]['hour'] = '14';
        $default[6]['hours'][4]['is_available'] = '1';
        $default[6]['hours'][5]['hour'] = '15';
        $default[6]['hours'][5]['is_available'] = '1';
        $default[6]['hours'][6]['hour'] = '16';
        $default[6]['hours'][6]['is_available'] = '1';
        $default[6]['hours'][7]['hour'] = '17';
        $default[6]['hours'][7]['is_available'] = '1';
        $default[6]['hours'][8]['hour'] = '18';
        $default[6]['hours'][8]['is_available'] = '1';
        $default[6]['hours'][9]['hour'] = '19';
        $default[6]['hours'][9]['is_available'] = '1';
        $default[6]['hours'][10]['hour'] = '20';
        $default[6]['hours'][10]['is_available'] = '1';
        $default[6]['hours'][11]['hour'] = '21';
        $default[6]['hours'][11]['is_available'] = '1';
        $rs = false;
        $service_week_time_model = new ServiceWeekTime();
        foreach ($default as $k => $v) {
            $data_add[$k]['uid']      = $uid;
            $data_add[$k]['mobile']   = $mobile;
            $data_add[$k]['week']     = $v['week'];
            $data_add[$k]['week_int'] = $v['week_int'];
            $data_add[$k]['hours']    = json_encode($v['hours']);

            $service_week_time_where[$k]['uid']      = $uid;
            $service_week_time_where[$k]['mobile']   = $mobile;
            $service_week_time_where[$k]['week_int'] = $v['week_int'];

            $update_data[$k]['hours'] = json_encode($v['hours']);

            $info = $service_week_time_model->getInfo($service_week_time_where[$k], true, 'id');
            if (empty($info)) {
                $rs = $service_week_time_model->insertInfo($data_add[$k]);
            } else {
                $update_data[$k]['update_time'] = date('Y-m-d H:i:s', time());
                $rs = $service_week_time_model->updateInfo($update_data[$k], $service_week_time_where[$k]);
            }
        }
        if (!$rs) {
            return false;
        }
        return true;
    }
}
