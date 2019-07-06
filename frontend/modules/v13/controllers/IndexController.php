<?php
/**
 * 公共接口
 * PHP Version 5.6
 * @category  Social
 * @package   BASE
 * @author    guanxu <510104561@qq.com>
 * @time      2017/10/9
 */

namespace frontend\modules\v13\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Group;
use frontend\models\i500_social\GroupMember;
use common\helpers\TxyunHelper;
use frontend\models\i500_social\TradeUnion;
use frontend\models\i500_social\TradeUnionStreetCommunity;
use frontend\models\i500_social\TradeUnionUser;
use frontend\models\i500_social\TradeUnionUserInfo;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\User;

class IndexController extends BaseController {


    /**
     * 获取社区
     */
    public function actionCommunity() {


        $name  = RequestHelper::post('name');
        //$TradeUnionUser = TradeUnionUser::findOne(['mobile'=>$this->mobile])->toArray();
        $TradeUnionUser = TradeUnionUser::find()->where(["mobile"=>$this->mobile])->asArray()->one();
        $TradeUnionStreetCommunity = TradeUnionStreetCommunity::find()->where(["trade_union_id"=>$TradeUnionUser["trade_union_id"]]);
       // if(!empty($name)) $TradeUnionStreetCommunity->andwhere(['like', 'name', ''.$name.'%', false]);
        if(!empty($name)) $TradeUnionStreetCommunity->andwhere(['like', 'name', $name]);
        $list = $TradeUnionStreetCommunity->select("id,name,first_letter")->orderBy('first_letter ASC,name ASC,id ASC')->asArray()->all();
        if(!empty($list)) {
            $this->returnJsonMsgNew(200,["list"=>$list,"trade_id"=>$TradeUnionUser["trade_union_id"]],"获取成功");
        }else{
            $this->returnJsonMsgNew(404,["list"=>$list,"trade_id"=>$TradeUnionUser["trade_union_id"]],"数据为空");
        }

    }

    /**
     * 获取工会信息
     */

    public function actionTrade() {
        //$TradeUnionUser = TradeUnionUser::findOne(['mobile'=>$this->mobile])->toArray();
        $TradeUnionUser = TradeUnionUser::find()->where(["mobile"=>$this->mobile])->asArray()->one();
        if(!empty($TradeUnionUser)) {
            $TradeUnion = TradeUnion::find()->where(["id"=>$TradeUnionUser["trade_union_id"]])->asArray()->one();
            $return = [
                "url"           => \Yii::$app->params['mHttpsUrl'].'/trade-union/protocol?trade_id='.$TradeUnion['id'],
                "trade_id"      => $TradeUnion["id"],
                "trade_name"    => $TradeUnion["name"],
            ];
            $this->returnJsonMsgNew(200,$return,"获取成功");
        }else{
            $this->returnJsonMsgNew(404,[],"请绑定工会");
        }
    }

    /**
     * 当前会员是否已保存信息
     */
    public function actionTradeUser(){
        $result = TradeUnionUserInfo::find()->where(["mobile"=>$this->mobile])->asArray()->one();
        if(!empty($result)) {
            $this->returnJsonMsgNew(200,[],"信息已保存");
        } else {
            $this->returnJsonMsgNew(404,[],"信息未保存");
        }

    }

    /**
     * 判断该人是否绑定接口，是否保存信息
     */
    public function actionTradeUserInfo() {
        // 是否绑定接口
        //$TradeUnionUser = TradeUnionUser::findOne(['mobile'=>$this->mobile])->toArray();
        $TradeUnionUser = TradeUnionUser::find()->where(['mobile'=>$this->mobile])->asArray()->one();
        if(!empty($TradeUnionUser)) {
            $TradeUnion = TradeUnion::find()->where(["id"=>$TradeUnionUser["trade_union_id"]])->asArray()->one();
            $result = TradeUnionUserInfo::find()->where(["mobile"=>$this->mobile])->asArray()->one();
            $return = [
                "id"      => $TradeUnion["id"],
                "name"    => $TradeUnion["name"],
            ];
            if(!empty($result)) {
                $this->returnJsonMsgNew(200,$return,"工会信息已保存");
            } else {
                $this->returnJsonMsgNew(410,$return,"工会信息未保存");
            }
        }else{
            //$this->returnJsonMsgNew(404,[],"请绑定工会");
            $this->returnJsonMsgNew(410,[
                "id"      => '',
                "name"    => '',
            ],"工会信息未保存");
        }

    }

}