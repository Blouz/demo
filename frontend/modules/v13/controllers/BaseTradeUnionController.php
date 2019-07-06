<?php
/**
 * v13 工会基类
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/9/28
 */

namespace frontend\modules\v13\controllers;

use frontend\models\i500_social\TradeUnion;
use frontend\models\i500_social\TradeUnionUser;
use common\helpers\Common;
use frontend\models\i500_social\Group;
use frontend\models\i500_social\GroupMember;
use common\helpers\TxyunHelper;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\User;
use frontend\models\i500_social\TradeUnionUserInfo;
use frontend\models\i500_social\TradeUnionStreetCommunity;

class BaseTradeUnionController extends BaseController {
    //工会id
    public $trade_union_id = 0;
    //工会信息
    public $trade_union_info = [];
    
    /**
     * 初始化
     * @return array
     */
    public function init() {
        parent::init();
        $this->_getTradeUnion();
    }
    
    //获取当前用户所属工会信息
    private function _getTradeUnion() {
        $TradeUnionUser =  TradeUnionUser::find()->where(["mobile"=>$this->mobile])->asArray()->one();
        if(empty($TradeUnionUser)) $this->returnJsonMsg(404,'',"该会员未绑定工会");
        
        $this->trade_union_info = TradeUnion::find()->where(["id"=>$TradeUnionUser["trade_union_id"]])->asArray()->one();
        if(empty($this->trade_union_info)) $this->returnJsonMsg(404,'',"工会不存在");
        
        $this->trade_union_id = $this->trade_union_info['id'];
    }
    
    //获取当前用户工会社区聊天群id
    public function getGroupId() {
        //工会社区id
        $tcommunity_id = TradeUnionUserInfo::find()->select(['trade_union_street_community_id'])->where(['mobile'=>$this->mobile])->scalar();
        //工会社区id为空
        if (empty($tcommunity_id)) {
            return false;
        }
        //工会社区名称
        $tcommunity_name = TradeUnionStreetCommunity::find()->select(['name'])->where(['id'=>$tcommunity_id])->scalar();
        //工会社区名为空
        if (empty($tcommunity_name)) {
            return false;
        }
        
        //获取工会群id
        $group_id = Group::find()->select(['group_id'])->where(['owner_group'=>'3','source'=>1,'trade_union_id'=>$tcommunity_id])->scalar();
        //工会不存在，则创建
        if (empty($group_id)) {
            //创建工会群并且把当前工会的所有人都加入进来
            $model = TxyunHelper::Create_group('admin','Public',$tcommunity_name.'群',Common::C('defaultGroup'));
            $model = json_decode($model,true);
            if (empty($model) || $model['ActionStatus']!='OK') {
                return false;
            }
            //创建工会群
            $group = new Group();
            $group->group_id = $model['GroupId'];
            $group->trade_union_id = $tcommunity_id;
            $group->name = $tcommunity_name.'群';
            $group->image = Common::C('defaultGroup');
            $group->desc = '';
            $group->is_deleted = 2;
            $group->owner_group = 3;
            $group->source = 1;
            $res = $group->save();
            //创建失败
            if (empty($res)) {
                return false;
            }
            $group_id = $group->group_id;
        }

        $group_mobile = GroupMember::find()->select(['id'])->where(['group_id'=>$group_id,'mobile'=>$this->mobile,'is_deleted'=>2])->scalar();
        //未在工会群组成员内
        if (empty($group_mobile)) {
            $user = User::find()->select(['id'])->where(['mobile'=>$this->mobile])->asArray()->one();
            $userinfo = UserBasicInfo::find()->select(['mobile','realname','avatar'])->where(['mobile'=>$this->mobile])->asArray()->one();
            //用户资料为空
            if (empty($user) || empty($userinfo)) {
                return false;
            }
            //加入腾讯云
            $join_in = TxyunHelper::Join_group($group_id, [$user['id']]);
            $join_in = json_decode($join_in, true);
            if (empty($join_in) || $join_in['ActionStatus']!='OK') {
                return false;
            }
            //加入群组
            $group_member = new GroupMember();
            $group_member->group_id = $group_id;
            $group_member->mobile = $userinfo['mobile'];
            $group_member->nickname = $userinfo['realname'];
            $group_member->role = 2;
            $res = $group_member->save();
            //加入失败
            if (empty($res)) {
                return false;
            }
        }
        
        return $group_id;
    }
}
