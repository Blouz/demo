<?php
/**
 * 群聊天
 * User: Administrator
 * Date: 2016/11/07
 */

namespace frontend\modules\v8\controllers;

use common\helpers\BaseRequestHelps;
use common\helpers\Common;
use common\helpers\LoulianHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\GroupStation;
use frontend\models\i500_social\GroupStationRoom;
use frontend\models\i500_social\GroupRoute;
class GroupController extends BaseController
{
     /**
      * 创建群组,加入群组
      * @param string $mobile 手机号
      * @return array
      * @author xuxiaoyu <huangdekui@i500m.com>
      */
    public function actionGroupIndex()
    {
        $mobile = BaseRequestHelps::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $route_id = BaseRequestHelps::post('route_id','','');
        if (empty($route_id)) {
            $this->returnJsonMsg('5002',[],'路线ID不能为空');
        }
        $station_id = BaseRequestHelps::post('station_id','','');
        if (empty($station_id)) {
            $this->returnJsonMsg('5003',[],'车站ID不能为空');
        }
        //查询站的名字
        $station = GroupStation::find()->select(['station_name'])->where(['route_id'=>$route_id,'id'=>$station_id])->asArray()->one();
        //查询群组ID
        $loulian_group = GroupStationRoom::find()->select(['loulian_group_id'])->where(['route_id'=>$route_id,'station_id'=>$station_id])->asArray()->one();
        //查询用户信息
        $loulian_basicinfo = UserBasicInfo::find()->select(['mobile','nickname','avatar'])->where(['mobile'=>$mobile])->asArray()->one();
        if (empty($loulian_group)) {
            $group = LouLianHelper::CreateGroup($mobile,$mobile,$station['station_name'],'');
            if (!empty($group) && $group['error_code'] == 2000) {
                $room = new GroupStationRoom();
                $room -> route_id = $route_id;
                $room -> station_id = $station_id;
                $room -> loulian_group_id = $group['id'];
                $room -> mobile = $mobile;
                $res = $room -> save();
                if (!$res) {
                    return $this->returnJsonMsg('500',[],'网络繁忙');
                }
                $loulian_group = array('loulian_group_id' => $group['id']);
            }else {
                return $this->returnJsonMsg('501',[],'加入群失败');
            }
        }else{ 
            $group =  LouLianHelper::InsertGroup($loulian_group['loulian_group_id'],$mobile);
            //var_dump($group);exit();
            if(empty($group)){
               return $this->returnJsonMsg('501',[],'加入群失败');
            }
        }
        return  $this->returnJsonMsg('200',$loulian_group,Common::C('code','200'));
    }
    /**
     * 退出群组
     * @return array
     */
    public function actionGroupDelete(){
        $mobile = BaseRequestHelps::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $group_info = GroupStationRoom::find()->select(['mobile','loulian_group_id'])->where(['mobile'=>$mobile])->asArray()->one();
        $group =  LouLianHelper::DeleteGroup($group_info['loulian_group_id'],$mobile);
        return  $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
} 