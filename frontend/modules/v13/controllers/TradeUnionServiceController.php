<?php
/**
 * 工会会员服务信息
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/10/8
 */

namespace frontend\modules\v13\controllers;

use common\helpers\Common;
use frontend\models\i500_social\TradeUnionService;
use common\helpers\RequestHelper;
use yii\data\Pagination;

class TradeUnionServiceController extends BaseTradeUnionController {
    //服务信息列表
    public function actionList() {
        $page = RequestHelper::post('page','1','intval');
        $this->pageSize = RequestHelper::post('page_size',$this->pageSize,'intval');
        
        $model = TradeUnionService::find()->select(['id','name','banner0'])->where(['status'=>1,'trade_unio_id'=>$this->trade_union_id]);
        $count = $model->count();
        $list =  $model->offset(($page-1)*$this->pageSize)->limit($this->pageSize)->orderBy('create_time desc')->asArray()->all();
        //分页
        $pages = new Pagination(['totalCount' => $count,'pageSize'=>$this->pageSize]);
        
        $data['list'] = $list;
        $data['count'] = $count;
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        
        $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
    
    //服务信息详情
    public function actionDetails() {
        $id = RequestHelper::post('id',0,'intval');
        if (empty($id)) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        //服务信息详情
        $detail = TradeUnionService::find()->select(['id','name','banner','create_time'])->where(['id'=>$id])->asArray()->one();
        if (empty($detail)) {
            $this->returnJsonMsg('404',[],Common::C('code','404'));
        }
        $detail['banner'] = @json_decode($detail['banner'],true);
        $detail['banner'] = empty($detail['banner']) ? [] : $detail['banner'];
        $detail['href'] = \Yii::$app->params['mHttpsUrl'].'/trade-union-service/details?id='.$detail['id'];
        $data['item'] = $detail;
        
        $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
    
}