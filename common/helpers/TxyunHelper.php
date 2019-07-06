<?php

/* 
 * 
 * @category  Social
 * @package   Post
 * @author    wangleilei <wangleilei@i500m.com>
 * @time      2017
 * @copyright 2017 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wangleilei@i500m.com
 */

namespace common\helpers;

class TxyunHelper
{
    //正式
    public static $user_sig = 'eJxlj81qg0AYRfc*hbhNqd*MP2ihi6RIUCNpU0ublUycMXwY-ybTVCl597YmEKF3e87lcr81XdeNdPV6z-K8*axVpoZWGPqDboBxd4NtizxjKrMk-wdF36IUGSuUkCMkjuNQgKmDXNQKC7wajFdYT-CRl9m4cenbv2Xq*eBNFdyPMAnensKl3EdVFx42KUtm9DkNGiy6QSbbXbt2iceinS*hzOViFs0xmJNtP5D4YGL3pRRfbVwag9cnp8Fe*y*Lyoxr*z00P3yyLB8nkworcT0EHgXLBXdCT0IesalHgQJxCLXgL4Z21n4AAjhcfg__';
    //测试
    //public static $user_sig = 'eJxlj0FPgzAAhe-8CsJ1RtpCS9gNCzNTMAE2Il4I0uLqXEGoymL87ypbYhPf9fteXt6nYZqmtYnzy7ppujepKnXsuWUuTQtYF3*w7wWralU5A-sH*dSLgVd1q-gwQ4gxRgDojmBcKtGKs1Gzg5AaHtm*mjdOffenjDyPEF0RTzNMopKu0-DDeYY8zAbnOntMPTKhbozQPS5glDXJA*VwtU8nxw8JDda7IEZbu90CSu1NfHUXjK8F2*WoXGW3L2UyAftmUcgw8UW*KLVJJQ78fAgQgpHruxp958MoOjkLCEAMkQN*Yxlfxjf9blqU';
    
    public static function Regsiter($mobile,$nick,$avatar)
    {
        $uri = Common::C('TxYunURL');
        $user_sig = self::$user_sig;
        $identifier = Common::C('identifier');
        $sdkappid = Common::C('sdkappid');
        $private_key_path = Common::C('private_key_path');
        $generator = Common::C('generator');
//        $user_sig = sig::signature($mobile,$sdkappid,$private_key_path,$generator);
        $link = "im_open_login_svc/account_import?usersig=".$user_sig."&apn=1&identifier=".$identifier."&sdkappid=".$sdkappid."&contenttype=json";
        $url = $uri.$link;
        $data = array();
        $data['Identifier'] = $mobile;
        $data['Nick'] = $nick;
        $data['FaceUrl'] = $avatar;
        $json_data = json_encode($data,TRUE);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1 );
//        curl_setopt($ch, CURLOPT_HEADER, 0 );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data );
        
        $res = curl_exec($ch);
        curl_close ( $ch );
//        $data = array();
//        $data[] = json_decode($res,true);
//        $data[] = $user_sig[0];
        return $res;
    }
    public static function Create_group($mobile,$type,$group_name,$face_url = NULL)
    {
        $uri = Common::C('TxYunURL');
        $user_sig = self::$user_sig;
        $identifier = Common::C('identifier');
        $sdkappid = Common::C('sdkappid');
        
        $link = "group_open_http_svc/create_group?usersig=".$user_sig."&apn=1&identifier=".$identifier."&sdkappid=".$sdkappid."&contenttype=json";
        $url = $uri.$link;
        $data = array();
        $data['Owner_Account'] = $mobile;
        $data['Type'] = $type;
        $data['Name'] = $group_name;
        $data['FaceUrl'] = $face_url;
        $json_data = json_encode($data,TRUE);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1 );
//        curl_setopt($ch, CURLOPT_HEADER, 0 );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data );
        
        $res = curl_exec($ch);
        curl_close ( $ch );
        return $res;
    }
    public static function Update_group($group_id,$group_name,$introduction,$notification,$face,$maxnum,$apply_join_option)
    {
        $uri = Common::C('TxYunURL');
        $user_sig = self::$user_sig;
        $identifier = Common::C('identifier');
        $sdkappid = Common::C('sdkappid');
        $link = "group_open_http_svc/modify_group_base_info?usersig=".$user_sig."&apn=1&identifier=".$identifier."&sdkappid=".$sdkappid."&contenttype=json";
        $url = $uri.$link;
        $data = array();
        $data['GroupId'] = $group_id;
        if(!empty($group_name))
        {
            $data['Name'] = $group_name;
        }
        if(!empty($introduction))
        {
            $data['Introduction'] = $introduction;
        }
        if(!empty($notification))
        {
            $data['Notification'] = $notification;
        }
        if(!empty($face))
        {
            $data['FaceUrl'] = $face;
        }
        if(!empty($maxnum))
        {
            $data['MaxMemberNum'] = $maxnum;
        }
        if(!empty($apply_join_option))
        {
            $data['ApplyJoinOption'] = $apply_join_option;
        }
        $json_data = json_encode($data,TRUE);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1 );
//        curl_setopt($ch, CURLOPT_HEADER, 0 );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data );
        
        $res = curl_exec($ch);
        curl_close ( $ch );
        return $res;
    }
    //加入群
    public static function Join_group($group_id,$users)
    {
        $uri = Common::C('TxYunURL');
        $user_sig = self::$user_sig;
        $identifier = Common::C('identifier');
        $sdkappid = Common::C('sdkappid');
        $link = "group_open_http_svc/add_group_member?usersig=".$user_sig."&apn=1&identifier=".$identifier."&sdkappid=".$sdkappid."&contenttype=json";
        $url = $uri.$link;
        $data = array();
        $data['GroupId'] = $group_id;
        $member = array();
        for($i=0;$i<count($users);$i++)
        {
            $member[]['Member_Account'] = $users[$i];
        }
        $data['MemberList'] = $member;

        $json_data = json_encode($data,TRUE);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1 );
//        curl_setopt($ch, CURLOPT_HEADER, 0 );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data );
        
        $res = curl_exec($ch);
        curl_close ( $ch );
        return $res;
    }
    //退群
    public static function Exit_group($group_id,$users)
    {
        $uri = Common::C('TxYunURL');
        $user_sig = self::$user_sig;
        $identifier = Common::C('identifier');
        $sdkappid = Common::C('sdkappid');
        $link = "group_open_http_svc/delete_group_member?usersig=".$user_sig."&apn=1&identifier=".$identifier."&sdkappid=".$sdkappid."&contenttype=json";
        $url = $uri.$link;
        $data = array();
        $data['GroupId'] = $group_id;
        $member = array();
        for($i=0;$i<count($users);$i++)
        {
            $member[] = $users[$i];
        }
        $data['MemberToDel_Account'] = $member;

        $json_data = json_encode($data,TRUE);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1 );
//        curl_setopt($ch, CURLOPT_HEADER, 0 );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data );
        
        $res = curl_exec($ch);
        curl_close ( $ch );
        return $res;
    }
    //解散群
    public static function Destroy_group($group_id)
    {
        $uri = Common::C('TxYunURL');
        $user_sig = self::$user_sig;
        $identifier = Common::C('identifier');
        $sdkappid = Common::C('sdkappid');
        $link = "group_open_http_svc/destroy_group?usersig=".$user_sig."&apn=1&identifier=".$identifier."&sdkappid=".$sdkappid."&contenttype=json";
        $url = $uri.$link;
        $data = array();
        $data['GroupId'] = $group_id;

        $json_data = json_encode($data,TRUE);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1 );
//        curl_setopt($ch, CURLOPT_HEADER, 0 );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data );
        
        $res = curl_exec($ch);
        curl_close ( $ch );
        return $res;
    }
    //更新用户个人信息
    public static function Edit_userinfo($mobile,$users)
    {
        $uri = Common::C('TxYunURL');
        $user_sig = self::$user_sig;
        $identifier = Common::C('identifier');
        $sdkappid = Common::C('sdkappid');
        $link = "profile/portrait_set?usersig=".$user_sig."&apn=1&identifier=".$identifier."&sdkappid=".$sdkappid."&contenttype=json";
        $url = $uri.$link;
        $data = array();
        $data['From_Account'] = $mobile;
        $profile = array();

        if(!empty($users['nickname']))
        {
            $nick = array('Tag'=>'Tag_Profile_IM_Nick','Value'=>$users['nickname']);
            $profile[] = $nick;
        }
        if(!empty($users['avatar']))
        {
            $avatar = array('Tag'=>'Tag_Profile_IM_Image','Value'=>$users['avatar']);
            $profile[] = $avatar;
        }
        if(!empty($users['sex']))
        {
            if($users['sex']==1)
            {
                $sex = "Gender_Type_Male";
            }
            if($users['sex']==2)
            {
                $sex = "Gender_Type_Female";
            }
            $sex = array('Tag'=>'Tag_Profile_IM_Gender','Value'=>$sex);
            $profile[] = $sex;
        }
        if(!empty($users['personal_sign']))
        {
            $personal_sign = array('Tag'=>'Tag_Profile_IM_SelfSignature','Value'=>$users['personal_sign']);
            $profile[] = $personal_sign;
        }
        if(!empty($users['birthday']))
        {
            $birth = strtotime($users['birthday']);
            $birthday = array('Tag'=>'Tag_Profile_IM_BirthDay','Value'=>$birth);
            $profile[] = $birthday;
        }

        $data['ProfileItem'] = $profile;
        $json_data = json_encode($data,TRUE);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1 );
//        curl_setopt($ch, CURLOPT_HEADER, 0 );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data );
        
        $res = curl_exec($ch);
        curl_close ( $ch );
        return $res;
    }

    /**
     * 添加黑名单
     * @param  string $id 腾讯云注册的id
     * @param  array  $users_id 拉黑的人腾讯云注册id
     */
    
    public static function blackUser($id,$users_id){
        $uri = Common::C('TxYunURL');
        $user_sig = self::$user_sig;
        $identifier = Common::C('identifier');
        $sdkappid = Common::C('sdkappid');
        $link = "sns/black_list_add?usersig=".$user_sig."&identifier=".$identifier."&sdkappid=".$sdkappid."&contenttype=json";
        $url = $uri.$link;
        $data = array();
        $data['From_Account'] = $id;
        $member = array();
        for($i=0;$i<count($users_id);$i++)
        {
            $member[] = $users_id[$i];
        }
        $data['To_Account'] = $member;

        $json_data = json_encode($data,TRUE);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1 );
//        curl_setopt($ch, CURLOPT_HEADER, 0 );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data );
        
        $res = curl_exec($ch);
        curl_close ( $ch );
        return $res;
    }

    /**
     * 删除黑名单
     * @param  string $id 腾讯云注册的ID
     * @param  array  $users_id 将要删除的黑名单的id
     * @return json
     */
    public static function editBlackUser($id,$users_id){
        $uri = Common::C('TxYunURL');
        $user_sig = self::$user_sig;
        $identifier = Common::C('identifier');
        $sdkappid = Common::C('sdkappid');
        $link = "/sns/black_list_delete?usersig=".$user_sig."&identifier=".$identifier."&sdkappid=".$sdkappid."&contenttype=json";
        $url = $uri.$link;
        $data = array();
        $data['From_Account'] = $id;
        $member = array();
        for($i=0;$i<count($users_id);$i++)
        {
            $member[] = $users_id[$i];
        }
        $data['To_Account'] = $member;

        $json_data = json_encode($data,TRUE);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1 );
//        curl_setopt($ch, CURLOPT_HEADER, 0 );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data );
        
        $res = curl_exec($ch);
        curl_close ( $ch );
        return $res;
    }

    /**
     * 添加好友关系
     * @param  string $id 腾讯云注册的ID
     * @param  array  $users_id 将要删除的黑名单的id
     * @return json
     */
    public static function addFriend($id,$user_data){
        $uri = Common::C('TxYunURL');
        $user_sig = self::$user_sig;
        $identifier = Common::C('identifier');
        $sdkappid = Common::C('sdkappid');
        $link = "sns/friend_add?usersig=".$user_sig."&identifier=".$identifier."&sdkappid=".$sdkappid."&contenttype=json";
        $url = $uri.$link;
        $data = array();
        $data['From_Account'] = $id;
        $data['AddFriendItem'][] = $user_data;
        $json_data = json_encode($data);
        // print_r($json_data);exit;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1 );
//        curl_setopt($ch, CURLOPT_HEADER, 0 );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data );
        
        $res = curl_exec($ch);
        curl_close ( $ch );
        return $res;
    }
}