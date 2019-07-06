<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\modules\v10\controllers;

use common\helpers\BaseRequestHelps;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\Group;
use frontend\models\i500_social\GroupMember;
use frontend\models\i500_social\GroupType;
use frontend\models\i500_social\UserBasicInfo;
use common\helpers\TxyunHelper;
use common\vendor\tls_sig\php\sig;
use yii\db\Query;
class GroupController extends BaseController
{
    public function actionCreateGroup()
    {
        $mobile = BaseRequestHelps::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $image = BaseRequestHelps::post('image','','');
        if (empty($image)) {
            $this->returnJsonMsg('6002', [], '头像不能为空');
        }
        $group_name = Common::userTextEncode($_POST['name']);
        if (empty($group_name)) {
            $this->returnJsonMsg('6003', [], '名称不能为空');
        }
        if (strlen($group_name)>30) {
            $this->returnJsonMsg('6010', [], '群名称长度超过最大限制');
        } 
        $group_type_id = BaseRequestHelps::post('group_type_id','','');
        if (empty($group_type_id)) {
            $this->returnJsonMsg('6004', [], '类别不能为空');
        }
       $start = date('Y-m-d 00:00:00');
       $end = date('Y-m-d 00:00:00',strtotime('+1 day'));
       
       $owner_mobile = $mobile;
       $group_num = Group::find()->select(['id'])->where(['owner_mobile'=>$mobile])->andWhere(['between', 'create_time', $start, $end])->count();
       $mobile = User::find()->select(['id'])->where(['mobile'=>$mobile])->scalar();
       //$info = UserBasicInfo::find()->select(['nickname', 'avatar'])->where(['mobile'=>$owner_mobile])->asArray()->one();
       //$nick = $info['nickname'];
       //$avatar = $info['avatar'];
       if((int)$group_num>=500)
       {
            $this->returnJsonMsg('6005', [], '用户每天建群数量不能超过500');
       }
       //$ress = TxyunHelper::Regsiter($mobile,$nick,$avatar);
       //$resu = json_decode($ress, true);
       $type = "Public";
       $res = TxyunHelper::Create_group($mobile,$type,$group_name,$image);
       $result = json_decode($res,true);
       if($result['ActionStatus']=='OK')
       {
           $id = $result['GroupId'];
//           $char = explode('#',$id);
//           $prefix = $char[0].'#';
//           $group_id = $char[1];
           $userinfo = UserBasicInfo::find()->select(['mobile','nickname','avatar','last_community_id'])->where(['mobile'=>$owner_mobile])->asArray()->one();
           if(!empty($userinfo))
           {
                $group = new Group();
                $group -> community_id = $userinfo['last_community_id'];
                $group -> name = $group_name;
                $group -> group_id = $id;
//                $group -> prefix = $prefix;
                $group -> image = $image;
                $group -> group_type_id = $group_type_id;
                $group -> owner_mobile = $owner_mobile;
                $group -> is_deleted = '2';
                $group -> owner_group = '2';
                $group -> source = '1';
                $create_group = $group -> save(false);
                if($create_group)
                {
                    $group_member = new GroupMember();
                    $group_member -> group_id = $id;
                    $group_member -> mobile = $owner_mobile;
                    $group_member -> nickname = $userinfo['nickname'];
                    $group_member -> role = '1';
                    $group_member -> is_deleted = '2';
                    $create_group_member = $group_member -> save(false);
                }
           }
       } else {
           $this->returnJsonMsg('400',[],Common::C('code','400')); 
       }
       return  $this->returnJsonMsg('200',$result['GroupId'],Common::C('code','200'));
    }
    //更新群信息
    public function actionGroupUpdate()
    {
        $mobile = RequestHelper::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $group_id = RequestHelper::post('group_id','','');
        if (empty($group_id)) {
            $this->returnJsonMsg('516', [], '群组id不能为空');
        }

        $image = RequestHelper::post('image','','');
        if (empty($image)) {
            $this->returnJsonMsg('6002', [], '头像不能为空');
        }
        $group_name = RequestHelper::post('name','','');
        if (empty($group_name)) {
            $this->returnJsonMsg('6003', [], '名称不能为空');
        }
        if (strlen($group_name)>30) {
            $this->returnJsonMsg('6010', [], '群名称长度超过最大限制');
        } 
        $group_type_id = RequestHelper::post('group_type_id','','');
        if (empty($group_type_id)) {
            $this->returnJsonMsg('6004', [], '类别id不能为空');
        }
        $introduction = NULL;
        $notification = NULL;
        $maxnum = 1000;
        $apply_join_option = NULL;
        $res = TxyunHelper::Update_group($group_id, $group_name, $introduction, $notification, $image, $maxnum, $apply_join_option);

        $result = json_decode($res,true);
        if($result['ActionStatus']=='OK')
        {
            $update_group = Group::updateAll(array('image'=>$image,'name'=>$group_name,'group_type_id'=>$group_type_id),'owner_mobile=:mobile AND group_id=:id',array(':mobile'=>$mobile,':id'=>$group_id));
        } else {
           $this->returnJsonMsg('400',[],Common::C('code','400')); 
        }
        return  $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
    //加入群
    public function actionGroupJoin()
    {
        $group_id = RequestHelper::post('group_id','','');
        if (empty($group_id)) {
            $this->returnJsonMsg('516', [], '群组id不能为空');
        }
        $users = json_decode($_POST['user_mobile'],true);
        if (empty($users)) {
            $this->returnJsonMsg('517', [], '加入群组用户的手机号不能为空');
        }
        //使用i500_user里面的id注册腾讯云
        $users_id = User::find()->select(['id'])->where(['mobile'=>$users])->column();
        $res = TxyunHelper::Join_group($group_id, $users_id);
        $result = json_decode($res,true);
        $success = false;
        $new_users = array();
        if($result['ActionStatus']=='OK')
        {
            $members = GroupMember::find()->select(['mobile'])->where(['group_id'=>$group_id,'is_deleted'=>2])->asArray()->all();
            $members_mobile = array();
            foreach($members as $m)
            {
                $members_mobile[] = $m['mobile'];
            }
            //排除已加入该群的用户
            for($i=0;$i<count($users);$i++)
            {
                if(!in_array($users[$i], $members_mobile))
                {
                    $new_users[] = $users[$i];
                }
            }
            rsort($new_users);

            $nick = UserBasicInfo::find()->select(['mobile','nickname'])->where(['mobile'=>$users])->asArray()->all();
            //用户加入群
            if(!empty($new_users))
            {
                for($j=0;$j<count($new_users);$j++)
                {
                    $group_mumber = new GroupMember();
                    $group_mumber->group_id = $group_id;
                    $group_mumber->mobile = $new_users[$j];
                    foreach($nick as $n)
                    {
                        if($n['mobile']==$new_users[$j])
                        {
                            $group_mumber->nickname = $n['nickname'];
                        }
                    }
                    $group_mumber->role = 2;
                    $success = $group_mumber->save(false);
                }
            }

        } else {
           $this->returnJsonMsg('400',[],Common::C('code','400')); 
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
        if (empty($_POST['user_mobile'])) {
            $this->returnJsonMsg('517', [], '退出群组用户的手机号不能为空');
        }
        $users_mobile = json_decode($_POST['user_mobile'],true);
        if (empty($users_mobile)) {
            $this->returnJsonMsg('517', [], '退出群组用户的手机号不能为空');
        }
        $users = User::find()->select(['id'])->where(['mobile'=>$users_mobile])->column();
        $res = TxyunHelper::Exit_group($group_id, $users);
        $result = json_decode($res,true);
        if($result['ActionStatus']=='OK') {
            for($i=0;$i<count($users_mobile);$i++)
            {
//                $mobile = $users_mobile[$i];
//                $exit_group = GroupMember::updateAll(array('is_deleted'=>1),'mobile=:mobile AND group_id=:id',array(':mobile'=>$mobile,':id'=>$group_id));
                GroupMember::updateAll(['is_deleted'=>1],['mobile'=>$users_mobile[$i],'group_id'=>$group_id]);
            }
            return  $this->returnJsonMsg('200',$result,Common::C('code','200'));
        } else {
            return $this->returnJsonMsg('10007',[],'退群失败');
        }
        
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
            } else {
               $this->returnJsonMsg('400',[],Common::C('code','400')); 
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
            $result['name'] = '业主';
        }elseif($result['owner_group'] == 3){
            $result['name'] = '工会';
        }
        $result['group_name'] = Common::userTextDecode($result['group_name']);
        return  $this->returnJsonMsg('200',$result,Common::C('code','200'));
      
    }

}