<?php
/**
 * APP基类
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   BASE
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      2017/4/13
 * @copyright 辽宁i500科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */

namespace frontend\modules\rewrite\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use yii\web\Controller;
use frontend\models\i500_social_rewrite\User;
use frontend\models\i500_social_rewrite\UserToken;

/**
 * BASE
 *
 * @category Social
 * @package  BASE
 * @author   liuyanwei <liuyanwei@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     liuyanwei@i500m.com
 */
class BaseController extends Controller
{
    //关闭 csrf 验证 
    public $enableCsrfValidation = false;

    protected $params = null;

    public $mobile = '';

    public $token = '';
    //用户权限
    public $permission = 0;

    //用户所在小区id
    public $community_info = [];

    //是否注册成功
    public $is_verify =0;




    /**
     * 初始化(获取用户一些基本信息 小区id  权限)
     * @return array
     */
    public function init()
    {
        parent::init();
        //获取请求类型
        $method = RequestHelper::getMethod();
        switch ($method) {
            case 'POST':
                $this->params = RequestHelper::post();
                break;
            case 'PUT' :
                $this->params = RequestHelper::put();
                break;
            default :
                $this->params = RequestHelper::get();
                break;
        }
        $this->saveLog(Common::C('returnLogFile'), var_export($this->params, true));
        //获取当前访问controller名称
        if($this->id != "login"){
            //除了login外，所有controller必须有mobile token 验证
            if (empty($this->params['token']) || empty($this->params['mobile'])){
                $this->returnJsonMsg(403, [], Common::C('coderewrite', '403'));
            }

            $this->mobile = $this->params['mobile'];
            //验证token 是否正确
            $user_status = $this->_checkToken($this->params['token'], $this->params['mobile']);
            if($user_status != '200') {
                $this->returnJsonMsg($user_status, [], Common::C('coderewrite', $user_status));
            }
        }
    }
    
    /**
     * 返回JSON格式的数据
     * @param string $code    错误代码
     * @param array  $data    数据
     * @param string $message 错误说明
     * @return array
     */
    public function returnJsonMsg($code='', $data=array(), $message='')
    {
        $arr = array(
            'code' => $code,
            'data' => $data,
            'message' => $message,
        );
        //记录接口数据
        $this->saveLog(Common::C('paramsLogFile'), var_export($arr, true));
        $ret = json_encode($arr);
        $ret_str = str_replace('(null)', '', $ret);      //出现在数据库中
        $ret_str = str_replace('null', 'null', $ret_str);  //出现在返回值中
        die($ret_str);
    }


    /**
     * 检查Token
     * @param string $token  Token
     * @param string $mobile 手机号
     * @return int
     */
    private function _checkToken($token = '', $mobile = '')
    {   
        $code = 200;
        $token_model = new UserToken();
        $token_where['mobile'] = $mobile;
        $token_fields = 'token,token_expired_in';
        $token_info = $token_model->getInfo($token_where, true, $token_fields);
        if (empty($token_info)) {
            $code = 506;
        } else if($token != $token_info['token']){
            $code = 508;
        } else if (time() > $token_info['token_expired_in']) {
            $code = 509;
        } else {
            $user_model = new User();
            $user = $user_model->getUserStatus($mobile);
            if (!isset($user['status']) || $user['status'] != 2) {
                $code = 507;
            }else {
                $this->community_info = $user['userCommunity'];
            }
        }
        return $code;
    }

    /**
     * 开启日志
     * @param string $path 路径
     * @param string $data 数据
     * @return bool
     */
    public function saveLog($path = '', $data = '')
    {
        if (Common::C('openLog')) {
            file_put_contents($path, "执行时间：" . date('Y-m-d H:i:s') . " 数据：" . var_export($data, true) . "\n", FILE_APPEND);
        }
    }
}
