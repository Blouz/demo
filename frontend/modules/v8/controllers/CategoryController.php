<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\modules\v8\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\UserBasicInfo;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\db\Query;
/**
 * Service
 *
 * @category Social
 * @package  Service

 */
class CategoryController extends BaseController
{
    public function actionServiceCategory()
    {
		$cate = new ServiceCategory();
		$field=array();
		$field[]='id';
		$field[]='name';
		$field[]='image';
		
		$condition[ServiceCategory::tableName().'.pid'] = 0;
		$condition[ServiceCategory::tableName().'.is_topic'] = '2';
		$condition[ServiceCategory::tableName().'.status'] = '2';
		$condition[ServiceCategory::tableName().'.is_deleted'] = '2';
		
		$result = $cate->find()->select($field)
							   ->where($condition)
							   ->asArray()
							   ->all();
							   
		for($i=0;$i<count($result);$i++)
		{
			$result[$i]['image'] = Common::C('imgHost').$result[$i]['image'];
		}
		$this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));
    }
}