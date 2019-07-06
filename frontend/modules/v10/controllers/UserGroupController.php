<?php
/**
 * 群聊天
 * User: duzongyan
 * Date: 2017/02/16
 */

namespace frontend\modules\v10\controllers;

use common\helpers\BaseRequestHelps;
use common\helpers\Common;
use common\helpers\LoulianHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\Group;
use frontend\models\i500_social\GroupMember;
use frontend\models\i500_social\GroupType;
use frontend\models\i500_social\UserBasicInfo;
class UserGroupController extends BaseController
{
     /*
      * 更新群组信息
      * @param string $group_id 群号
      * @param string $mobile 手机号
      * @param string $image   群图片
      * @param string $name   群名称
      * @param string $group_type_id   群类别
      * @param string $desc   群简介
      * @return array
      * @author duzongyan <duzongyan@i500m.com>
      */
    public function actionGroupUpdate()
    {
        $mobile = BaseRequestHelps::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $group_id = BaseRequestHelps::post('group_id','','');
        if (empty($group_id)) {
            $this->returnJsonMsg('516', [], '群组id不能为空');
        }

        $image = BaseRequestHelps::post('image','','');
        if (empty($image)) {
            $this->returnJsonMsg('6002', [], '头像不能为空');
        }
        $name = BaseRequestHelps::post('name','','');
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

     /*
      * 获取群组信息
      * @param string $group_id 群号
      * @param string $mobile 手机号
      * @return array
      * @author duzongyan <duzongyan@i500m.com>
      */
    public function actionGroupGet()
    {
        $mobile = BaseRequestHelps::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $group_id = BaseRequestHelps::post('group_id','','');
        if (empty($group_id)) {
            $this->returnJsonMsg('516', [], '群组id不能为空');
        }
        $result = Group::find()
                     ->select(['group_id','name','owner_mobile','desc','image','group_type_id'])
                     ->with(["type"=>function($query){
                         $query->select(['id','name']);
                     }])
                     ->where(['group_id'=>$group_id,'is_deleted'=>2])
                     ->asArray()
                     ->one();
        return  $this->returnJsonMsg('200',$result,Common::C('code','200'));
      
    }
    /*
     * 获取群成员列表
     * @param string $group_id 群号
     * @param string $mobile 手机号
     * @param string $page 页数 （不传默认1）
     * @param string $pageSize 数量 （不传默认10）
     * @return array
     * @author huangdekui <huangdekui@i500m.com>
     */
    public function actionGroupMember()
    {
        $mobile = BaseRequestHelps::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $page     = RequestHelper::post('page','1','intval');
        $pageSize = RequestHelper::post('pagesize','10','intval');
        $group_id = BaseRequestHelps::post('group_id','','');
        if (empty($group_id)) {
            $this->returnJsonMsg('516', [], '群组id不能为空');
        }
        $result = GroupMember::find()->select(['mobile','role'])
                  ->with(['user'=>function($data){
                    $data->select(['nickname','avatar','mobile']);
                  }])
                  ->where(['group_id'=>$group_id,'is_deleted'=>2])
                  ->offset(($page-1)*$pageSize)
                  ->limit($pageSize)
                  ->asArray()
                  ->all();
        return  $this->returnJsonMsg('200',$result,Common::C('code','200'));
    }

     /*
      * 加入群组
      * @param string $mobile 手机号
      * @param string $group_id 群ID
      * @param string $user_mobile 加入群的手机号 例:["12345678910"]
      * @return array
      * @author duzongyan <duzongyan@i500m.com>
      */
    public function actionGroupJoin()
    {
        //当前登录人的手机号
        $mobile = BaseRequestHelps::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $group_id = BaseRequestHelps::post('group_id','','');
        if (empty($group_id)) {
            $this->returnJsonMsg('516', [], '群组id不能为空');
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

     /*
      * 解散群组
      * @param string $mobile 手机号
      * @param  string $group_id 群id
      * @return array
      * @author duzongyan <duzongyan@i500m.com>
      */
    public function actionGroupExit(){
        //当前登录人的手机号
        $mobile = BaseRequestHelps::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $group_id = BaseRequestHelps::post('group_id','','');
        if (empty($group_id)) {
            $this->returnJsonMsg('516', [], '群组id不能为空');
        }
        //解散群组
        $exit_group =  LouLianHelper::ExitGroup($group_id);
        if (!empty($exit_group) && $exit_group['error_code'] == 2000) {
            $res = Group::updateAll(['is_deleted'=>'1'],['group_id'=>$group_id]);
            $re = GroupMember::updateAll(['is_deleted'=>'1'],['group_id'=>$group_id]);
            if (!$res || !$re) {
                return $this->returnJsonMsg('500',[],'网络繁忙');
            }
        }
        return  $this->returnJsonMsg('200',[],Common::C('code','200'));
    }

    /*
     * 群组类别
     * @param string $mobile 手机号
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */
     public function actionGroupType()
    {
        //当前登录人的手机号
        $mobile = BaseRequestHelps::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $result = GroupType::find()->select(['id','name'])->where(['status'=>1])->orderBy('sort ASC')->asArray()->all();
        if (empty($result)) {   
            $this->returnJsonMsg('735',[], '群组类别为空');
        }
        return  $this->returnJsonMsg('200',$result,Common::C('code','200'));
    }

    /*
     * 群组列表
     * @param string $mobile 手机号
     * @param string $page   页数 默认1
     * @param string $page_size   页数 默认10
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
    
        //获取用户信息
        $userinfo = UserBasicInfo::find()->select(['last_community_id'])->where(['mobile'=>$mobile])->asArray()->one();
        if($userinfo['last_community_id'] == "") {
           $this->returnJsonMsg('732', [], '用户未加入小区');
        }

        $groupIdArr = GroupMember::find()->select(["group_id"])->where(['is_deleted'=>2,'mobile'=>$mobile])->column();

        $result = Group::find()->select(['group_id','name','image','owner_group','group_type_id','owner_mobile'])->with(['type'=>function($query) {
            $query->select(['id','name']);
        }])->with(['member'=>function($data) use($mobile) {
            $data->select("group_id,count('group_id') as count")->groupBy('group_id')->where(['is_deleted'=>2,'mobile'=>$mobile]);
        }])->where(['is_deleted'=>2,'community_id'=>$userinfo['last_community_id'],'group_id'=>$groupIdArr])->orderBy('create_time DESC')->offset(($page-1) * $page_size)->limit($page_size)->asArray()->all();
        if (empty($result)) {
            $this->returnJsonMsg('532', [], '暂无数据');
        }else{
            foreach($result as $key=>$value){
                if($value['owner_group'] == 1){
                    $result[$key]['image'] = $result[$key]['image']?$result[$key]['image']:Common::C('defaultGroup');
                }
            }
        }
        return  $this->returnJsonMsg('200',$result,Common::C('code','200'));
    }


    /*
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