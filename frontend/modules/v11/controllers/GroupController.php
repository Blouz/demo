<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\modules\v11\controllers;

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
        $array = [];
        $array[] = $result;
        return  $this->returnJsonMsg('200',$array,Common::C('code','200'));
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
        if($result['ActionStatus']=='OK') {
            for($i=0;$i<count($users_mobile);$i++)
            {
                $mobile = $users_mobile[$i];
                $exit_group = GroupMember::updateAll(array('is_deleted'=>1),'mobile=:mobile AND group_id=:id',array(':mobile'=>$mobile,':id'=>$group_id));
            }
            $array = [];
            $array[] = $result;
            return  $this->returnJsonMsg('200',$array,Common::C('code','200'));
        } else {
            return $this->returnJsonMsg('10007',[],'退群失败');
        }

    }
}