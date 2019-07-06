<?php

/* 
 * 
 * @category  Social
 * @package   Post
 * @author    wangleilei <wangleilei@i500m.com>
 * @time      2017
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wangleilei@i500m.com
 */

namespace frontend\modules\v11\controllers;


use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Section;
use frontend\models\i500_social\Group;
use frontend\models\i500_social\TradeUnion;
use frontend\models\i500_social\TradeUnionUser;
use frontend\models\i500_social\TradeUnionUserInfo;

class SectionController extends BaseController
{
    public function actionSectionList()
    {
        $mobile = RequestHelper::post('mobile', '', 'trim');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $community_id = RequestHelper::post('community_id', '', 'intval');
        if (empty($community_id)) {
            $this->returnJsonMsg('799', [],'社区id不能为空');
        }
        
        $res = Section::find()->select(['title','image','url','community_id','param','type'])
                              ->where(['community_id'=>$community_id])
                              ->orWhere(['community_id'=>0])
                              ->andWhere(['status'=>1])
                              ->orderBy('sort asc,id desc')
                              ->offset(0)
                              ->limit(20)
                              ->asArray()
                              ->all();

        foreach ($res as $key=>$val) {
            //邻居议事厅,param为业主群id码
            if ($val['type']==13) {
                $group_id = Group::find()->select('group_id')->where(['community_id'=>$community_id,'owner_group'=>1])->scalar();
                $val['param'] = empty($group_id) ? '' : $group_id;
                $res[$key] = $val;
            }else if($val["type"] == 19) {
                // 社区工会
                $TradeUnionUser = TradeUnionUser::find()->where(["mobile"=>$this->mobile])->asArray()->one();
                if(empty($TradeUnionUser)) {
                    // 没有绑定工会直接 退出+清空
                    unset($res[$key]);
                    continue;
                }
                $TradeUnionUserInfo = TradeUnionUserInfo::find()->where(["mobile"=>$this->mobile])->asArray()->one();
                $TradeUnion = TradeUnion::find()->where(["id"=>$TradeUnionUser["trade_union_id"]])->asArray()->one();
                $res[$key]["title"] = $TradeUnion["name"];
                // 如果有信息 说明已经填写资料
                if(empty($TradeUnionUserInfo)) {
                    $res[$key]["param"] = 0;
                }else{
                    $res[$key]["param"] = 1;
                }
            }
        }
        $res = array_values($res);
        $this->returnJsonMsg('200', $res, Common::C('code', '200'));

    }
}