<?php
/**
 * 小工具
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Plug
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/05 09:21
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */

namespace frontend\modules\v1\controllers;

use frontend\models\i500_social\User;
use common\helpers\SsdbHelper;
use frontend\models\i500_social\UserChannel;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserToken;
use frontend\models\i500_social\UserVerifyCode;
use frontend\models\i500_social\ServiceTime;
use frontend\models\i500_social\ServiceWeekTime;
use Yii;
use common\helpers\RequestHelper;
use common\helpers\Common;
use yii\web\Controller;

/**
 * Plug
 *
 * @category Social
 * @package  Plug
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class PlugController extends Controller
{
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }
    /**
     * 生成签名
     * @return string
     */
    public function actionSign()
    {
        $app_code = 'DKJA@(SL)RssMAKDKas!L';
        //$timestamp = time();
        $timestamp = '1439200388';
        $val  = '';
        $params = RequestHelper::get();
        $data['msg'] = '';
        if ($params) {
            //ksort($params);
            foreach ($params as $k => $v) {
                $val .= strtolower($v);
            }
            $sign = md5(md5(md5($app_code.$timestamp).md5($timestamp)).md5($val));
            $data['msg'] = '&amp;appId=I500_SOCIAL&amp;dev=1&amp;timestamp='.$timestamp.'&amp;sign='.$sign;
        }
        $data['timestamp'] = $timestamp;
        return $this->render('sign', ['data'=>$data]);
    }

//    public function actionTest()
//    {
//        $app_code = 'DKJA@(SL)RssMAKDKas!L';
//        echo "app_code=".$app_code."<br><br>";
//        $timestamp = '1439200388';
//        echo "timestamp=".$timestamp."<br><br>";
//        $channel = '3';
//        echo "参数：channel=".$channel."<br><br>";
//        $channel_user_id = '123456';
//        echo "参数：channel_user_id=".$channel_user_id."<br><br>";
//        $md5_1 = "md5(app_code+timestamp)=".md5($app_code.$timestamp);
//        echo $md5_1."<br><br>";
//        $md5_2 = "md5(timestamp)=".md5($timestamp);
//        echo $md5_2."<br><br>";
//        $md5_3 = "md5(3+123456)=".md5(3123456);
//        echo $md5_3."<br><br>";
//        $md5_str = md5(md5(md5($app_code.$timestamp).md5($timestamp)).md5(3123456));
//        $md5_4 = "md5(md5(md5(app_code+timestamp)+md5(timestamp))+md5(3+123456))=".$md5_str;
//        echo $md5_4;
//
//    }

    /**
     * 自定义服务时间V1
     * @return string
     */
    public function actionTime()
    {
        return $this->render('time');
    }

    /**
     * 自定义服务时间V2
     * @return string
     */
    public function actionTime2()
    {
        return $this->render('time2');
    }


    /**
     * 获取验证码
     * @return string
     */
    public function actionSms()
    {
        return $this->render('sms');
    }

    /**
     * 管理SSDB
     * @return string
     */
    public function actionSsdb()
    {
        return $this->render('ssdb');
    }

    /**
     * 获取所有Key
     * @return array
     */
    public function actionGetAllKey()
    {
        $rs = SsdbHelper::Cache('keys');
        echo json_encode(['code' => 'ok', 'data' => $rs]);
    }

    /**
     * 获取Key
     * @return array
     */
    public function actionGetKey()
    {
        $key = RequestHelper::get('key', '', '');
        $value = SsdbHelper::Cache('get', $key);
        echo json_encode(['code' => 'ok', 'data' => var_export($value, true)]);
    }

    /**
     * 删除Key
     * @return array
     */
    public function actionDelKey()
    {
        $key = RequestHelper::get('key', '', '');
        $rs = SsdbHelper::Cache('del', $key);
        if ($rs) {
            echo json_encode(['code' => 'ok']);
        } else {
            echo json_encode(['code' => 'no']);
        }
    }

    /**
     * 设置服务时间V1
     * @return array
     */
    public function actionSetServiceTime()
    {
        $id    = RequestHelper::post('id', '0', 'intval');
        $times = RequestHelper::post('times', '[]', '');
        if (empty($id)) {
            die (json_encode(['code' => 'no', 'msg' => 'ID不能为空']));
        }
        if (empty($times)) {
            die (json_encode(['code' => 'no', 'msg' => '服务时间不能为空']));
        }
        $update_hours = [];
        $times_count = count($times);
        for ($i=0;$i<$times_count;$i++) {
            $update_hours[$i]['hour']            = $times[$i];
            $update_hours[$i]['is_available']    = '1';
            $update_hours[$i]['appointment_num'] = '0';
        }
        $service_time_model = new ServiceTime();
        $where['id'] = $id;
        $info = $service_time_model->getInfo($where, true. 'id');
        if (empty($info)) {
            die (json_encode(['code' => 'no', 'msg' => '不存在该ID的信息！']));
        }
        $update['hours'] = json_encode($update_hours);
        $rs = $service_time_model->updateInfo($update, $where);
        if (!$rs) {
            die (json_encode(['code' => 'no', 'msg' => '服务器繁忙请重试！']));
        }
        die (json_encode(['code' => 'ok', 'msg' => '操作成功']));
    }

    /**
     * 设置默认服务时间V2
     * @return array
     */
    public function actionSetDefaultServiceTime()
    {
        $uid    = RequestHelper::post('uid', '0', 'intval');
        $mobile = RequestHelper::post('mobile', '[]', '');
        if (empty($uid)) {
            die (json_encode(['code' => 'no', 'msg' => 'UID不能为空']));
        }
        if (empty($mobile)) {
            die (json_encode(['code' => 'no', 'msg' => '手机号不能为空']));
        }
        if (!Common::validateMobile($mobile)) {
            die (json_encode(['code' => 'no', 'msg' => '手机号格式不正确！']));
        }
        $service_week_time_model = new ServiceWeekTime();
        $rs = $service_week_time_model->setDefaultTime($uid, $mobile);
        if (!$rs) {
            die (json_encode(['code' => 'no', 'msg' => '服务器繁忙请重试！']));
        }
        die (json_encode(['code' => 'ok', 'msg' => '操作成功']));
    }
    /**
     * 删除用户
     * @return array
     */
    public function actionRemoveUser()
    {

        $mobile = RequestHelper::get('mobile', '', '');
        $type   = RequestHelper::get('type', '', '');
        if ($type == '1') {
            UserBasicInfo::deleteAll('mobile='.$mobile);
            $rs_1 = User::deleteAll('mobile='.$mobile);
            if ($rs_1) {
                echo json_encode(['code'=>'ok','msg'=>'删除成功']);
            } else {
                echo json_encode(['code'=>'no','msg'=>'删除失败']);
            }
        } else {
            $user_model = new User();
            $user_where['mobile']     = $mobile;
            $user_where['is_deleted'] = '2';
            $user_info = $user_model->getInfo($user_where, true, 'id,mobile');
            if ($user_info) {
                echo json_encode(['code'=>'ok','msg'=>'存在']);
            } else {
                echo json_encode(['code'=>'ok','msg'=>'不存在']);
            }
        }
    }

    /**
     * 解绑
     * @return array
     */
    public function actionRemoveBindUser()
    {
        $mobile = RequestHelper::get('mobile', '', '');
        $rs_1 = User::deleteAll('mobile='.$mobile);
        $rs_2 = UserChannel::deleteAll('mobile='.$mobile);
        UserBasicInfo::deleteAll('mobile='.$mobile);
        if ($rs_1 && $rs_2) {
            echo json_encode(['code'=>'ok','msg'=>'解绑成功']);
        } else {
            echo json_encode(['code'=>'no','msg'=>'解绑失败']);
        }
    }

    /**
     * 查看Code
     * @return string
     */
    public function actionGetErrorMsg()
    {
        $code = RequestHelper::get('code', '', '');
        $msg = Common::C('code', $code);
        if ($msg) {
            echo json_encode(['code'=>'ok','msg'=>'错误信息：'.$msg]);
        } else {
            echo json_encode(['code'=>'ok','msg'=>'抱歉，未能查询到。']);
        }
    }

    /**
     * 获取Token的方法
     * @return array
     */
    public function actionGetToken()
    {
        $mobile = RequestHelper::get('mobile', '', '');
        $user_token_model = new UserToken();
        $user_token_where['mobile'] = $mobile;
        $user_token_info = $user_token_model->getInfo($user_token_where);
        if (!empty($user_token_info)) {
            $msg = 'Token：'.$user_token_info['token'].' 创建时间：'.$user_token_info['create_time'];
        } else {
            $msg = '未能查询到数据。';
        }
        echo json_encode(['code'=>'ok','msg'=>$msg]);
    }

    /**
     * 获取验证码的方法
     * @return array
     */
    public function actionGetCode()
    {
        $mobile = RequestHelper::get('mobile', '', '');
        $user_code_model = new UserVerifyCode();
        $user_code_where['mobile'] = $mobile;
        $user_code_info = $user_code_model->getInfo($user_code_where, true, '*', '', 'id desc');
        if (!empty($user_code_info)) {
            $data['mobile']      = $user_code_info['mobile'];
            $data['code']        = $user_code_info['code'];
            $data['type']        = $user_code_info['type'];
            $data['create_time'] = $user_code_info['create_time'];
            $data['expires_in']  = $user_code_info['expires_in'];
        } else {
            $data['mobile'] = 'Error';
            $data['code'] = 'Error';
            $data['type'] = 'Error';
            $data['create_time'] = 'Error';
            $data['expires_in'] = 'Error';
        }
        echo json_encode(['code'=>'ok','data'=>$data]);
    }

    /**
     * 生成Md5字符串
     * @return array
     */
    public function actionGetMd5()
    {
        $str = RequestHelper::get('str', '', '');
        echo json_encode(['code'=>'ok','msg'=>md5($str)]);
    }
}
