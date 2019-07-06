<?php
/**
 * 恒信通相关
 *
 * PHP Version 5
 *
 * @category  SOCIAL
 * @package   HELPER
 * @author    zhengyu <zhengyu@iyangpin.com>
 * @time      15/8/25 10:19
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      zhengyu@iyangpin.com
 */


namespace common\helpers;

use Yii;


/**
 * 恒信通相关
 *
 * @category SOCIAL
 * @package  HELPER
 * @author   zhengyu <zhengyu@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     zhengyu@iyangpin.com
 */
class HxtHelper
{
    //private $_default_arr_where = array();
    //private $_default_str_andwhere = '';
    //private $_default_arr_order = array();
    //private $_default_str_field = '*';
    //private $_default_int_offset = -1;
    //private $_default_int_limit = -1;

    private $_switch_log = 1;//1=开启 0=关闭

    /**
     * 构造函数
     *
     * Author zhengyu@iyangpin.com
     */
    public function __construct()
    {
        //$this->_sep1 = '----------';
        //$this->_sep2 = $this->_sep1 . $this->_sep1;
        //$this->_sep4 = $this->_sep2 . $this->_sep2;

        $this->service = Yii::$app->params['hxt_soap_url'];
    }

    /**
     * 查询接口
     *
     * Author zhengyu@iyangpin.com
     *
     * @param array $arr 接口所需参数
     *
     * @return array array(code=0/1,data=>array,msg=>xxx)  code 0=失败 1=成功
     */
    public function query($arr)
    {
        $client = new \SoapClient($this->service);

        $client->soap_defencoding = 'utf-8';
        $client->decode_utf8 = false;
        $client->xml_encoding = 'utf-8';

        $param = $arr;
        $this->_zlog(json_encode($param), 'hxt_log');
        //echo "<pre>";print_r($param);echo "</pre>";exit;

        $result = $client->__soapCall("HXTServiceQuery", array($param));

        if (is_soap_fault($result)) {
            $str = "[SOAP Fault],faultcode=" . $result->faultcode . ",faultstring=" . $result->faultstring;
            $this->_zlog($str, 'soap_exception');

            return array('code' => 0, 'data' => array(), 'msg' => 'SOAP Fault');
        } else {
            //var_dump($result);exit;
            if (!isset($result->HXTServiceQueryResult->any)) {
                $str = "[SOAP error],no result->HXTServiceQueryResult->any";
                $this->_zlog($str . "\n" . var_dump($result), 'soap_exception');
                return array('code' => 0, 'data' => array(), 'msg' => 'SOAP return error');
            }
            $xml = $result->HXTServiceQueryResult->any;
            $this->_zlog($xml, 'hxt_log');
            $arr_data = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

            if (isset($arr_data['PaymentOrderID'])) {
                unset($arr_data['PaymentOrderID']);
            }
            if (isset($arr_data['MCode'])) {
                unset($arr_data['MCode']);
            }

            if (isset($arr_data['ResultCode']) && $arr_data['ResultCode'] == '00') {
                return array('code' => 1, 'data' => $arr_data, 'msg' => '');
            } else {
                $this->_zlog(json_encode($arr_data));
                return array('code' => 0, 'data' => $arr_data, 'msg' => '');
            }
        }
    }



    /**
     * 缴费接口
     *
     * Author zhengyu@iyangpin.com
     *
     * @param array $arr 接口所需参数
     *
     * @return array array(code=0/1,data=>array,msg=>xxx)  code 0=失败 1=成功
     */
    public function pay($arr)
    {
        $client = new \SoapClient($this->service);

        $client->soap_defencoding = 'utf-8';
        $client->decode_utf8 = false;
        $client->xml_encoding = 'utf-8';

        $param = $arr;
        $this->_zlog(json_encode($param), 'hxt_log');
        //echo "<pre>";print_r($param);echo "</pre>";exit;

        $result = $client->__soapCall("HXTServicePay", array($param));

        if (is_soap_fault($result)) {
            $str = "[SOAP Fault],faultcode=" . $result->faultcode . ",faultstring=" . $result->faultstring;
            $this->_zlog($str, 'soap_exception');

            return array('code' => 0, 'data' => array(), 'msg' => 'SOAP Fault');
        } else {
            //var_dump($result);exit;
            if (!isset($result->HXTServicePayResult->any)) {
                $str = "[SOAP error],no result->HXTServicePayResult->any";
                $this->_zlog($str . "\n" . var_dump($result), 'soap_exception');
                return array('code' => 0, 'data' => array(), 'msg' => 'SOAP return error');
            }
            $xml = $result->HXTServicePayResult->any;
            $this->_zlog($xml, 'hxt_log');
            $arr_data = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            //echo "<pre>";print_r($arr_data);echo "</pre>";exit;

            if (isset($arr_data['PaymentOrderID'])) {
                unset($arr_data['PaymentOrderID']);
            }
            if (isset($arr_data['MCode'])) {
                unset($arr_data['MCode']);
            }

            if (isset($arr_data['ResultCode']) && $arr_data['ResultCode'] == '00') {
                return array('code' => 1, 'data' => $arr_data, 'msg' => '');
            } else {
                $this->_zlog(json_encode($arr_data));
                return array('code' => 0, 'data' => $arr_data, 'msg' => '');
            }
        }
    }


    /**
     * 记录日志
     *
     * Author zhengyu@iyangpin.com
     *
     * @param string $str  内容
     * @param string $type 类型
     *
     * @return void
     */
    private function _zlog($str = '', $type = '')
    {
        if ($this->_switch_log !== 1) {
            return;
        }

        $str_today = date("Ymd", time());

        $content = date("H:i:s ") . $str . "\n\n";

        if ($type == '') {
            //记录返回值非成功记录
            $log_dir = "/tmp/hxtlog";
            if (!is_dir($log_dir)) {
                @mkdir($log_dir, 0700);
            }
            file_put_contents($log_dir . '/tmp_hxt_error_' . $str_today, $content, FILE_APPEND);
        } elseif ($type == 'soap_exception') {
            //记录soap返回异常
            $log_dir = "/tmp/zlog";
            if (!is_dir($log_dir)) {
                @mkdir($log_dir, 0700);
            }
            $content = date("Y-m-d_H:i:s ") . $str;
            file_put_contents($log_dir . '/tmp_soap_exception', $content, FILE_APPEND);
        } elseif ($type == 'hxt_log') {
            //记录和恒信通接口的全部发送、返回
            $log_dir = "/tmp/hxtlog";
            if (!is_dir($log_dir)) {
                @mkdir($log_dir, 0700);
            }
            file_put_contents($log_dir . '/not_tmp_hxt_log_' . $str_today, $content, FILE_APPEND);
        } else {
        }

        return;
    }








}
