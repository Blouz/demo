<?php
/**
 * 好友相关
 *
 * PHP Version 5
 * 可写多行的文件相关说明
 *
 * @category  I500M
 * @package   Member
 * @author    xuxiaoyu <xuxiaoyu@i500m.com>
 * @time      16/10/14
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      xuxiaoyu@i500m.com
 */
namespace frontend\modules\v8\controllers;

use common\helpers\RequestHelper;
use common\helpers\Common;
use frontend\models\i500_social\AppStats;
  
class StatsController extends BaseController
{

    /**
     * 用户统计 列表
     * @param string  $mobile    电话
     * @return array
     * @author xuxiaoyu <xuxiaoyu@i500m.com>
     */
    public function actionList()
    {   
        $imei = RequestHelper::post('imei', '', '');
        if (empty($imei)) {
            $this->returnJsonMsg('5001', [], '手机串码为空');
        }
        $brand = RequestHelper::post('brand', '', '');
        if (empty($brand)) {
            $this->returnJsonMsg('5004', [], '手机品牌为空');
        }
        $login_ip = RequestHelper::post('login_ip', '', '');
        if (empty($login_ip)) {
            $this->returnJsonMsg('5005', [], '用户登录ip为空');
        }
        $download_channel = RequestHelper::post('download_channel', '', '');
        if (empty($download_channel)) {
            $this->returnJsonMsg('5006', [], '下载渠道为空');
        }
        $user_mobile = RequestHelper::post('user_mobile', '', '');
        $information = AppStats::find()->select(['imei','brand', 'first_login_time', 'last_login_time', 'login_ip', 'user_mobile', 'download_channel'])
                                       ->where(['imei'=>$imei])
				                       ->asArray()
				                       ->one();
        if (empty($information)) {
	        	$first_login_time = date('Y-m-d H:i:s');
	            $users = new AppStats();
		        $users -> imei = $imei;
		        $users -> brand = $brand;
		        $users -> first_login_time = $first_login_time;
		        $users -> login_ip = $login_ip;
		        $users -> user_mobile = $user_mobile;
		        $users -> download_channel = $download_channel;
		        $res = $users -> save();
		        if (!$res) {
		            $this->returnJsonMsg('5002', [], '失败');
		        }
        }else{
            $last_login_time = date('Y-m-d H:i:s');
            $res = AppStats::updateAll(['last_login_time'=>$last_login_time],['imei'=>$imei]);
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
}
