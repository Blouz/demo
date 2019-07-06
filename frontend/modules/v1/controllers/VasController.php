<?php
/**
 * 恒信通增值业务接口(value added service)
 *
 * PHP Version 5
 *
 * @category  SOCIAL
 * @package   CONTROLLER
 * @author    zhengyu <zhengyu@iyangpin.com>
 * @time      15/8/25 13:32
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      zhengyu@iyangpin.com
 */

namespace frontend\modules\v1\controllers;

use Yii;
use common\helpers\HxtHelper;
use common\helpers\RequestHelper;


/**
 * 恒信通增值业务接口
 *
 * @category SOCIAL
 * @package  CONTROLLER
 * @author   zhengyu <zhengyu@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     zhengyu@iyangpin.com
 */
class VasController extends BaseController
{

    /**
     * Action之前的处理
     *
     * Author zhengyu@iyangpin.com
     *
     * @param \yii\base\Action $action action
     *
     * @return bool
     *
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }


    /**
     * 接口-查询智能表信息
     *
     * Author zhengyu@iyangpin.com
     * post
     *   yhbh   用户编号
     *   ip     可选参数
     *
     * @return void
     */
    public function actionQueryznb()
    {
        $helper_hxt = new HxtHelper();

        $arr = array();
        $arr['TerminalID'] = Yii::$app->params['hxt_TerminalID'];
        $arr['KeyID'] = Yii::$app->params['hxt_KeyID'];
        $arr['UserID'] = '';
        $arr['Account'] = '';
        $arr['EMail'] = '';
        $arr['CardNo'] = '';
        $arr['TotalFee'] = 0;
        $arr['ShopCode'] = '3202';
        $yhbh = RequestHelper::post('yhbh', '', 'trim');
        if ($yhbh == '') {
            echo json_encode(array('code' => 0, 'data' => array(), 'msg' => '缺少参数:用户编号'));
            return;
        }
        $arr['PaymentInfo'] = $yhbh . '$G00';
        $arr['IPAddress'] = RequestHelper::post('ip', '127.0.0.1', 'trim');
        $arr['Source'] = '';
        $arr['TraceNo'] = '';

        $arr['MCode'] = $this->_createQueryMcode($arr);

        $arr_result = $helper_hxt->query($arr);
        //print_r($arr_result);

        echo json_encode($arr_result);
        return;
    }

    /**
     * 接口-智能电表缴费
     *
     * Author zhengyu@iyangpin.com
     * post
     *   yhbh   用户编号
     *   money  缴费金额,单位：分
     *   ip     可选参数
     *
     * @return void
     */
    public function actionPayznb()
    {
        $helper_hxt = new HxtHelper();

        $yhbh = RequestHelper::post('yhbh', '', 'trim');
        if ($yhbh == '') {
            echo json_encode(array('code' => 0, 'data' => array(), 'msg' => '缺少参数:用户编号'));
            return;
        }
        $money = RequestHelper::post('money', 0, 'intval');
        if ($yhbh == 0) {
            echo json_encode(array('code' => 0, 'data' => array(), 'msg' => '缺少参数:缴费金额'));
            return;
        }
        $ip = RequestHelper::post('ip', '127.0.0.1', 'trim');

        //4200 查询交易
        $arr = array();
        $arr['TerminalID'] = Yii::$app->params['hxt_TerminalID'];
        $arr['KeyID'] = Yii::$app->params['hxt_KeyID'];
        $arr['UserID'] = '';
        $arr['Account'] = '';
        $arr['EMail'] = '';
        $arr['CardNo'] = '';
        $arr['TotalFee'] = $money;
        $arr['ShopCode'] = '4200';
        $arr['PaymentInfo'] = $yhbh . '$G00$03$';
        $arr['IPAddress'] = $ip;
        $arr['Source'] = '';
        $arr['TraceNo'] = '';

        $arr['MCode'] = $this->_createQueryMcode($arr);

        $arr_result = $helper_hxt->query($arr);
        if (isset($arr_result['code']) && $arr_result['code'] == 0) {
            echo json_encode(array('code' => 0, 'data' => array(), 'msg' => '错误代码:11'));
            return;
        }
        if (isset($arr_result['data']) && isset($arr_result['data']['PaymentInfo'])) {
            $arr_tmp = explode('$', $arr_result['data']['PaymentInfo']);
            $order_id = $arr_tmp[0];//中心流水号
        } else {
            echo json_encode(array('code' => 0, 'data' => array(), 'msg' => '错误代码:12'));
            return;
        }


        $arr_data = array(
            'yhbh' => $yhbh,
            'money' => $money,
            'orderid' => $order_id,
        );


        //4200 缴费
        $arr = array();
        $arr['TerminalID'] = Yii::$app->params['hxt_TerminalID'];
        $arr['KeyID'] = Yii::$app->params['hxt_KeyID'];
        $arr['UserID'] = '';
        $arr['Account'] = '';
        $arr['EMail'] = '';
        $arr['CardNo'] = '';
        $arr['SettlementDate'] = date("md", time());//银行清算日
        $arr['HostSerialNo'] = '999999999999';//银行扣费号
        $arr['TotalFee'] = $money;
        $arr['ShopCode'] = '4200';
        $arr['PaymentInfo'] = $yhbh . '$' . $order_id;
        $arr['IPAddress'] = $ip;
        $arr['Source'] = '';
        $arr['TraceNo'] = '';
        $arr['PromotionCode'] = '';

        $arr['MCode'] = $this->_createPayMcode($arr);

        $arr_result = $helper_hxt->pay($arr);
        //echo "<pre>";print_r($arr_result);echo "</pre>";exit;

        if (isset($arr_result['code']) && $arr_result['code'] == 0) {
            if (isset($arr_result['data']) && isset($arr_result['data']['ResultCode'])) {
                $arr_data = $arr_result['data'];
                $str_code = $arr_result['data']['ResultCode'];
                if ($str_code === 'G0' || $str_code === '30') {
                    echo json_encode(array('code' => 0, 'data' => $arr_data, 'msg' => '错误代码:202'));
                    return;
                } else {
                    echo json_encode(array('code' => 0, 'data' => $arr_data, 'msg' => '错误代码:203'));
                    return;
                }
            } else {
                echo json_encode(array('code' => 0, 'data' => $arr_data, 'msg' => '错误代码:201'));
                return;
            }
        } elseif (isset($arr_result['code']) && $arr_result['code'] == 1) {
            if (isset($arr_result['data']) && isset($arr_result['data']['ResultCode'])) {
                $arr_result_data = $arr_result['data'];
                $str_code = $arr_result_data['ResultCode'];
                if ($str_code === '00') {
                    echo json_encode(array('code' => 1, 'data' => $arr_data, 'msg' => ''));
                    return;
                } else {
                    echo json_encode(array('code' => 0, 'data' => $arr_data, 'msg' => '错误代码:212'));
                    return;
                }
            } else {
                echo json_encode(array('code' => 0, 'data' => $arr_data, 'msg' => '错误代码:211'));
                return;
            }
        } else {
            echo json_encode(array('code' => 0, 'data' => $arr_data, 'msg' => '错误代码:231'));
            return;
        }
    }




    /**
     * 接口-查询抄表信息
     *
     * Author zhengyu@iyangpin.com
     * post
     *   yhbh   用户编号
     *   ip     可选参数
     *
     * @return void
     */
    public function actionQuerycb()
    {
        $helper_hxt = new HxtHelper();

        $yhbh = RequestHelper::post('yhbh', '', 'trim');
        if ($yhbh == '') {
            echo json_encode(array('code' => 0, 'data' => array(), 'msg' => '缺少参数:用户编号'));
            return;
        }

        $arr = array();
        $arr['TerminalID'] = Yii::$app->params['hxt_TerminalID'];
        $arr['KeyID'] = Yii::$app->params['hxt_KeyID'];
        $arr['UserID'] = '';
        $arr['Account'] = '';
        $arr['EMail'] = '';
        $arr['CardNo'] = '';
        $arr['TotalFee'] = 0;
        $arr['ShopCode'] = '3102';
        $arr['PaymentInfo'] = $yhbh;
        $arr['IPAddress'] = RequestHelper::post('ip', '127.0.0.1', 'trim');
        $arr['Source'] = '';
        $arr['TraceNo'] = '';

        $arr['MCode'] = $this->_createQueryMcode($arr);

        $arr_result = $helper_hxt->query($arr);
        //print_r($arr_result);

        echo json_encode($arr_result);
        return;
    }

    /**
     * 接口-抄表缴费
     *
     * Author zhengyu@iyangpin.com
     * post
     *   userid 用户在本网站的id
     *   yhbh   用户编号
     *   money  缴费金额,单位：分
     *   ip     可选参数
     *
     * @return void
     */
    public function actionPaycb()
    {
        $helper_hxt = new HxtHelper();

        $yhbh = RequestHelper::post('yhbh', '', 'trim');
        if ($yhbh == '') {
            echo json_encode(array('code' => 0, 'data' => array(), 'msg' => '缺少参数:用户编号'));
            return;
        }
        $money = RequestHelper::post('money', 0, 'intval');
        if ($yhbh == 0) {
            echo json_encode(array('code' => 0, 'data' => array(), 'msg' => '缺少参数:缴费金额'));
            return;
        }
        $ip = RequestHelper::post('ip', '127.0.0.1', 'trim');

        //4100 查询交易
        $arr = array();
        $arr['TerminalID'] = Yii::$app->params['hxt_TerminalID'];
        $arr['KeyID'] = Yii::$app->params['hxt_KeyID'];
        $arr['UserID'] = '';
        $arr['Account'] = '';
        $arr['EMail'] = '';
        $arr['CardNo'] = '';
        $arr['TotalFee'] = $money;
        $arr['ShopCode'] = '4100';
        $arr['PaymentInfo'] = $yhbh;
        $arr['IPAddress'] = $ip;
        $arr['Source'] = '';
        $arr['TraceNo'] = '';

        $arr['MCode'] = $this->_createQueryMcode($arr);

        $arr_result = $helper_hxt->query($arr);
        if (isset($arr_result['code']) && $arr_result['code'] == 0) {
            echo json_encode(array('code' => 0, 'data' => array(), 'msg' => '错误代码:11'));
            return;
        }
        if (isset($arr_result['data']) && isset($arr_result['data']['PaymentInfo'])) {
            $arr_tmp = explode('$', $arr_result['data']['PaymentInfo']);
            $order_id = $arr_tmp[0];//中心流水号
        } else {
            echo json_encode(array('code' => 0, 'data' => array(), 'msg' => '错误代码:12'));
            return;
        }


        $arr_data = array(
            'yhbh' => $yhbh,
            'money' => $money,
            'orderid' => $order_id,
        );


        //4100 缴费
        $arr = array();
        $arr['TerminalID'] = Yii::$app->params['hxt_TerminalID'];
        $arr['KeyID'] = Yii::$app->params['hxt_KeyID'];
        $arr['UserID'] = '';
        $arr['Account'] = '';
        $arr['EMail'] = '';
        $arr['CardNo'] = '';
        $arr['SettlementDate'] = date("md", time());//银行清算日
        $arr['HostSerialNo'] = '999999999999';//银行扣费号
        $arr['TotalFee'] = $money;
        $arr['ShopCode'] = '4100';
        $arr['PaymentInfo'] = $yhbh . '$' . $order_id;
        $arr['IPAddress'] = $ip;
        $arr['Source'] = '';
        $arr['TraceNo'] = '';
        $arr['PromotionCode'] = '';

        $arr['MCode'] = $this->_createPayMcode($arr);

        $arr_result = $helper_hxt->pay($arr);

        if (isset($arr_result['code']) && $arr_result['code'] == 0) {
            echo json_encode(array('code' => 0, 'data' => $arr_data, 'msg' => '错误代码:21'));
            return;
        }
        if (isset($arr_result['data']) && isset($arr_result['data']['ResultCode'])) {
            $str_code = $arr_result['data']['ResultCode'];
            if ($str_code === '00') {
                echo json_encode(array('code' => 1, 'data' => $arr_data, 'msg' => ''));
                return;
            } elseif ($str_code === 'G0' || $str_code === '30') {
                echo json_encode(array('code' => 0, 'data' => $arr_data, 'msg' => '错误代码:23'));
                return;
            } else {
                echo json_encode(array('code' => 0, 'data' => $arr_data, 'msg' => '错误代码:24'));
                return;
            }
        } else {
            echo json_encode(array('code' => 0, 'data' => $arr_data, 'msg' => '错误代码:22'));
            return;
        }
    }








    /**
     * 查询接口
     *
     * Author zhengyu@iyangpin.com
     * echo数组格式 array(code=0/1,data=>array,msg=>xxx)
     *   code 0=失败 1=成功
     *
     * @return void
     */
    public function actionQuery()
    {
        $helper_hxt = new HxtHelper();

        $arr = array();
        $arr['TerminalID'] = Yii::$app->params['hxt_TerminalID'];
        $arr['KeyID'] = Yii::$app->params['hxt_KeyID'];
        $arr['UserID'] = '';
        $arr['Account'] = '';
        $arr['EMail'] = '';
        $arr['CardNo'] = '';
        $arr['TotalFee'] = RequestHelper::post('totalfee', '0', 'intval');
        $arr['ShopCode'] = RequestHelper::post('shopcode', '', 'trim');
        $arr['PaymentInfo'] = RequestHelper::post('paymentinfo', '', 'trim');
        $arr['IPAddress'] = RequestHelper::post('ip', '127.0.0.1', 'trim');
        $arr['Source'] = '';
        $arr['TraceNo'] = '';

        $arr['MCode'] = $this->_createQueryMcode($arr);


        $arr_result = $helper_hxt->query($arr);
        //print_r($arr_result);

        echo json_encode($arr_result);
        return;
    }


    /**
     * 缴费接口
     *
     * Author zhengyu@iyangpin.com
     *
     * @return void
     */
    public function actionPay()
    {
        $helper_hxt = new HxtHelper();

        $arr = array();
        $arr['TerminalID'] = Yii::$app->params['hxt_TerminalID'];
        $arr['KeyID'] = Yii::$app->params['hxt_KeyID'];
        $arr['UserID'] = '';
        $arr['Account'] = '';
        $arr['EMail'] = '';
        $arr['CardNo'] = '';
        $arr['SettlementDate'] = date("md", time());//银行清算日
        $arr['HostSerialNo'] = '999999999999';//银行扣费号
        $arr['TotalFee'] = RequestHelper::post('totalfee', '0', 'intval');
        $arr['ShopCode'] = RequestHelper::post('shopcode', '', 'trim');
        $arr['PaymentInfo'] = RequestHelper::post('paymentinfo', '', 'trim');
        $arr['IPAddress'] = RequestHelper::post('ip', '127.0.0.1', 'trim');
        $arr['Source'] = '';
        $arr['TraceNo'] = '';
        $arr['PromotionCode'] = '';


        $arr['MCode'] = $this->_createPayMcode($arr);

        $arr_result = $helper_hxt->pay($arr);
        //print_r($arr_result);

        echo json_encode($arr_result);
        return;
    }


    /**
     * 生成查询接口Mcode
     *
     * Author zhengyu@iyangpin.com
     *
     * @param array $arr 参数
     *
     * @return string 返回生成的签名
     */
    private function _createQueryMcode($arr)
    {
        $str1 = $arr['TerminalID'] . $arr['KeyID'] . $arr['UserID'] . $arr['Account'] . $arr['EMail']
            . $arr['CardNo'] . $arr['TotalFee'] . $arr['ShopCode'] . $arr['PaymentInfo'] . $arr['IPAddress']
            . $arr['Source'] . $arr['TraceNo'];
        $mackey = Yii::$app->params['hxt_MacKey'];
        $mcode = md5($str1 . md5($mackey));
        return $mcode;
    }

    /**
     * 生成缴费接口Mcode
     *
     * Author zhengyu@iyangpin.com
     *
     * @param array $arr 参数
     *
     * @return string 返回生成的签名
     */
    private function _createPayMcode($arr)
    {
        $str1 = $arr['TerminalID'] . $arr['KeyID'] . $arr['UserID'] . $arr['Account']
            . $arr['EMail'] . $arr['CardNo'] . $arr['SettlementDate'] . $arr['HostSerialNo']
            . $arr['TotalFee'] . $arr['ShopCode'] . $arr['PaymentInfo'] . $arr['IPAddress']
            . $arr['Source'] . $arr['TraceNo']. $arr['PromotionCode'];
        $mackey = Yii::$app->params['hxt_MacKey'];
        $mcode = md5($str1 . md5($mackey));
        return $mcode;
    }

}
