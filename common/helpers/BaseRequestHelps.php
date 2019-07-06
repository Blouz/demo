<?php
/**
 * BaseRequest
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   BaseRequest
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/12
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */

namespace common\helpers;

/**
 * BaseRequest
 *
 * @category Social
 * @package  BaseRequest
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class BaseRequestHelps
{

    /**
     * Get
     * @param string $name    参数名
     * @param string $default 默认值
     * @param null   $filter  过滤方法
     * @return array|mixed|null|string
     */
    public static function get($name = '', $default = '', $filter = null)
    {
        return self::getParams($name, $default, $filter, $_GET);
    }

    /**
     * Post
     * @param string $name    参数名
     * @param string $default 默认值
     * @param null   $filter  过滤方法
     * @return array|mixed|null|string
     */
    public static function post($name = '', $default = '', $filter = null)
    {
        return self::getParams($name, $default, $filter, $_POST);
    }

    /**
     * Put
     * @param string $name    参数名
     * @param string $default 默认值
     * @param null   $filter  过滤方法
     * @return array|mixed|null|string
     */
    public static function put($name = '', $default = '', $filter = null)
    {
        static $_PUT = null;
        if (is_null($_PUT)) {
            parse_str(file_get_contents('php://input'), $_PUT);
        }
        return self::getParams($name, $default, $filter, $_PUT);
    }

    /**
     * 获取方法
     * @return string
     */
    public static function getMethod()
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $input = 'POST';
                break;
            case 'PUT':
                $input = 'PUT';
                break;
            default:
                $input = 'GET';
        }
        return $input;
    }

    /**
     * 获取参数
     * @param string $name    参数名称
     * @param string $default 默认值
     * @param null   $filter  过滤方法
     * @param null   $input   Input
     * @return array|mixed|null|string
     */
    public static function getParams($name='', $default = '', $filter = null, $input = null)
    {
        $filters    =   isset($filter) ? $filter : 'htmlspecialchars';
        $filters    =   !empty($filters) ? $filters : 'htmlspecialchars';
        //@todo 20151022 取消验证，因为会出现验签失败。
        //$filters    .= ',removeXSS,abacaAddslashes';
        if ('' == $name) {
            $data       =   $input;
            if ($filters) {
                if (is_string($filters)) {
                    $filters    =   explode(',', $filters);
                }
                foreach ($filters as $filter) {
                    $data   =   self::array_map_recursive($filter, $data); // 参数过滤
                }
            }
        } elseif (isset($input[$name])) { // 取值操作
            $data       =   $input[$name];
            if ($filters) {
                if (is_string($filters)) {
                    $filters    =   explode(',', $filters);
                }
                if (is_array($filters)) {
                    foreach ($filters as $filter) {
                        if (function_exists($filter)) {
                            $data = is_array($data) ? self::array_map_recursive($filter, $data) : $filter($data);
                        } else {
                            $data = is_array($data) ? self::array_map_recursive($filter, $data) : self::$filter($data);
                        }
                    }
                }
            }
        } else { // 变量默认值
            $data = isset($default) ? $default : '';
        }
        if ($data == null || $data == 'null' || $data == 'NULL') {
            $data = $default;
        }
        return $data;
    }

    /**
     * 递归
     * @param string $filter 过滤方法
     * @param null   $data   数据
     * @return array
     */
    public static function array_map_recursive($filter = '', $data)
    {
        $result = array();
        foreach ($data as $key => $val) {
            if (function_exists($filter)) {
                $result[$key] = is_array($val) ? self::array_map_recursive($filter, $val) : $filter($val);
            } else {
                $result[$key] = is_array($val) ? self::array_map_recursive($filter, $val) : self::$filter($val);
            }
        }
        return $result;
    }

    /**
     * 移除XSS
     * @param string $val 值
     * @return mixed
     */
    public static function removeXSS($val = '')
    {
        //加上这行代码就把 , 给过滤啦，所以注释掉啦。
        //$val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);
        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';
        for ($i = 0; $i < strlen($search); $i++) {
            $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val);
            $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val);
        }
        $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
        $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
        $ra = array_merge($ra1, $ra2);
        $found = true;
        while ($found == true) {
            $val_before = $val;
            for ($i = 0; $i < sizeof($ra); $i++) {
                $pattern = '/';
                for ($j = 0; $j < strlen($ra[$i]); $j++) {
                    if ($j > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                        $pattern .= '|';
                        $pattern .= '|(&#0{0,8}([9|10|13]);)';
                        $pattern .= ')*';
                    }
                    $pattern .= $ra[$i][$j];
                }
                $pattern .= '/i';
                $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2);
                $val = preg_replace($pattern, $replacement, $val);
                if ($val_before == $val) {
                    $found = false;
                }
            }
        }
        return $val;
    }

    /**
     * SQl 防注入
     * @param string $var 值
     * @return array|string
     */
    public static function abacaAddslashes($var = '')
    {
        if (!get_magic_quotes_gpc()) {
            if (is_array($var)) {
                foreach ($var as $key => $val) {
                    $var[$key] = self::abacaAddslashes($val);
                }
            } else {
                $var = addslashes($var);
            }
        }
        return $var;
    }
}
