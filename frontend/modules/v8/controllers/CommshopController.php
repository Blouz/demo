<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\modules\v8\controllers;

///use frontend\controllers\RestController;
use frontend\models\i500_social\Recruit;
use frontend\models\i500m\Community;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\UserBasicInfo;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use common\helpers\CurlHelper;
use  frontend\models\i500m\Shop;
use frontend\models\i500m\Category;
use yii\db\Query;
/**
 * Service
 *
 * @category Social
 * @package  Service

 */
class CommshopController extends BaseController
{
    public function actionGetCommunityshop()
    {
        $mobile=RequestHelper::post('mobile', '', '');
        $city_id=RequestHelper::post('city_id', '', ''); //所属城市id
	if(empty($city_id))
	{
            $this->returnJsonMsg('2001',[], '城市id不能为空');	
	}
        $comm_id=RequestHelper::post('community_id', '', '');  //所属社区id
        if(empty($comm_id))
	{
            $this->returnJsonMsg('2002',[], '社区id不能为空');	
	}
        $shop = new Shop();
         
        $field=array();
        $field[]='shop.id';
        $field[]='shop.logo as shop_img';
        $field[]='shop.shop_name';
        $field[]='shop.address';
        $field[]='shop.sent_fee';
        $field[]='shop.free_money';
        $field[]='shop.freight';
        

        
        $condition['shop_community.community_id'] = $comm_id;
//        $condition['shop_community.city_id'] = $city_id;

        if(!empty($mobile))
        {
            $condition[Service::tableName().'.mobile'] = $mobile;
        }
        
        $result = $shop->find()->select($field)
                                ->join('LEFT JOIN','shop_community','shop_community.shop_id=shop.id')
                                ->andwhere($condition)
//                                ->offset($page)
//                                ->limit(10)
                                ->asArray()
                                ->all();
        $this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));
        
    }
    
    public function actionOpenShop()
    {
        $tel = $mobile=RequestHelper::post('telphone', '', '');
        $res = Recruit::updateAll(['be_merchant'=>1],['telphone'=>$tel]);
        $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
    }
    public function actionProductType()
    {
        $pid=RequestHelper::post('pid', '', ''); //分类id
        if(empty($pid))
	{
            $this->returnJsonMsg('2334',[], '主分类id不能为空');	
	}
        $category = new Category();
        $res = $category->find()->select(['id','name'])->where(['parent_id'=>$pid])->asArray()->all();
        $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
    }
    
}