<?php
/**
 * 一行的文件介绍
 *
 * PHP Version 5
 * 可写多行的文件相关说明
 *
 * @category  I500M
 * @package   Member
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15/8/26 下午4:32
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v1\controllers;

use common\helpers\RequestHelper;
use frontend\models\shop\ActivityProducts;
use frontend\models\shop\ShopActivity;
use frontend\models\shop\ActProducts;
use frontend\models\shop\ShopCategory;
use frontend\models\shop\ShopProducts;

class CategoryController extends BaseController
{
    public function actionIndex()
    {
        $model = new ShopCategory();
        $this->shop_id = RequestHelper::get('shop_id', 0, 'intval');
        //$menu[] = ['name'=>'热门推荐', 'id'=>'hot','type'=>'hot'];
        $menu = [];
        //读取活动
//        $activity = new ShopActivity();
//        $activity_name = $activity->getActivityName($this->shop_id);
//        //var_dump($activity_name);exit();
//        foreach($activity_name as $k => $v) {
//            $menu[] = ['name'=>$v['name'], 'id'=>$v['type'], 'type'=>'activity'];
//        }


        //获取分类
        $item = $model->getCategory(['shop_id'=>$this->shop_id]);
        //var_dump($item);
        $product_model = new ShopProducts();

        foreach ($item as $k => $v) {
            $count = $product_model->getCount(['shop_id'=>$this->shop_id, 'cat_id'=>$v['id'], 'status'=>1]);
            //var_dump($count);
            if ($count > 0) {
                $menu[] = ['name'=>$v['name'], 'id'=>$v['id'], 'type'=>'category'];
            }
        }
        $this->returnJsonMsg(200, $menu, 'SUCCESS');
    }
}
