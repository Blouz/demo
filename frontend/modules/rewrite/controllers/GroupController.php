<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\modules\rewrite\controllers;

use common\helpers\BaseRequestHelps;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social_rewrite\User;
use frontend\models\i500_social_rewrite\Group;
use frontend\models\i500_social_rewrite\GroupMember;
use frontend\models\i500_social_rewrite\GroupType;
use frontend\models\i500_social_rewrite\UserBasicInfo;
use frontend\models\i500_social_rewrite\UserCommunity;
use common\helpers\TxyunHelper;
use common\vendor\tls_sig\php\sig;
use yii\db\Query;
class GroupController extends GroupBaseController
{
    public function actionCreateGroup()
    {
        if (empty($this->image)) {
            $this->returnJsonMsg('2001', [], Common::C('coderewrite','2001'));
        }
        if (empty($this->group_name)) {
            $this->returnJsonMsg('2002', [], Common::C('coderewrite','2002'));
        }
        if (empty($this->group_type_id)) {
            $this->returnJsonMsg('2003', [], Common::C('coderewrite','2003'));
        }
       $start = date("Y-m-d H:i:s", strtotime("-1 day"));
       $end = date("Y-m-d H:i:s", time());

       $Group = new Group();
       $group_where = array('owner_mobile'=>$this->mobile);
       $group_and_where = array('between', 'create_time', $start, $end);
       $group_num = $Group->getCount($group_where,$group_and_where);

       $User = new User();
       $user_where = array('mobile'=>$this->mobile);
       $user_field = array('id');
       $userid = $User->getOneRecord($user_where,'',$user_field);
    
       if((int)$group_num>=500)
       {
            $this->returnJsonMsg('2004', [], Common::C('coderewrite','2004'));
       }
       $type = "Public";
       $res = TxyunHelper::Create_group($userid['id'],$type,$this->group_name,$this->image);
       $result = json_decode($res,true);
       if($result['ActionStatus']=='OK')
       {
           $id = $result['GroupId'];
           $UserBasicInfo = new UserBasicInfo();
           $userinfo_field = array('mobile','nickname','avatar');
           $userinfo = $UserBasicInfo->getOneRecord($user_where,'', $userinfo_field);

           $UserCommunity = new UserCommunity();
           $usercommunity_field = array('mobile','community_id');
           $usercommunity = $UserCommunity->getOneRecord($user_where,'', $usercommunity_field);
           if(!empty($userinfo))
           {    

                $group_data = array();
                $group_data['community_id'] = $usercommunity['community_id'];
                $group_data['name'] = $this->group_name;
                $group_data['group_id'] = $id;
                $group_data['image'] = $this->image;
                $group_data['group_type_id'] = $this->group_type_id;
                $group_data['owner_mobile'] = $this->mobile;
                $group_data['is_deleted'] = '2';
                $group_data['owner_group'] = '2';
                $group_data['source'] = '1';
                $create_group = $Group->insertInfo($group_data);
                if($create_group)
                {   

                    $GroupMember = new GroupMember();
                    $member_data = array();
                    $member_data['group_id'] = $id; 
                    $member_data['mobile'] = $this->mobile; 
                    $member_data['nickname'] = $userinfo['nickname']; 
                    $member_data['role'] = '1'; 
                    $member_data['is_deleted'] = '2'; 
                    $create_group_member = $GroupMember->insertInfo($member_data);
                }
           }
       }
       return  $this->returnJsonMsg('200',$result['GroupId'],Common::C('coderewrite','200'));
    }

    //更新群信息
    public function actionGroupUpdate()
    {
        if (empty($this->group_id)) {
            $this->returnJsonMsg('2005', [], Common::C('coderewrite','2005'));
        }
        if (empty($this->image)) {
            $this->returnJsonMsg('2001', [], Common::C('coderewrite','2001'));
        }
        if (empty($this->group_name)) {
            $this->returnJsonMsg('2002', [], Common::C('coderewrite','2002'));
        }
        if (empty($this->group_type_id)) {
            $this->returnJsonMsg('2003', [], Common::C('coderewrite','2003'));
        }
        $introduction = NULL;
        $notification = NULL;
        $maxnum = 500;
        $apply_join_option = NULL;
        $res = TxyunHelper::Update_group($this->group_id, $this->group_name, $introduction, $notification, $this->image, $maxnum, $apply_join_option);

        $result = json_decode($res,true);
        if($result['ActionStatus']=='OK')
        {
            $Group = new Group();
            $group_data = array();
            $group_data['name'] = $this->group_name;
            $group_data['image'] = $this->image;
            $group_data['group_type_id'] = $this->group_type_id;
            $cond = array();
            $cond['owner_mobile'] = $this->mobile;
            $cond['group_id'] = $this->group_id;
            $update_group = $Group->updateInfo($group_data,$cond);
        }
        return  $this->returnJsonMsg('200',$result,Common::C('coderewrite','200'));
    }

    //加入群
    public function actionGroupJoin()
    {
        if (empty($this->group_id)) {
            $this->returnJsonMsg('2005', [], Common::C('coderewrite','2005'));
        }
        if (empty($this->users)) {
            $this->returnJsonMsg('2006', [], Common::C('coderewrite','2006'));
        }
        //使用i500_user里面的id注册腾讯云
        $User = new User();
        $user_where = array('mobile'=>$this->users);
        $user_field = array('id');
        $users_id = $User->getOneColumn($user_where,$user_field);

        $res = TxyunHelper::Join_group($this->group_id, $users_id);
        $result = json_decode($res,true);
        $success = false;
        $new_users = array();
        if($result['ActionStatus']=='OK')
        {   
            $GroupMember = new GroupMember();
            $member_field = array('mobile');
            $member_where = array('group_id'=>$this->group_id,'is_deleted'=>2);
            $members = $GroupMember->getList($member_where,$member_field,'','');
            $members_mobile = array();
            foreach($members as $m)
            {
                $members_mobile[] = $m['mobile'];
            }
            //排除已加入该群的用户
            for($i=0;$i<count($this->users);$i++)
            {
                if(!in_array($this->users[$i], $members_mobile))
                {
                    $new_users[] = $this->users[$i];
                }
            }
            rsort($new_users);

            $UserBasicInfo = new UserBasicInfo();
            $userinfo_field = array('mobile','nickname');
            $userinfo = $UserBasicInfo->getList($user_where,$userinfo_field,'','');
            //用户加入群
            if(!empty($new_users))
            {
                for($j=0;$j<count($new_users);$j++)
                {
                    $member_data = array();
                    $member_data['group_id'] = $this->group_id;
                    $member_data['mobile'] = $new_users[$j];
                    foreach($userinfo as $v)
                    {
                        if($v['mobile']==$new_users[$j])
                        {
                            $member_data['nickname'] = $v['nickname'];
                        }
                    }
                    $member_data['role'] = '2';
                    $success = $GroupMember->insertInfo($member_data);
                }
            }

        }
        return  $this->returnJsonMsg('200',$result,Common::C('code','200'));
    }
    
    //退群
    public function actionGroupExit()
    {
        $group_id = RequestHelper::post('group_id','','');
        if (empty($group_id)) {
            $this->returnJsonMsg('516', [], '群组id不能为空');
        }
        $users_mobile = json_decode($_POST['user_mobile'],true);
        if (empty($users_mobile)) {
            $this->returnJsonMsg('517', [], '退出群组用户的手机号不能为空');
        }
        $users = User::find()->select(['id'])->where(['mobile'=>$users_mobile])->column();

        $res = TxyunHelper::Exit_group($group_id, $users);
        $result = json_decode($res,true);
        if($result['ActionStatus']=='OK')
        {
            for($i=0;$i<count($users_mobile);$i++)
            {
                $mobile = $users_mobile[$i];
                $exit_group = GroupMember::updateAll(array('is_deleted'=>1),'mobile=:mobile AND group_id=:id',array(':mobile'=>$mobile,':id'=>$group_id));
            }
        }
        return  $this->returnJsonMsg('200',$result,Common::C('code','200'));
    }

    //解散群
    public function actionGroupDestroy()
    {
        $group_id = RequestHelper::post('group_id','','');
        if (empty($group_id)) {
            $this->returnJsonMsg('516', [], '群组id不能为空');
        }
        $mobile = RequestHelper::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $users = User::find()->select(['id'])->where(['mobile'=>$mobile])->column();

        $is_owner = Group::find()->select(['id'])->where(['group_id'=>$group_id,'owner_mobile'=>$mobile])->asArray()->all();
        if(!empty($is_owner))
        {
            $res = TxyunHelper::Destroy_group($group_id);
            $result = json_decode($res,true);
            if($result['ActionStatus']=='OK')
            {
                $res = Group::updateAll(['is_deleted'=>1],['group_id'=>$group_id]);
                return  $this->returnJsonMsg('200',$result,Common::C('code','200'));
            }
        }
        else
        {
            return $this->returnJsonMsg('6111',[],'您不是该群群主');
        }
    }

    //群信息
    public function actionGroupGet()
    {
        $group_id = BaseRequestHelps::post('group_id','','');
        if (empty($group_id)) {
            $this->returnJsonMsg('516', [], '群组id不能为空');
        }
        $page = BaseRequestHelps::post('page', '1', 'intval');
        $size = 10;
        //获取群组信息
        // $field[] = "i500_group.id";
        $field[] = "i500_group.group_id";
        $field[] = "i500_group.name as group_name";
        $field[] = "i500_group.desc";
        $field[] = "i500_group.image";
        $field[] = "i500_group.group_type_id";
        $field[] = "i500_group.community_id";
        $field[] = "i500_group.owner_mobile";
        $field[] = "i500_group.owner_group";
        $field[] = "i500_group_type.id";
        $field[] = "i500_group_type.name";
        $field['members'] = (new Query())->select('count(id)')->from("i500_group_member")->where("group_id=i500_group.group_id and is_deleted=2");

        $condition[Group::tableName().'.group_id'] = $group_id;
        $condition[Group::tableName().'.is_deleted'] = 2;

        $result = Group::find()
                     ->select($field)
                     ->join('LEFT JOIN','i500_group_type','i500_group_type.id=i500_group.group_type_id')
                     ->joinWith(['member'=>function($query) use($page,$size){
                           $query->select(['i500_group_member.id','group_id','i500_group_member.mobile'])->where([GroupMember::tableName().'.is_deleted'=>2])->orderBy('role ASC')->offset(($page-1)*$size)->limit($size)->joinWith(['user'=>function($data){
                                $data->select(['nickname','avatar','mobile']);
                           }]);
                     }])
                     ->where($condition)
                     ->asArray()
                     ->one(); 
        if($result['owner_group'] == 1){
            $type_name = '业主';
            $result['name'] = $type_name;
        }           
        $result['group_name'] = Common::userTextDecode($result['group_name']);
        return  $this->returnJsonMsg('200',$result,Common::C('code','200'));
      
    }

}