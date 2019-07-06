<?php
/**
 * 商城商品
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/8/25
 */

namespace frontend\modules\v13\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\PrivilegeGoods;
use frontend\models\i500_social\PrivilegeSpecification;
use yii\data\Pagination;
use frontend\models\i500_social\PrivilegeShopCarts;

class ShopGoodsController extends BasePrivilegeController {
    //预加载
    public function beforeAction($action) {
        //无特权 
        if (!$this->checkPrivilege()) {
            $this->returnJsonMsg('2200', [] , Common::C('code', '2200'));
        }
        return parent::beforeAction($action);
    }

    /**
     * 商品列表
     * @return array
     */
    public function actionIndex()
    {
        $where = [];
        $recommend_list = [];
        //默认销量排序
        $search = RequestHelper::post('search','','trim');
        if (!empty($search)) {
            $where = ['like','title',$search];
        }

        //价格排序
        $price_sort = RequestHelper::post('price_sort','','intval');
        if ($price_sort == 1) {//ASC
            $order = 'price ASC,sales_num DESC';
        } else if($price_sort == 2) {//DESC
            $order = 'price DESC,sales_num DESC';
        } else {
            $order = 'sales_num DESC,sort DESC';
        }
        $page = RequestHelper::post('page','1','intval');
        $this->pageSize = RequestHelper::post('page_size',$this->pageSize,'intval');
        //搜索商品以及下一页的时候不查询推荐商品
        if (empty($search) && $page == 1) {
            //推荐商品最多20个
            $recommend_list = PrivilegeGoods::find()->select(['id','title','price','sales_num'])
                              ->with(['picture'=>function($query){
                                  $query->select(['g_id','image']);
                              }])
                              ->where(['status'=>2,'recommend'=>1])
                              ->orderBy('sales_num DESC,sort DESC')
                              ->limit(20)
                              ->asArray()
                              ->all();
            foreach ($recommend_list as $key=>$val) {
                $recommend_list[$key]['image'] = isset($val['picture']['image'])?$val['picture']['image']:'';
                unset($recommend_list[$key]['picture']);
            }
        }

        //全部商品列表
        $model = PrivilegeGoods::find()->select([PrivilegeGoods::tableName().'.id','title','price','sales_num'])
                 ->With(['picture'=>function($query){
                     $query->select(['g_id','image']);
                 }])
                 ->where(['status'=>2])
                 ->andWhere($where);
        $count = $model->count();
        $list =  $model->offset(($page-1)*$this->pageSize)
                 ->limit($this->pageSize)
                 ->orderBy($order)
                 ->asArray()
                 ->all();
        foreach ($list as $key=>$val) {
            $list[$key]['image'] = isset($val['picture']['image'])?$val['picture']['image']:'';
            unset($list[$key]['picture']);
        } 
        //分页
        $pages = new Pagination(['totalCount' => $count,'pageSize'=>$this->pageSize]);

        $data = [];
        $data['recommend_list'] = $recommend_list;
        $data['list'] = $list;
        $data['count'] = $count;
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     * 商品详情
     * @return array
     */
    public function actionDetails()
    {
        $id = RequestHelper::post('id','','intval');
        if (empty($id)) {
            $this->returnJsonMsg('2134',[],Common::C('code','2134'));
        }
        //详情
        $item = PrivilegeGoods::find()->select(['id','title','price','sales_num','total_num','status'])
                ->with(['photo'=>function($query){
                    $query->select(['g_id','image']);
                }])
                ->where(['id'=>$id])
                ->asArray()
                ->one();
        if (empty($item)) {
            $this->returnJsonMsg('404',[],Common::C('code','404'));
        }
        //查询规格ID
        $s_id = PrivilegeSpecification::find()->select(['id'])->where(['g_id'=>$id])->asArray()->scalar();
        $s_id = empty($s_id)?'0':$s_id;

        $data = [];
        $item['s_id'] = $s_id;
        $item['href'] = \Yii::$app->params['mHttpsUrl'].'/privilege-goods/detail?id='.$item['id'];
        $item['declare_content'] = \Yii::$app->params['mHttpsUrl'].'/store/index';
        $data['item'] = $item;
        //购物车数量
        $data['cartcount'] = PrivilegeShopCarts::getCartCount($this->mobile);
        
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
}