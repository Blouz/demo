<?php
/**
 * 工会类
 * PHP Version 5.6
 * @category  Social
 * @package   BASE
 * @author    guanxu <510104561@qq.com>
 * @time      2017/10/9
 */

namespace frontend\modules\v13\controllers;

use common\helpers\FastDFSHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\TradeUnion;
use frontend\models\i500_social\TradeUnionStreet;
use frontend\models\i500_social\TradeUnionStreetCommunity;
use frontend\models\i500_social\TradeUnionUser;
use frontend\models\i500_social\TradeUnionUserInfo;
use frontend\models\i500_social\Group;
use frontend\models\i500_social\UserBasicInfo;
use common\helpers\TxyunHelper;

class TradeUnionController extends BaseTradeUnionController {

    /**
     * 会员信息保存
     */
    public function actionUser() {

        $name  = trim(RequestHelper::post('name'));
        $card  = trim(RequestHelper::post('card'));
        $id    = RequestHelper::post('id');
        // i500_trade_union_user_info
        if(empty($name)) $this->returnJsonMsg("404","","姓名不能为空！");
        if(empty($id)) $this->returnJsonMsg("404","","请选择社区！");
        if(empty($card)) $this->returnJsonMsg("404","","请输入正确的会员卡号！");
        $TradeUnionUserInfo = TradeUnionUserInfo::find()->where(["id"=>$this->uid])->all();
        if(!empty($TradeUnionUserInfo)) $this->returnJsonMsg("404","","该用户已补全信息！");
        $TradeUnionStreetCommunity = TradeUnionStreetCommunity::find()->where(["id"=>$id])->one();
        if(empty($TradeUnionStreetCommunity)) $this->returnJsonMsg("404","","该社区不存在！");
        if(!preg_match("/^\d*$/",$card)) $this->returnJsonMsg("404","","请输入正确的会员卡号");
        $model = new TradeUnionUserInfo();
        $model->user_id = $this->uid;
        $model->mobile  = $this->mobile;
        $model->card    = $card;
        $model->trade_union_street_community_id = $id;
        $model->create_time = date("Y-m-d H:i:s");
        $model->save(false);
        UserBasicInfo::updateAll(['nickname'=>$name,'realname'=>$name,'update_time'=>date('Y-m-d H:i:s')],['mobile'=>$this->mobile]);
        //修改用户腾讯云信息
        TxyunHelper::Edit_userinfo($this->uid,['nickname'=>$name]);
        //加入工会群
        $this->getGroupId();
        $this->returnJsonMsg(200,"","保存成功！");
    }

    /**
     * 工会主页面
     */
    public function actionIndex(){
        $list = TradeUnionUser::find()->where(["mobile"=>$this->mobile])->asArray()->one();
        if(empty($list)) {
            $this->returnJsonMsgNew(404,"","对不起，请绑定所属工会");
        }else{
            $trade = TradeUnion::find()->where(["id"=>$list["trade_union_id"]])->asArray()->one();
           // $this->returnJsonMsgNew(200,["phone"=>$trade["phone"],"img"=>$trade["content_img"]],"获取成功");
            //获取工会群id
            $group_id = $this->getGroupId();
            $group_name = empty($group_id) ? '' : Group::find()->select(['name'])->where(['group_id'=>$group_id])->scalar();
            $data = [
                "phone" => $trade["phone"],
                "img" => $trade["content_img"],
                'group_id' => empty($group_id)?'':$group_id,
                'group_name' => empty($group_name)?'':$group_name,
            ];
            $this->returnJsonMsgNew(200,$data,"获取成功");
        }
    }



    public function actionCommunity() {
        $list = TradeUnionUser::find()->where(["mobile"=>$this->mobile])->asArray()->one();
        if(empty($list)) {
            $this->returnJsonMsg(404,"","对不起，请绑定所属工会");
        }else{
            //$trade = TradeUnion::find()->where(["id"=>$list["trade_union_id"]])->asArray()->one();
            $result     = TradeUnionStreet::find()->where(["trade_union_id"=>$list["trade_union_id"]])->select("id ,name")->asArray()->all();
            $TradeUnion = TradeUnion::find()->where(["id"=>$list["trade_union_id"]])->asArray()->one();
            foreach($result as $key => $val) {
                $result[$key]["list"] = TradeUnionStreetCommunity::find()->where(["trade_union_street_id"=>$val["id"]])->select("id ,name")->asArray()->all();
            }
            $this->returnJsonMsgNew(200,["list"=>$result,"name"=>$TradeUnion["name"]],"获取成功");
        }

    }



}