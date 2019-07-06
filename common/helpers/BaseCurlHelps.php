<?php
/**
 * CURL 请求
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   CURL
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/10
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace common\helpers;

use linslin\yii2\curl\Curl;
use yii\helpers\ArrayHelper;

/**
 * CURL 请求
 *
 * @category Social
 * @package  CURL
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class BaseCurlHelps
{

    /**
     * Post 请求
     * @param string $url  URl
     * @param array  $post 传递的值
     * @param bool   $sign 是否开启签名
     * @return mixed
     */
    public static function post($url = '', $post = array(), $sign = false)
    {
        if ($sign) {
            $appId     = Common::C('appId');
            $APP_CODE  = Common::C('APP_CODE');
            $app_key   = $APP_CODE[$appId];
            $timestamp = time();
            $sign = self::_createSign($app_key, $timestamp, $post);
            $post['appId']      = $appId;
            $post['timestamp']  = $timestamp;
            $post['sign']       = $sign;
        }
        $curl = new Curl();
        $response = $curl->reset()
            ->setOption(CURLOPT_POSTFIELDS, http_build_query($post))->post($url);
        $response = json_decode($response, true);
        return $response;
    }

    /**
     * Get 请求
     * @param string $url  URL
     * @param bool   $sign 是否开启签名
     * @return array
     */
    public static function get($url = '', $sign = false)
    {
        if ($sign) {
            $appId     = Common::C('appId');
            $APP_CODE  = Common::C('APP_CODE');
            $app_key   = $APP_CODE[$appId];
            $timestamp = time();
            $data = array();
            $ex = explode('?', $url);
            if (isset($ex[1])) {
                $param = explode('&', $ex[1]);
                $data = array();
                foreach ($param as $k => $v) {
                    $ex_v = explode('=', $v);
                    $data[$ex_v[0]] = $ex_v[1];
                }
            }
            $sign = self::_createSign($app_key, $timestamp, $data);
            if (stripos($url, '?') === false) {
                $url .= '?appId=' . $appId . '&timestamp=' . $timestamp . '&sign=' . $sign;
            } else {
                $url .= '&appId=' . $appId . '&timestamp=' . $timestamp . '&sign=' . $sign;
            }
        }
        $curl = new Curl();
        $response = $curl->get($url);
        $response = json_decode($response, true);
        return $response;
    }

    /**
     * 创建签名的方法
     * @param string $app_key   APP_KEY
     * @param string $timestamp 时间戳
     * @param array  $data      数据
     * @return string
     */
    private static function _createSign($app_key = '', $timestamp = '', $data = array())
    {
        $val = '';
        if ($data) {
            krsort($data);
            foreach ($data as $k => $v) {
                $val .= $v;
            }
        }
        return md5(md5(md5($app_key.$timestamp).md5($timestamp)).md5($val));
    }
}
