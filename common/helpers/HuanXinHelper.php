<?php
/**
 * 环信SDK(https://console.easemob.com)
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   HuanXin
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/12
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */

namespace common\helpers;

use linslin\yii2\curl\Curl;

/**
 * HuanXinSDK
 *
 * @category Social
 * @package  HuanXin
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class HuanXinHelper
{
    /**
     * 环信登陆方法
     * @param string $mobile   用户名
     * @param string $password 密码
     * @return array
     */
    public static function hxLogin($mobile = '', $password = '')
    {
        if (Common::C('openHuanXin')) {
            $post_data['username'] = $mobile;
            $post_data['password'] = $password;
            $post_data['grant_type'] = "password";
            $data = json_encode($post_data);
            $curl = new Curl();
            $response = $curl->setOption(CURLOPT_POSTFIELDS, $data)->post(Common::C('hxTokenAPI'));
            $list = json_decode($response, 1);
            if ($list) {
                return $list;
            } else {
                return 0;
            }
        } else {
            return [];
        }
    }

    /**
     * 环信注册接口
     * @param string $mobile   手机号
     * @param string $password 密码
     * @param string $nickname 昵称
     * @return array
     */
    public static function hxRegister($mobile = '', $password = '', $nickname = '')
    {
        if (Common::C('openHuanXin')) {
            $post_data['username'] = $mobile;
            $post_data['password'] = $password;
            $post_data['nickname'] = $nickname;
            $data = json_encode($post_data);
            $curl = new Curl();
            $response = $curl->setOption(CURLOPT_POSTFIELDS, $data)->post(Common::C('hxUsersAPI'));
            $list = json_decode($response, true);
            if (!empty($list['entities'])) {
                //注册并且添加好友
                if (Common::C('regAddFriendCustomerService')) {
                    $rs = self::hxAddFriend($mobile, Common::C('customerServiceUserName'));
                    if (!empty($rs)) {
                        return $rs;
                    } else {
                        return ['code'=>'101'];
                    }
                } else {
                    return $list;
                }
            } else {
                return 0;
            }
        } else {
            return [];
        }
    }

    /**
     * 环信修改昵称
     * @param string $username 手机号
     * @param string $nickname 昵称
     * @return array
     */
    public static function hxModifyNickName($username = '', $nickname = '')
    {
        if (Common::C('openHuanXin')) {
            $post_data['nickname'] = $nickname;
            $data = json_encode($post_data);
            $token = self::token();
            $Authorization = "Bearer " . $token['access_token'];
            $header[] = 'Authorization: ' . $Authorization;
            $curl = new Curl();
            $response = $curl
                ->setOption(CURLOPT_HTTPHEADER, $header)
                ->setOption(CURLOPT_POSTFIELDS, $data)
                ->put(Common::C('hxUsersAPI') . $username);
            $list = json_decode($response, true);
            if ($list) {
                return 200;
            } else {
                return 101;
            }
        } else {
            return [];
        }
    }

    /**
     * 添加好友
     * @param string $username        用户名
     * @param string $friend_username 好友用户名
     * @return array
     */
    public static function hxAddFriend($username = '', $friend_username='')
    {
        if (Common::C('openHuanXin')) {
            $token = self::token();
            $Authorization = "Bearer " . $token['access_token'];
            $header[] = 'Authorization: ' . $Authorization;
            $curl = new Curl();
            $response = $curl
                ->setOption(CURLOPT_HTTPHEADER, $header)
                ->POST(Common::C('hxUsersAPI') . $username.'/contacts/users/'.$friend_username);
            $list = json_decode($response, true);
            return $list;
        } else {
            return [];
        }
    }

    /**
     * 获取用户状态
     * @param string $username 用户名
     * @return int
     */
    public static function userStatus($username='')
    {
        $token = self::token();
        $Authorization= "Bearer ".$token['access_token'];
        $header[] = 'Authorization: '.$Authorization;
        $curl = new Curl();
        $response = $curl->setOption(CURLOPT_HTTPHEADER, $header, false)->get(Common::C('hxUsersAPI').$username);
        $list = json_decode($response, 1);
        if ($list) {
            return 200;
        } else {
            return 101;
        }
    }

    /**
     * Token
     * @return array
     */
    public static function token()
    {
        $post_data['grant_type'] = "client_credentials";
        $post_data['client_id'] = Common::C('hxClientID');
        $post_data['client_secret'] = Common::C('hxClientSecret');
        $data = json_encode($post_data);
        $curl = new Curl();
        $response = $curl->setOption(CURLOPT_POSTFIELDS, $data)->post(Common::C('hxTokenAPI'));
        return json_decode($response, 1);
    }
}
