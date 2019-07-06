<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\caching;

use SSDB\SimpleClient;
use yii\base\InvalidConfigException;
use Yii;

/**
 * Ssdb
 *
 * @category Yii
 * @package  Ssdb
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class SsdbCache extends Cache
{
    public  $_cache;

    private $_servers = [];

    protected $_keyPre  = 'SOCIAL_API_';

    /**
     * 初始化
     * @return array
     */
    public function init()
    {
        parent::init();
    }

    /**
     * 获取 SSDB 对象
     * @return mixed
     * @throws InvalidConfigException
     */
    public function getSsdbCache()
    {
        if ($this->_cache !== null) {
            return $this->_cache;
        } else {
            $servers = $this->getServers();
            foreach ($servers as $server) {
                if ($server['host'] === null) {
                    throw new InvalidConfigException("The 'host' property must be specified for every ssdb server.");
                }
                if ($server['port'] === null) {
                    throw new InvalidConfigException("The 'port' property must be specified for every ssdb server.");
                }
                if ($server['auth'] === null) {
                    throw new InvalidConfigException("The 'auth' property must be specified for every ssdb server.");
                }
                $timeout = empty($server['timeout']) ? 2000 : $server['timeout'] ;
                $this->_keyPre = $server['keyPrefix'];
                $this->_cache = new SimpleClient($server['host'], $server['port'], $timeout);
                $this->_cache->auth($server['auth']);
            }
            return $this->_cache;
        }
    }

    /**
     * 获取Keys
     * @return mixed
     * @throws InvalidConfigException
     */
    public function getkeys()
    {
        return $this->getSsdbCache()->keys($this->_keyPre,'',1000);
        //return $this->getSsdbCache()->hkeys($this->_keyPre, "", "", $this->getSsdbCache()->hsize($this->_keyPre));
    }

    /**
     * 获取对象
     * @param string $key 键
     * @return mixed
     * @throws InvalidConfigException
     */
    public function getValue($key)
    {
        $key = $this->_keyPre.$key;
        return unserialize($this->getSsdbCache()->get($key));
    }

    /**
     * 缓存值
     * @param string $key    键
     * @param string $value  值
     * @param int    $expire 过期时间
     * @return mixed
     * @throws InvalidConfigException
     */
    public function setValue($key, $value, $expire)
    {
        $key = $this->_keyPre.$key;
        $this->getSsdbCache()->hset($this->_keyPre, $key, 1);
        if ($expire > 0) {
            //$expire += time();
            return $this->getSsdbCache()->setx($key, serialize($value), (int) $expire);
        } else {
            return $this->getSsdbCache()->set($key, serialize($value));
        }
    }

    /**
     * 设置值
     * @param string $key    键
     * @param string $value  值
     * @param int    $expire 过期时间
     * @return mixed
     */
    public function addValue($key, $value, $expire)
    {
        $key = $this->_keyPre.$key;
        return $this->setValue($key, $value, $expire);
    }

    /**
     * 删除值
     * @param string $key 键
     * @return mixed
     * @throws InvalidConfigException
     */
    public function deleteValue($key)
    {
        $key = $this->_keyPre.$key;
        $this->getSsdbCache()->hdel($key);
        return $this->getSsdbCache()->del($key);
    }

    /**
     * 清空值
     * @return mixed
     * @throws InvalidConfigException
     */
    public function flushValues()
    {
        $this->getSsdbCache()->multi_del($this->getkeys());
        return $this->getSsdbCache()->hclear($this->_keyPre);
    }

    /**
     * 获取Servers
     * @return array
     */
    public function getServers()
    {
        return $this->_servers;
    }

    /**
     * 设置
     * @param array $config 配置
     * @return array
     */
    public function setServers($config)
    {
        $this->_servers = $config;
    }
}
