<?php
/**
 * WalkPrizeController.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/8/1 9:46
 */

namespace frontend\modules\v12\controllers;


use common\helpers\Common;
use yii\data\Pagination;
use common\helpers\RequestHelper;
use frontend\models\i500_social\WalkPrizeClaim;
use frontend\models\i500_social\WalkActivityPart;
use frontend\models\i500_social\WalkTeam;

class WalkPrizeController extends BaseWalkController
{
    /**
     * 我的奖品列表
     * @return array
     */
    public function actionIndex()
    {
        //分页
        $page = RequestHelper::post('page', 1, 'intval');
        //个数
        $limit = RequestHelper::post('limit', 20, 'intval');
        //队伍id
        $team_id = WalkActivityPart::find()->select(['team_id'])->where(['mobile'=>$this->mobile,'is_team'=>1])->asArray()->column();
        $team_id = empty($team_id) ? 0 : $team_id;
        //我获得的奖品
        $model = WalkPrizeClaim::find()->select(['id','ntitle','start_time','end_time','status','type'])
                 ->where(['type'=>1,'type_key'=>$this->mobile])
                 ->orWhere(['type'=>4,'type_key'=>$team_id]);
        $count = $model->count();
        $list = $model->offset(($page-1)*$limit)->limit($limit)->asArray()->all();
        foreach ($list as $key=>$val) {
            $val['start_time'] = date('Y年n月j日', strtotime($val['start_time']));
            $val['end_time'] = date('Y年n月j日', strtotime($val['end_time']));
            $list[$key] = $val;
        }
        $pages = new Pagination(['totalCount' => $count]);
        $pages->setPageSize($limit, true);
        $data['list'] = $list;
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     * 我的奖品详情
     * @return array
     */
    public function actionDetails()
    {
        //奖品ID
        $id = RequestHelper::post('id','','intval');
        if (empty($id)) {
            return $this->returnJsonMsg('2134',[],Common::C('code','2134'));
        }
        //队伍id
        $team_id = WalkActivityPart::find()->select(['team_id'])->where(['mobile'=>$this->mobile,'is_team'=>1])->asArray()->column();
        $team_id = empty($team_id) ? 0 : $team_id;
        //查询详情
        $item = WalkPrizeClaim::find()->select(['id','ntitle','type_level_name','ptitle','pcontent','prules','image','start_time','end_time','code','status','type','claim_time'])
                 ->where(['type'=>1,'type_key'=>$this->mobile])
                 ->orWhere(['type'=>4,'type_key'=>$team_id])
                 ->andWhere(['id'=>$id])
                 ->asArray()
                 ->one();
        if (empty($item)) {
            return $this->returnJsonMsg('2135',[],Common::C('code','2135'));
        }
        $item['start_time'] = date('Y年n月j日', strtotime($item['start_time']));
        $item['end_time'] = date('Y年n月j日', strtotime($item['end_time']));
        //团队日冠军
        $item['claim_time'] = date('Y年n月j日', strtotime($item['claim_time']));
        $team_name = WalkTeam::find()->select(['name'])->where(['id'=>$team_id])->scalar();
        $item['team_name'] = empty($team_name)?'':$team_name;
        $data['item'] = $item;
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
}