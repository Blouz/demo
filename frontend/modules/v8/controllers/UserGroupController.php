<?php
/**
 * 群聊天
 * User: duzongyan
 * Date: 2017/02/16
 */

namespace frontend\modules\v8\controllers;

use common\helpers\BaseRequestHelps;
use common\helpers\Common;
use common\helpers\LoulianHelper;
use common\helpers\TxyunHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\Group;
use frontend\models\i500_social\GroupMember;
use frontend\models\i500_social\GroupType;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500m\Community;
use frontend\models\i500_social\TradeUnionUserInfo;

class UserGroupController extends BaseController
{
     /**
      * 创建群组
      * @param string $mobile 手机号
      * @return array
      * @author duzongyan <duzongyan@i500m.com>
      */
    public function actionGroupCreate()
    {
        $mobile = BaseRequestHelps::post('mobile','','');
        // $mobile = 15870023822;
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $step = User::find()->select(['step'])->where(['mobile'=>$mobile])->scalar();
        if ((int)$step != 8) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }

        $image = BaseRequestHelps::post('image','','');
        // $image = 'http://c.hiphotos.baidu.com/baidu/pic/item/267f9e2f07082838c0c7d121ba99a9014c08f139.jpg';
        if (empty($image)) {
            $this->returnJsonMsg('6002', [], '头像不能为空');
        }
        // $name = BaseRequestHelps::post('name','','');
        $name = Common::userTextEncode($_POST['name']);
        // $name = "排球群";
        if (empty($name)) {
            $this->returnJsonMsg('6003', [], '名称不能为空');
        }
        $group_type_id = BaseRequestHelps::post('group_type_id','','');
        // $group_type_id = 1;
        if (empty($group_type_id)) {
            $this->returnJsonMsg('6004', [], '类别不能为空');
        }
        $desc = BaseRequestHelps::post('desc','','');

        //获取用户信息
        $userinfo = UserBasicInfo::find()->select(['mobile','nickname','avatar','last_community_id'])->where(['mobile'=>$mobile])->asArray()->one();
        if(empty($userinfo)) {
            return $this->returnJsonMsg('519',[],'用户未加入小区');
        }

        //注册露脸
        $loulian_re = LouLianHelper::llRegister ($userinfo[ 'mobile' ] , $userinfo[ 'nickname' ] , $userinfo[ 'avatar' ]);
        if ( empty( $loulian_re ) || $loulian_re[ 'error_code' ] != 2000 ) {
            return $this->returnJsonMsg('500',[],'注册露脸失败');
        }
        //创建群组
        $loulian_group = LouLianHelper::CreateGroup($mobile,$mobile,$name,$desc);
        if (!empty($loulian_group) && $loulian_group['error_code'] == 2000) {
            $group = new Group();
            $group -> community_id = $userinfo['last_community_id'];
            $group -> name = $loulian_group['name'];
            $group -> group_id = $loulian_group['id'];
            $group -> desc = $loulian_group['desc'];
            $group -> image = $image;
            $group -> group_type_id = $group_type_id;
            $group -> owner_mobile = $mobile;
            $group -> is_deleted = '2';
            $group -> owner_group = '2';
            $res = $group -> save(false);

            $group_member = new GroupMember();
            $group_member -> group_id = $loulian_group['id'];
            $group_member -> mobile = $mobile;
            $group_member -> nickname = $loulian_group['member']['0']['nickname'];
            $group_member -> role = '1';
            $group_member -> is_deleted = '2';
            $re = $group_member -> save(false);
            if (!$res || !$re) {
                return $this->returnJsonMsg('500',[],'网络繁忙');
            }
        }else {
            return $this->returnJsonMsg('515',[],'创建群失败');
        }
        $result = [];
        $result['group_id'] = $loulian_group['id'];
        return  $this->returnJsonMsg('200',$result,Common::C('code','200'));
    }

    /**
      * 更新群组信息
      * @param string $mobile 手机号
      * @return array
      * @author duzongyan <duzongyan@i500m.com>
      */
    public function actionGroupUpdate()
    {
        $group_id = BaseRequestHelps::post('group_id','','');
        if (empty($group_id)) {
            $this->returnJsonMsg('516', [], '群组id不能为空');
        }

        $image = BaseRequestHelps::post('image','','');
        if (empty($image)) {
            $this->returnJsonMsg('6002', [], '头像不能为空');
        }
        // $name = BaseRequestHelps::post('name','','');
        $name = Common::userTextEncode($_POST['name']);
        if (empty($name)) {
            $this->returnJsonMsg('6003', [], '名称不能为空');
        }
        $group_type_id = BaseRequestHelps::post('group_type_id','','');
        if (empty($group_type_id)) {
            $this->returnJsonMsg('6004', [], '类别不能为空');
        }
        $desc = BaseRequestHelps::post('desc','',''); 
        //更新群组
        $update_group = LouLianHelper::UpdateGroup($group_id,$name,$desc);
        if (!empty($update_group) && $update_group['error_code'] == 2000) {
             $res = Group::updateAll(['name'=>$name,'desc'=>$desc,'image'=>$image,
            'group_type_id'=>$group_type_id,'update_time'=>date('Y-m-d H:i:s')],['group_id'=>$group_id]);
            if (!$res) {
                return $this->returnJsonMsg('500',[],'网络繁忙');
            }
        }else {
            return $this->returnJsonMsg('517',[],'更新群组信息失败');
        }
        return  $this->returnJsonMsg('200',[],Common::C('code','200'));
    }

    /**
      * 获取群组信息
      * @param string $group_id 群号
      * @return array
      * @author duzongyan <duzongyan@i500m.com>
      */
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

    /**
      * 加入群组
      * @param string $mobile 手机号
      * @return array
      * @author duzongyan <duzongyan@i500m.com>
      */
    public function actionGroupJoin()
    {
        $group_id = BaseRequestHelps::post('group_id','','');
        if (empty($group_id)) {
            $this->returnJsonMsg('516', [], '群组id不能为空');
        }
        //当前登录人的手机号
        $mobile = BaseRequestHelps::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $step = User::find()->select(['step'])->where(['mobile'=>$mobile])->scalar();
        if ((int)$step != 8) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }
        //加入人的手机号
        $user_mobile = $_POST['user_mobile'];

        if (empty($user_mobile)) {
            $this->returnJsonMsg('604', [], '加入者手机号不能为空');
        }
        
        $user_mobile = json_decode($user_mobile,true);

        $user = UserBasicInfo::find()->select(['mobile','nickname','avatar'])
                ->with(['member'=>function($query) use($group_id){
                    $query->select(['mobile','role','is_deleted'])->where(['group_id'=>$group_id]);
                }])
                ->where(['mobile'=>$user_mobile])->asArray()->all();

        //注册露脸
        for($i=0;$i<count($user);$i++){
            if (!empty($user[$i]['member']) ) {
                if($user[$i]['member']['is_deleted'] != 1){
                    //查询手机号是否在传入的数组中
                    $key = array_search($user[$i]['mobile'],$user_mobile);
                    //删除掉之前加入的人
                    unset($user_mobile[$key]);
                }
            }
            if(empty($user_mobile)){
                    $this->returnJsonMsg('517', [], '此用户已在群组中');
            }
            $loulian_re = LouLianHelper::llRegister ($user[$i][ 'mobile' ] , $user[$i][ 'nickname' ] , $user[$i][ 'avatar' ]);

            if (empty( $loulian_re ) || $loulian_re[ 'error_code' ] != 2000 ) {
                return $this->returnJsonMsg('500',[],'注册露脸失败');
            }
        }
        $user_mobile = implode(',', $user_mobile);
        //加入群组
        $join_group = LouLianHelper::InsertGroup($group_id,$user_mobile);
        if (!empty($join_group) && $join_group['error_code'] == 2000) {
            $res =false;   
            $user_mobile = explode(',', $user_mobile);

            //删除掉重新插入
            $result = GroupMember::find()->select(['id','mobile'])->where(['mobile'=>$user_mobile,'group_id'=>$group_id])->asArray()->asArray()->all();
            if($result){
                $res1 = GroupMember::updateAll(['is_deleted'=>2],['mobile'=>$user_mobile,'group_id'=>$group_id]);
                if(!$res1){
                    return $this->returnJsonMsg('521',[],'加入失败,用户已加入群组');
                }
            }else{
                for ($i=0;$i<count($user_mobile);$i++) {
                    $nickname = $this->_getName($user_mobile[$i]);
                    $group_member = new GroupMember();
                    $group_member -> group_id = $group_id;
                    $group_member -> mobile   = $user_mobile[$i];
                    $group_member -> nickname   = $nickname['nickname'];
                    $group_member -> role     = 2;
                    $group_member -> is_deleted =2;
                    $res = $group_member->save(false); 
                }
                if (!$res) {
                    return $this->returnJsonMsg('500',[],'网络繁忙');
                }
            }
        }else {
            return $this->returnJsonMsg('522',[],'加入群组失败');
        }      
        return  $this->returnJsonMsg('200',[],Common::C('code','200'));
    }

    /**
      * 移除群组
      * @param string $mobile 手机号
      * @return array
      * @author huangdekui <huangdekui@i500m.com>
      */
    public function actionGroupAllRemove(){
        $mobile = BaseRequestHelps::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $step = User::find()->select(['step'])->where(['mobile'=>$mobile])->scalar();
        if ((int)$step != 8) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }
        //移除人的手机号
        $user_mobile = $_POST['user_mobile'];
        if (empty($user_mobile)) {
            $this->returnJsonMsg('604', [], '移除人的手机号不能为空');
        }

        $user_mobile = json_decode($user_mobile,true);
        //退出群组
        $group_id = BaseRequestHelps::post('group_id','','');
        if (empty($group_id)) {
            $this->returnJsonMsg('516', [], '群组id不能为空');
        }
        //转化成数组
        $is_mobile = Group::find()->select(['owner_mobile'])->where(['group_id'=>$group_id,'is_deleted'=>2])->scalar();

        if(in_array($is_mobile, $user_mobile)) {
            $this->returnJsonMsg('518', [], '群主不能删除');
        }
        $user_mobile = implode(',', $user_mobile);
        $remove_group =  LouLianHelper::DeleteGroup($group_id,$user_mobile);
        $user_mobile = explode(',', $user_mobile);
        if (!empty($remove_group) && $remove_group['error_code'] == 2000) {
            $res = GroupMember::updateAll(['is_deleted'=>'1'],['mobile'=>$user_mobile,'group_id'=>$group_id]);
            if (!$res) {
                return $this->returnJsonMsg('520',[],'删除失败,用户已移除群组');
            }
        }
        return  $this->returnJsonMsg('200',[],Common::C('code','200'));
    }



    /**
      * 退出群组
      * @param string $mobile 手机号
      * @return array
      * @author duzongyan <duzongyan@i500m.com>
      */
    public function actionGroupRemove(){
        $mobile = BaseRequestHelps::post('mobile','','');
        // $mobile = '18740090392';
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        //退出群组
        $group_id = BaseRequestHelps::post('group_id','','');
        // $group_id = '98bd7ad0fcba11e6a593fb907fee57ba';
        if (empty($group_id)) {
            $this->returnJsonMsg('516', [], '群组id不能为空');
        }
        $remove_group =  LouLianHelper::DeleteGroup($group_id,$mobile);
        if (!empty($remove_group) && $remove_group['error_code'] == 2000) {
            $res = GroupMember::updateAll(['is_deleted'=>'1'],['mobile'=>$mobile,'group_id'=>$group_id]);
            if (!$res) {
                return $this->returnJsonMsg('500',[],'网络繁忙');
            } 
            return  $this->returnJsonMsg('200',[],Common::C('code','200'));
        }    
    }

    /**
      * 解散群组
      * @param string $group_id 群id
      * @return array
      * @author duzongyan <duzongyan@i500m.com>
      */
    public function actionGroupExit(){
        $group_id = BaseRequestHelps::post('group_id','','');
        if (empty($group_id)) {
            $this->returnJsonMsg('516', [], '群组id不能为空');
        }
        //解散群组
        //1:是业主群，2：不是业主群
        $owner_group = Group::find()->select(['owner_group'])->where(['group_id' => $group_id])->scalar();
        if($owner_group == 2) {
            $exit_group =  LouLianHelper::ExitGroup($group_id);
            if (!empty($exit_group) && $exit_group['error_code'] == 2000) {
                $res = Group::updateAll(['is_deleted'=>'1'],['group_id'=>$group_id]);
                $re = GroupMember::updateAll(['is_deleted'=>'1'],['group_id'=>$group_id]);
                if (!$res || !$re) {
                    return $this->returnJsonMsg('500',[],'网络繁忙');
                } 
                return  $this->returnJsonMsg('200',[],Common::C('code','200'));
            }   
        }else {
            return  $this->returnJsonMsg('664',[],Common::C('code','664'));
        }
    }

    /**
     * 群组类别
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */
     public function actionGroupType()
    {   
        $result = GroupType::find()->select(['id','name'])->where(['status'=>1])->orderBy('sort ASC')->asArray()->all();
        if (empty($result)) {   
            $this->returnJsonMsg('735',[], '群组类别为空');
        }
        $this->returnJsonMsg('200',$result, Common::C('code','200','data','[]'));
    }

    /**
     * 群组列表
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */
     public function actionGroupList()
    {   
        $mobile = BaseRequestHelps::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $step = User::find()->select(['step'])->where(['mobile'=>$mobile])->scalar();
        if ((int)$step != 8) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }

        $page = BaseRequestHelps::post('page', '1', 'intval');
        $page_size = BaseRequestHelps::post('page_size', '10', 'intval');
    
        //获取用户信息s
        $userinfo = UserBasicInfo::find()->select(['last_community_id','mobile','realname','avatar'])->where(['mobile'=>$mobile])->asArray()->one();
        $user = User::find()->select(['id'])->where(['mobile'=>$mobile])->asArray()->one();
        if($userinfo['last_community_id'] == "") {
           $this->returnJsonMsg('732', [], '用户未加入小区');
        }

        //查询一下当前的用户所在小区有没有业主群
        $group = Group::find()->select(['community_id','group_id'])
                 ->where(['owner_group'=>'1','source'=>1,'community_id'=>$userinfo['last_community_id']])->asArray()->one();

        if(empty($group)){
            $data = Community::find()->select(['id','name'])
                 ->with(['user'=>function($query){
                     $query->select(['last_community_id','realname','avatar','mobile']);
                 }])
                 ->where(['status'=>1,'id'=>$userinfo['last_community_id']])
                 ->asArray()
                 ->one();
            $data_name = '邻居议事厅';
            //创建业主群并且把当前小区的所有人都加入进来
            $model = TxyunHelper::Create_group('admin','Public',$data_name,Common::C('defaultGroup'));
            $model = json_decode($model,true);
            if ( !empty( $model ) && $model['ActionStatus'] == 'OK' ) {
                 $group = new Group();
                 $group->community_id = $data['id'];
                 $group->name = $data_name;
                 $group->group_id = $model['GroupId'];
                 $group->image = Common::C('defaultGroup');
                 $group->desc = '';
                 $group->is_deleted = 2;
                 $group->owner_group = 1;
                 $group->create_time = date('Y-m-d H:i:s');
                 $group->source = 1;
                 $res = $group->save(false);

                 $mobile = [];
                 if ($res && !empty($data['user'])) {
                     $resgister = TxyunHelper::Regsiter($user['id'],$userinfo['realname'],$userinfo['avatar']);
                     $resgister = json_decode($resgister, true);
                     if ( !empty( $resgister ) && $resgister['ActionStatus'] == 'OK' ) {
                         $group_member = new GroupMember();
                         $group_member->group_id = $model['GroupId'];
                         $group_member->mobile = $data['user']['mobile'];
                         $group_member->nickname = $data['user']['realname'];
                         $group_member->role = 2;
                         $group_member->save(false);
                         $mobile[] = $user['id'];
                     }
                     $join_in = TxyunHelper::Join_group($model['GroupId'], $mobile);
                     $join_in = json_decode($join_in, true);
                 }
            }
        } else {
            //当前登录用户在不在业主群
            $field = [];
            $field[] = 'i500_user_basic_info.mobile';
            $field[] = 'i500_user_basic_info.realname';
            $field[] = 'i500_user_basic_info.avatar';
            $field[] = 'i500_group_member.group_id';
            $field[] = 'i500_group_member.id';

            $group_id = GroupMember::find()->select($field)
                        ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_group_member.mobile')
                        ->where(['group_id'=>$group['group_id'],GroupMember::tableName().'.mobile'=>$mobile,'is_deleted'=>2])
                        ->asArray()
                        ->one();
            $usermobile = [];
            if (empty($group_id)) {
//                $resgister = TxyunHelper::Regsiter($user['id'],$userinfo['realname'],$userinfo['avatar']);
//                $resgister = json_decode($resgister, true);
//                if ( !empty( $resgister ) && $resgister['ActionStatus'] == 'OK' ) {
                    $group_member = new GroupMember();
                    $group_member->group_id = $group['group_id'];
                    $group_member->mobile = $userinfo['mobile'];
                    $group_member->nickname = $userinfo['realname'];
                    $group_member->role = 2;
                    $group_member->save(false);
                    $usermobile[] = $user['id'];
//                }
                $join_in = TxyunHelper::Join_group($group['group_id'], $usermobile);
                $join_in = json_decode($join_in, true);
			}
        }
        $groupIdArr = GroupMember::find()->select(['group_id'])->where(['mobile'=>$mobile,'is_deleted'=>2])->column();
        
        //工会社区id
        $tcommunity_id = TradeUnionUserInfo::find()->select(['trade_union_street_community_id'])->where(['mobile'=>$mobile])->scalar();
        $tcommunity_id = empty($tcommunity_id) ? -1 : $tcommunity_id;
        //群列表
        $result = Group::find()->select(['group_id','name','image','group_type_id','owner_mobile'])
                  ->with(['type'=>function($query) { 
                    $query->select(['id','name']);
                  }])->with(['member'=>function($data) use($mobile) {
                    $data->select("group_id,count('group_id') as count")->groupBy('group_id')->where(['is_deleted'=>2,'mobile'=>$mobile]);
                  }])
                  ->where(['is_deleted'=>2,'group_id'=>$groupIdArr])
                  ->andWhere(['or',['community_id'=>$userinfo['last_community_id']],['trade_union_id'=>$tcommunity_id]])
                  ->orderBy('create_time DESC')->offset(($page-1) * $page_size)->limit($page_size)->asArray()->all();
        foreach ($result as $k => $v) {
            $result[$k]['name'] = Common::userTextDecode($v['name']);
        }
        if (empty($result)) {   
            $this->returnJsonMsg('532', [], '暂无数据');
        }
        $this->returnJsonMsg('200',$result, Common::C('code','200','data','[]'));
    }


    /**
     * 获取用户信息
     * @param string $mobile 电话
     * @return array
     */
    private function _getName($mobile = '')
    {
        $user_base_info_model = new UserBasicInfo();
        $user_base_info_where['mobile'] = $mobile;
        $user_base_info_fields = 'nickname,avatar';
        $rs['avatar']   = '';
        $rs['nickname'] = '';
        $rs = $user_base_info_model->getInfo($user_base_info_where, true, $user_base_info_fields);
        if (!empty($rs)) {
            if ($rs['avatar']) {
                if (!strstr($rs['avatar'], 'http')) {
                    $rs['avatar'] = Common::C('imgHost').$rs['avatar'];
                }
            }
        }
        return $rs;
    }
} 