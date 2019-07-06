<?php
/**
 * MySupplyController.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/6/22 14:46
 */

namespace frontend\modules\v12\controllers;


use common\helpers\Common;
use yii\data\Pagination;
use common\helpers\RequestHelper;
use frontend\models\i500_social\ShouldSupply;

class MySupplyController extends BaseShouldController
{
    /**
     * 我的服务列表
     *
     * @return array
     */
    public function actionIndex(){
        //页数
        $page = RequestHelper::post('page', '1', 'intval');
        //个数
        $size = RequestHelper::post('page_size', '10', 'intval');

        //我的服务列表
        $supply = new ShouldSupply();
        $supply_data = $supply->SupplyList(
            [],
            [
                'status'=>[1,2],
                'mobile'=>$this->mobile,
                'community_id'=>$this->community_id
            ],
            $page, $size,
            [
                'id','mobile','title','content','price','unit'
            ]
        );
        if (!empty($supply_data)) {
            foreach ($supply_data as $key => $value) {
                if($value['photo']){
                    $supply_data[$key]['photo'] = [current($value['photo'])];
                }
            }
        }
        //计算服务数量
        $count = $supply->SupplyCount([], ['status'=>[1,2],'mobile'=>$this->mobile,'community_id'=>$this->community_id]);
        //查询页数
        $pages = new Pagination(['totalCount' => $count]);
        $pages->setPageSize($size, true);
        //返回数据
        $data = [];
        $data['list'] = $supply_data;
        $data['count'] = $count;
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
}