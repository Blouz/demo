<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-20 下午2:09
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v4\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\controllers\RestController;
use frontend\models\shop\ActivityProducts;
use frontend\models\shop\ShopActivity;
use frontend\models\shop\ActProducts;
use frontend\models\shop\ShopCategory;
use frontend\models\shop\ShopProducts;

class ServicecategoryController extends RestController
{
    public $modelClass = 'frontend\models\i500_social\ServiceCategory';
    public function actions(){
        $actions = parent::actions();
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];

        return $actions;
    }

    /**
     * 递归获取所有的分类树
     * @return array
     */
    public function actionList()
    {
        $category_model = new $this->modelClass;
        //$list = $this->findModel(['status'=>2]);
        $cate_list = $category_model->getList(['status'=>2,'type'=>[0, 1]],['id', 'pid','name','image']);


        foreach ($cate_list as $k => $v) {
            $cate_list[$k]['image'] = Common::formatImg($v['image']);
        }

        $category_tree = $category_model->getChildList($cate_list);
        foreach ($category_tree as $k => $v) {
            if (count($v['child']) == 0) {
                unset($category_tree[$k]);
            }
        }

        // $this->code = 201;
        $this->result['data'] = array_values($category_tree);
        return $this->response();

    }
    public function actionChilds($pid)
    {
        $category_model = new $this->modelClass;
        //$list = $this->findModel(['status'=>2]);
        $map = ['pid'=>$pid,'status'=>2, 'type'=>[0, 1]];
        $cate_list = $category_model->getList($map, ['id', 'pid','name','image']);
        if (!empty($cate_list)) {
            foreach ($cate_list as $k => $v) {
                $cate_list[$k]['image'] = Common::formatImg($v['image']);
            }
            $this->result['data'] = $cate_list;
        }

        return $this->result;
    }

}