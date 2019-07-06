<?php
/**
 * 露脸(http://www.v5.cn)
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   loulian
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      2015/8/12
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */

namespace common\helpers;

use linslin\yii2\curl\Curl;

/**
 * loulianSDK
 *
 * @category Social
 * @package  loulian
 * @author   liuyanwei <liuyanwei@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     liuyanwei@i500m.com
 */
class LouLianHelper
{
     /**
     * 露脸注册接口
     * @param string $mobile   手机号
     * @param string $password 密码
     * @param string $nickname 昵称
     * @return array
     */
    public static function llRegister($mobile = '', $nickname = '', $avatar = '')
    {
        if (Common::C('openLouLian')) {
            $token = self::token();
            $Authorization = "Bearer " . $token['access_token'];
            $header[] = 'Authorization: ' . $Authorization;
            $url = Common::C('llUsersAPI').'?app_user_id='.$mobile.'&app_user_nick_name='.$nickname.'&avatar='.$avatar;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header); 
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
            $output = curl_exec($curl);
            curl_close($curl);
            return json_decode($output,true);
        } else {
            return [];
        }
    }

    /**
     * 更新用户信息
     * @param string $mobile   手机号
     * @param string $password 密码
     * @param string $nickname 昵称
     * @return array
     */
    public static function updateUserInfo($mobile, $nickname = '', $avatar = '')
    {
        if (Common::C('openLouLian')) {
            $token = self::token();
            $Authorization = "Bearer " . $token['access_token'];
            $header[] = 'Authorization: ' . $Authorization;
            $url = Common::C('llUsersUpdateAPI').'?app_user_id='.$mobile.'&app_user_nick_name='.$nickname.'&avatar='.$avatar;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header); 
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
            $output = curl_exec($curl);
            curl_close($curl);
            return json_decode($output,true);
        } else {
            return [];
        }
    }

    /**
     * 创建群组
     * @return array
     * @author    xuxiaoyu <huangdekui@i500m.com>
     * @link      xuxiaoyu@i500m.com
     */
    public static function CreateGroup($mobile,$member,$name,$desc='')
    {
        if(Common::C('openLouLian')){
            $token = self::token();
            $Authorization = "Bearer " . $token['access_token'];
            $header[] = 'Authorization: ' . $Authorization;
            //$header[] = 'app-id:'.Common::C('llClientID');
            $url = Common::C('llURL').'/open/api/group/create';
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt ($curl, CURLOPT_POSTFIELDS, array('owner'=>$mobile,'member'=>$member,'name'=>$name,'desc'=>$desc));
            $output = curl_exec($curl);
            curl_close($curl);
            return json_decode($output,true);
        }else{
            return [];
        }
    }

     /**
     * 更新群组
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */
    public static function UpdateGroup($group_id,$name,$desc='')
    {
        if(Common::C('openLouLian')){
            $token = self::token();
            $Authorization = "Bearer " . $token['access_token'];
            $header[] = 'Authorization: ' . $Authorization;
            //$header[] = 'app-id:'.Common::C('llClientID');
            $url = Common::C('llURL').'/open/api/group/update';
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt ($curl, CURLOPT_POSTFIELDS, array('group_id'=>$group_id,'name'=>$name,'desc'=>$desc));
            $output = curl_exec($curl);
            curl_close($curl);
            return json_decode($output,true);
        }else{
            return [];
        }
    }

    /**
     * 获取群组信息
     * @param string $mobile   手机号
     * @param string $password 密码
     * @param string $nickname 昵称
     * @return array
     */
    public static function GetGroup($group_id, $detail = '')
    {
        if (Common::C('openLouLian')) {
            $token = self::token();
            $Authorization = "Bearer " . $token['access_token'];
            $header[] = 'Authorization: ' . $Authorization;
            $url = Common::C('llURL').'/open/api/group/get?group_id='.$group_id.'&detail='.$detail;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header); 
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
            $output = curl_exec($curl);
            curl_close($curl);
            return json_decode($output,true);
        } else {
            return [];
        }
    }

    /**
     * 加入群组
     * @return array
     * @author    xuxiaoyu <huangdekui@i500m.com>
     * @link      xuxiaoyu@i500m.com
     */
    public static function InsertGroup($group_id,$member){
        if(Common::C('openLouLian')){
            $token = self::token();
            $Authorization = "Bearer " . $token['access_token'];
            $header[] = 'Authorization: ' . $Authorization;
            $url = Common::C('llURL').'open/api/group/join';
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt ($curl, CURLOPT_POSTFIELDS, array('group_id'=>$group_id,'member'=>$member));
            $output = curl_exec($curl);
            curl_close($curl);
            return json_decode($output,true);
        }else{
            return [];
        }
    }


    /**
     * 退出群组
     * @return array
     * @author    xuxiaoyu <xuxiaoyu@i500m.com>
     * @link      xuxiaoyu@i500m.com
     */
    public static function DeleteGroup($group_id,$member){
        if(Common::C('openLouLian')){
            $token = self::token();
            $Authorization = "Bearer " . $token['access_token'];
            $header[] = 'Authorization: ' . $Authorization;
            $url = Common::C('llURL').'open/api/group/remove';
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt ($curl, CURLOPT_POSTFIELDS, array('group_id'=>$group_id,'member'=>$member));
            $output = curl_exec($curl);
            curl_close($curl);
            return json_decode($output,true);
        }else{
            return [];
        }
    }

    /**
     * 解散群组
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */
    public static function ExitGroup($group_id){
        if(Common::C('openLouLian')){
            $token = self::token();
            $Authorization = "Bearer " . $token['access_token'];
            $header[] = 'Authorization: ' . $Authorization;
            $url = Common::C('llURL').'open/api/group/exit';
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt ($curl, CURLOPT_POSTFIELDS, array('group_id'=>$group_id));
            $output = curl_exec($curl);
            curl_close($curl);
            return json_decode($output,true);
        }else{
            return [];
        }
    }

    /**
     * Token
     * @return array
     */
    public static function token()
    {
        $url = Common::C('llTokenAPI');
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'client_id='.Common::C('llClientID').'&client_secret='.Common::C('llClientSecret').'&grant_type=client_credentials');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output,true);
    }
}

