<?php
/**
 * SSDB帮助类
 *
 * PHP Version 5
 *
 * @category  SHOP
 * @package   SSDB
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/6/10 14:37
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace common\helpers;

/**
 * Ssdb
 *
 * @category Social
 * @package  Ssdb
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class SsdbHelper
{
    /**
     * 缓存操作
     * @param string $op    操作
     * @param string $key   键
     * @param string $value 值
     * @param int    $ttl   过期时间
     * @return bool|string
     */
    public static function Cache($op='set', $key='', $value='', $ttl=0)
    {
        if (Common::C('openSSDB')) {
            $log_arr['op']    = $op;
            $log_arr['key']   = $key;
            $log_arr['value'] = $value;
            $log_arr['ttl']   = $ttl;
            self::_saveCacheLog(Common::C('ssdbLogFile'), var_export($log_arr, true));
            switch ($op) {
                case 'set':
                    return \Yii::$app->cache->setValue($key, $value, $ttl);
                    break;
                case 'get':
                    return \Yii::$app->cache->getValue($key);
                    break;
                case 'del':
                    return \Yii::$app->cache->deleteValue($key);
                    break;
                case 'keys':
                    return \Yii::$app->cache->getkeys();
                    break;
                default:
                    return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 开启缓存日志
     * @param string $path 路径
     * @param string $data 数据
     * @return bool
     */
    private function _saveCacheLog($path = '', $data = '')
    {
        if (Common::C('openCacheLog')) {
            file_put_contents($path, "执行时间：" . date('Y-m-d H:i:s') . " 数据：" . var_export($data, true) . "\n", FILE_APPEND);
        }
    }
}
