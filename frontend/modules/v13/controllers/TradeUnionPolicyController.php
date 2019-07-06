<?php
/**
 * TradeUnionPolicyControllerController.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * User: huangdekui<huangdekui@i500m.com>
 * Time: 2017/9/26 17:16
 */

namespace frontend\modules\v13\controllers;


use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\TradeUnionPolicy;
use yii\data\Pagination;

class TradeUnionPolicyController extends BaseTradeUnionController
{
    /**
     * 工会政策列表
     * @return array
     */
    public function actionIndex()
    {
        $page = RequestHelper::post('page','1','intval');
        $this->pageSize = RequestHelper::post('page_size',$this->pageSize,'intval');
        //列表
        $model = TradeUnionPolicy::find()->select(['id','name','image','create_time'])
                 ->where(['status'=>1,'trade_unio_id'=>$this->trade_union_id])
                 ->orderBy('id DESC');

        $count = $model->count();
        $list  = $model->offset(($page-1)*$this->pageSize)->limit($this->pageSize)->asArray()->all();
        //分页
        $pages = new Pagination(['totalCount' => $count,'pageSize'=>$this->pageSize]);

        $data = [];
        $data['list'] = $list;
        $data['count'] = $count;
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     * 政策详情
     * @return array
     */
    public function actionDetails()
    {
        $id = RequestHelper::post('id','','intval');
        if (empty($id)) {
            $this->returnJsonMsg('2134',[],Common::C('code','2134'));
        }

        $item = TradeUnionPolicy::find()->select(['id','name','image','create_time'])->where(['id'=>$id])->asArray()->one();
        if (empty($item)) {
            $this->returnJsonMsg('404',[],Common::C('code','404'));
        }
        $item['href'] = \Yii::$app->params['mHttpsUrl'].'/trade-union-policy/details?id='.$id;
        $data = [];
        $data['item'] = $item;
        $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
}