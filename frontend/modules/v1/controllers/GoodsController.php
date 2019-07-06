<?php
/**
 * 商品
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Goods
 * @author    renyineng<renyineng@iyangpin.com>
 * @time      2015/8/25
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v1\controllers;

use frontend\models\i500m\Brand;
use frontend\models\i500m\Category;
use frontend\models\i500m\Product;
use frontend\models\i500m\ProductImage;
use frontend\models\shop\ActivityGoods;
use frontend\models\shop\ShopActivity;
use frontend\models\shop\ShopProducts;
use Yii;
use common\helpers\Common;
use common\helpers\CurlHelper;
use common\helpers\SsdbHelper;
use common\helpers\RequestHelper;
use yii\helpers\ArrayHelper;

/**
 * Goods
 *
 * @category Social
 * @package  Shop
 * @author   linxinliang <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class GoodsController extends BaseController
{
    public $enableCsrfValidation = false;
    public $pageSize = 20;

    /**
     * 商品列表
     * @return json
     */
    public function actionIndex()
    {
        $cat_id = RequestHelper::get('cat_id', 0, 'intval');
        $this->shop_id = RequestHelper::get('shop_id', 0, 'intval');
        if (empty($cat_id)) {
            $this->returnJsonMsg(102, [], '分类id必须!');
        }
        if (empty($this->shop_id)) {
            $this->returnJsonMsg(103, [], '商家id无效!');
        }
        $shop_models = new ShopProducts();
        $map = ['shop_id'=>$this->shop_id, 'cat_id'=>$cat_id, 'status'=>1];
        $data_list = $shop_models->getGoodsList($map, 'product_id,price,product_number,status,activity_id,activity_json', $this->pageSize);
        //var_dump($data_list);exit();
        $goods_id = $goods_list = $goods = $shop_products = [];
        $activity_product_ids = $activity_ids = $activity_goods = [];
        $goods_arr = [];
        if (!empty($data_list['list'])) {
            $now = date("Y-m-d H:i:s");
            foreach ($data_list['list'] as $k => $v) {

                if (!empty($v['activity_id']) && !empty($v['activity_json'])) {

                    $data_arr = json_decode($v['activity_json'], true);
                    if (!empty($data_arr)) {

                        //此活动有效
                        if ($data_arr['start_time'] < $now && $data_arr['end_time'] > $now) {
                            $activity_ids[] = $v['activity_id'];
                            $activity_product_ids[] = $v['product_id'];
                        }
                    }

                }
                $goods_id[] = $v['product_id'];
                //$shop_products[$v['product_id']] = $v;
            }
            if (!empty($activity_product_ids) && !empty($activity_ids)) {
                $activity_model = new ActivityGoods();
                $activity_goods = $activity_model->getActivityGoodsList($activity_ids, $activity_product_ids, $this->shop_id);
            }
            if (!empty($goods_id)) {
                $models = new Product();
                $goods = $models->getList(['id'=>$goods_id, 'status'=>1], 'id,name,image,bar_code,attr_value');
            }
            //var_dump($goods);
            $goods = ArrayHelper::index($goods, 'id');
            //var_dump($goods);
            $img_path = Yii::$app->params['imgHost'];
            if (substr($img_path, -1) == '/') {
                $img_path = substr($img_path, 0, -1);
            }

            foreach ($data_list['list'] as $k => $v) {
                $goods_arr[$k]['product_id'] = $v['product_id'];
                $goods_arr[$k]['product_number'] = $v['product_number'];

                $goods_arr[$k]['price'] = ArrayHelper::getValue($activity_goods, $v['product_id'].'.price', $v['price']);
                $goods_arr[$k]['name'] = ArrayHelper::getValue($goods, $v['product_id'].'.name', '');
                $goods_arr[$k]['attr_value'] = ArrayHelper::getValue($goods, $v['product_id'].'.attr_value', '');
                $goods_arr[$k]['image'] = $img_path . ArrayHelper::getValue($goods, $v['product_id'].'.image', '');
                $goods_arr[$k]['purchase_num'] = ArrayHelper::getValue($activity_goods, $v['product_id'].'.day_confine_num', 0);

                $goods_arr[$k]['origin_num'] = 0;


            }
            $pageCount = ArrayHelper::getValue($data_list, 'pageCount', 0);
            $count = ArrayHelper::getValue($data_list, 'count', 0);
            $this->returnJsonMsg(200, ['item'=>$goods_arr, 'pageCount'=>$pageCount, 'count'=>$count], '获取成功');

        } else {
            $this->returnJsonMsg(101, [], '无商品!');
        }

    }

    /**
     * 商品列表
     * @return json
     */
    public function actionIndexIos()
    {
        $cat_id = RequestHelper::get('cat_id', 0, 'intval');
        $this->shop_id = RequestHelper::get('shop_id', 0, 'intval');
        if (empty($cat_id)) {
            $this->returnJsonMsg(102, [], '分类id必须!');
        }
        if (empty($this->shop_id)) {
            $this->returnJsonMsg(103, [], '商家id无效!');
        }
        $shop_models = new ShopProducts();
        $map = ['shop_id'=>$this->shop_id, 'cat_id'=>$cat_id, 'status'=>1];
        $data_list = $shop_models->getList($map, 'product_id,price,product_number,status,activity_id,activity_json');
        //var_dump($data_list);exit();
        $goods_id = $goods_list = $goods = $shop_products = [];
        $activity_product_ids = $activity_ids = $activity_goods = [];
        $goods_arr = [];
        if (!empty($data_list)) {
            $now = date("Y-m-d H:i:s");
            foreach ($data_list as $k => $v) {

                if (!empty($v['activity_id']) && !empty($v['activity_json'])) {

                    $data_arr = json_decode($v['activity_json'], true);
                    if (!empty($data_arr)) {

                        //此活动有效
                        if ($data_arr['start_time'] < $now && $data_arr['end_time'] > $now) {
                            $activity_ids[] = $v['activity_id'];
                            $activity_product_ids[] = $v['product_id'];
                        }
                    }

                }
                $goods_id[] = $v['product_id'];
                //$shop_products[$v['product_id']] = $v;
            }
            if (!empty($activity_product_ids) && !empty($activity_ids)) {
                $activity_model = new ActivityGoods();
                $activity_goods = $activity_model->getActivityGoodsList($activity_ids, $activity_product_ids, $this->shop_id);
            }
            if (!empty($goods_id)) {
                $models = new Product();
                $goods = $models->getList(['id'=>$goods_id, 'status'=>1], 'id,name,image,bar_code,attr_value');
            }
            //var_dump($goods);
            $goods = ArrayHelper::index($goods, 'id');
            //var_dump($goods);
            $img_path = Yii::$app->params['imgHost'];
            if (substr($img_path, -1) == '/') {
                $img_path = substr($img_path, 0, -1);
            }

            foreach ($data_list as $k => $v) {
                $goods_arr[$k]['product_id'] = $v['product_id'];
                $goods_arr[$k]['product_number'] = $v['product_number'];

                $goods_arr[$k]['price'] = ArrayHelper::getValue($activity_goods, $v['product_id'].'.price', $v['price']);
                $goods_arr[$k]['name'] = ArrayHelper::getValue($goods, $v['product_id'].'.name', '');
                $goods_arr[$k]['attr_value'] = ArrayHelper::getValue($goods, $v['product_id'].'.attr_value', '');
                $goods_arr[$k]['image'] = $img_path . ArrayHelper::getValue($goods, $v['product_id'].'.image', '');
                $goods_arr[$k]['purchase_num'] = ArrayHelper::getValue($activity_goods, $v['product_id'].'.day_confine_num', 0);

                $goods_arr[$k]['origin_num'] = 0;


            }
            //$pageCount = ArrayHelper::getValue($data_list, 'pageCount', 0);
            //$count = ArrayHelper::getValue($data_list, 'count', 0);
            $this->returnJsonMsg(200, ['item'=>$goods_arr], '获取成功');

        } else {
            $this->returnJsonMsg(101, [], '无商品!');
        }

    }

    /**
     * 商品详情
     * @return array
     */
    public function actionDetail()
    {
        $product_id = RequestHelper::get('id', 0, 'intval');
        $this->shop_id = RequestHelper::get('shop_id', 0, 'intval');
        if (empty($product_id)) {
            $this->returnJsonMsg(101, [], '无效的商品id!');
        }
        if (empty($this->shop_id)) {
            $this->returnJsonMsg(102, [], '无效的商家id!');
        }
        $shop_model = new ShopProducts();
        $goods_info = $shop_model->getInfo(['shop_id'=>$this->shop_id, 'product_id'=>$product_id]);
        if ($goods_info['status'] != 1) {
            $this->returnJsonMsg(102, [], '此商品已经下架!');
        }
        $model = new Product();
        $info = $model->getInfo(['id'=>$product_id], 'name,description,status,attr_value');
        if ($info['status'] != 1) {
            $this->returnJsonMsg(102, [], '官方下架!');
        }
        $cat_model = new Category();
        //$cat_model->
        $cat_name = $cat_model->getField('name', ['id'=>$info['cate_first_id']]);
        $brand_model = new Brand();
        $brand_name = $brand_model->getField('name', ['id'=>$info['brand_id']]);
        //过滤商品介绍
        $html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"><html><head><meta http-equiv="content-type" content="text/html; charset=UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
            <style type="text/css">
            img{max-width: 100%;}
</style>
            </head>
            <body>';
        $html .= $info['description'];
        $html .= '</body></html>';
        $info['description'] = urlencode(str_replace('\"', '', htmlspecialchars_decode($html)));
        $p_info = [
            'name'=>$info['name'],
            'price'=>$goods_info['price'],
            'cat_name'=>$cat_name,
            'brand'=>$brand_name,
            'activity_id'=>0,
            'activity_name'=>'',
            'subtitle'=>'',
            'activity_price'=>0,
            'purchase_num'=>0,
            'attribute'=>$info['attr_value'],
            'description'=>$info['description'],
        ];
        $p_img = new ProductImage();
        $image_data = [];
        $image_list = $p_img->getList(['product_id'=>$product_id], 'image');
        $img_path = Yii::$app->params['imgHost'];
        if (substr($img_path, -1) == '/') {
            $img_path = substr($img_path, 0, -1);
        }
        if (!empty($image_list)) {
            foreach ($image_list as $k => $v) {
                $image_data[] = $img_path . $v['image'];
            }
        }
        $activity_info = [];
        if (!empty($goods_info['activity_id']) && !empty($goods_info['activity_json'])) {
            $data_arr = json_decode($goods_info['activity_json'], true);
            if (!empty($data_arr)) {
                $now = date("Y-m-d H:i:s");
                //此活动有效
                if ($data_arr['start_time'] < $now && $data_arr['end_time'] > $now) {
                    $activity_model = new ActivityGoods();
                    $activity_info = $activity_model->getActivityGood($this->shop_id, $product_id, $goods_info['activity_id']);
                }
            }


        }
//        if (!empty($activity_info)) {
//            $p_info['activity_id'] = $activity_info['activity_id'];
//            $p_info['activity_name'] = $activity_info['name'];
//            $p_info['subtitle'] = $activity_info['subtitle'];
//            $p_info['activity_price'] = $activity_info['activity_price'];
//            $p_info['purchase_num'] = $activity_info['purchase_num'];
//        }
        $p_info['photo'] = $image_data;
        $this->returnJsonMsg(200, $p_info, 'SUCCESS');
    }

    /**
     * 获取热门推荐
     * @return json
     */
    public function actionHot()
    {
        $model = new Product();
        $this->shop_id = RequestHelper::get('shop_id', 0, 'intval');
        $list = $model->getList(['is_hot'=>1, 'single'=>1, 'status'=>1], 'id,name,image,attr_value');
        $new_list = $goods_list = [];
        if (!empty($list)) {
            $goods_id = [];
            foreach ($list as $k => $v) {
                $goods_id[] = $v['id'];
                $new_list[$v['id']] = $v;
            }
            $img_path = Yii::$app->params['imgHost'];
            if (substr($img_path, -1) == '/') {
                $img_path = substr($img_path, 0, -1);
            }
            if (!empty($goods_id)) {
                $shop_model = new ShopProducts();
                $goods_list = $shop_model->getList(['product_id'=>$goods_id, 'shop_id'=>$this->shop_id, 'status'=>1], 'product_id,product_number,price');
                if (!empty($goods_list)) {
                    foreach ($goods_list as $k => $v) {
                        $goods_list[$k]['name'] =  ArrayHelper::getValue($new_list, $v['product_id'].'.name', '');
                        $goods_list[$k]['attr_value'] = ArrayHelper::getValue($new_list, $v['product_id'].'.attr_value', '');
                        $goods_list[$k]['image'] = $img_path . ArrayHelper::getValue($new_list, $v['product_id'].'.image', '');
                        $goods_list[$k]['purchase_num'] = 0;
                        $goods_list[$k]['origin_num'] = 0;
                    }
                }
            }
        }
        $this->returnJsonMsg(200, $goods_list, 'SUCCESS');
    }

    /**
     * 根据商家id 和活动类型 获取正在进行的所有活动
     * @return json
     */
    public function actionActivity()
    {
        $this->shop_id = RequestHelper::get('shop_id', 0, 'intval');
        $type = RequestHelper::get('type', 0, 'intval');
        $activity = new ShopActivity();
        if (empty($this->shop_id)) {
            $this->returnJsonMsg(101, [], '无效的商家id!');
        }
        if (empty($type)) {
            $this->returnJsonMsg(102, [], '无效的活动类型!');
        }
        $goods = $activity->getPurchaseGoods($this->shop_id, $type);
        $this->returnJsonMsg(200, $goods, 'SUCCESS');
       // var_dump($goods);
    }

    /**
     * Banner图片
     * @return json
     */
    public function actionBanner()
    {
        $this->shop_id = RequestHelper::get('shop_id', 0, 'intval');
        $model = new ShopActivity();
        $data = $model->getCurrentActivity($this->shop_id);
        $img_path = Yii::$app->params['imgHost'];
        if (substr($img_path, -1) == '/') {
            $img_path = substr($img_path, 0, -1);
        }
        $banner = [];
        foreach ($data as $k => $v) {
            $banner[$k] = [
                'image'=>$img_path . $v['images']
            ];
        }

        $this->returnJsonMsg(200, $banner, 'SUCCESS');
    }
}
