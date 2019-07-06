<?php
/**
 * 错误提示
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   ERROR
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
use frontend\models\i500_social\ApiErrorLog;

/**
 * ERROR
 *
 * @category Social
 * @package  ERROR
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class ErrorController extends Controller
{
    /**
     * 错误页面提示
     * @return string
     */
    public function actionIndex()
    {
     
        $this->_recordLog();
        $arr = array(
            'code' => '500',
            'data' => [],
            'message' => '抱歉，操作失败或请求方法不存在',
        );
        echo json_encode($arr);
    }

    /**
     * 记录错误日志
     * @return bool
     */
    private function _recordLog()
    {
        if (Common::C('error_control')) {
            /**开通错误监控**/
            $method = RequestHelper::getMethod();
            switch ($method) {
                case 'POST':
                    $params = RequestHelper::post();
                    break;
                case 'PUT' :
                    $params = RequestHelper::put();
                    break;
                default :
                    $params = RequestHelper::get();
                    break;
            }
            $data['params'] = json_encode($params);
            $exception = \Yii::$app->errorHandler->exception;
            $e = [];
            if ($exception !== null) {
                //$e['code']     = $exception->getCode();  //Yii Error Code 无意义
                $e['message']  = $exception->getMessage();
                //$e['trace']    = $exception->getTrace();
                $e['file']     = $exception->getFile();
                $e['line']     = $exception->getLine();
                //$e['previous'] = $exception->getPrevious();
            }
            $data['error_info'] = json_encode($e);
            $route = \Yii::$app->requestedRoute;
            $route_arr = explode('/', $route);
            $data['module']     = isset($route_arr[0]) ? $route_arr[0] : '';
            $data['controller'] = isset($route_arr[1]) ? $route_arr[1] : '';
            $data['action']     = isset($route_arr[2]) ? $route_arr[2] : 'index';
            $data['ip'] = Common::getIp();
            $model = new ApiErrorLog();
            return $model->insertInfo($data);
        } else {
            return true;
        }
    }
}
