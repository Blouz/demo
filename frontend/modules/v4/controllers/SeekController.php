<?php
/**
 * 求助页面
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   求助
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-18 上午10:36
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v4\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\controllers\AuthRestController;
use frontend\controllers\RestController;
use frontend\models\i500_social\Seek;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500m\Community;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

class SeekController extends RestController
{
    public $modelClass = 'frontend\models\i500_social\Seek';
    public function actions(){
        $actions = parent::actions();
        unset($actions['delete'],$actions['update']);
        // 使用"prepareDataProvider()"方法自定义数据provider
        //$actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];

        return $actions;
    }
    /**
     * 根据小区id 获取小区的需求 显示给服务方抢单
     * @return array|ActiveDataProvider
     */
    public function prepareDataProvider()
    {
        $community_id = RequestHelper::get('community_id', 0, 'intval');
        $community_city_id = RequestHelper::get('community_city_id', 1, 'intval');
       // var_dump($_GET);exit();
        //var_dump($community_id);
        $modelClass = $this->modelClass;
        if (empty($community_id)) {
           // $this->code = 201;
            $this->result['code'] = 513;
            $this->result['message'] = Common::C('code', '513');
            return $this->response();
        }
        $map['community_city_id'] = $community_city_id;
        $map['community_id'] = $community_id;
        return new ActiveDataProvider([
            'query' => $modelClass::find()->where($map),
        ]);
    }
    /**
     * 根据小区id 获取小区的需求 显示给服务方抢单
     * @return array|ActiveDataProvider
     */
    public function actionNear()
    {
        $community_id = RequestHelper::get('community_id', 0, 'intval');
        $community_city_id = RequestHelper::get('community_city_id', 1, 'intval');
        $category_id = RequestHelper::get('category_id', 0, 'intval');
        $child_id = RequestHelper::get('child_id', 0, 'intval');
//        $lng = RequestHelper::get('lng', 0, 'floatval');
//        $lat = RequestHelper::get('lat', 0, 'floatval');
        //var_dump($community_id);
        $modelClass = $this->modelClass;
        if (empty($community_id) || empty($community_city_id)) {
            // $this->code = 201;
            $this->result['code'] = 511;
            $this->result['message'] = Common::C('code', '511');
            return $this->response();
        }
        if (!empty($category_id)) {
            $map['category_id'] = $category_id;
            //var_dump($category_id);exit();
        }
        if (!empty($child_id)) {
            $map['son_category_id'] = $child_id;
        }

        $map['community_city_id'] = $community_city_id;
        $map['community_id'] = $community_id;
       // $map['audit_status'] = 2;
        $map['status'] = 1;
        $map['is_receive'] = 0;//显示未被抢单的
        $model = new $modelClass;
        $fields = ['id','mobile','category_id','son_category_id','image','price','unit', 'is_receive', 'seeks_description'=>'description', 'community_city_id', 'community_id', 'create_time'];
        //$data = $model->getPageItem($map, [], $fields);
        $query = $model::find()->select($fields)->where($map);
        $data = $this->getPagedRows($query,['order'=>'id desc']);
        if (!empty($data['item'])) {
            //$data['item'] = ArrayHelper::toArray($data['item']);
            //var_dump($data);exit();
            //获取分类
            $category = ServiceCategory::find()->select(['id','name','image'])->asArray()->all();
            //$category = ArrayHelper::map($category, 'id','name');
            $category = ArrayHelper::index($category, 'id');
           // var_dump($category);exit();
            $community_ids = $mobiles = [];
            foreach ($data['item'] as $k => $v) {
                $community_ids[] = $v['community_id'];
                $mobiles[] = $v['mobile'];
            }
            $community_list = Community::find()->select(['id','name','lng','lat'])->where(['id'=>$community_ids])->asArray()->all();
            if (!empty($community_list)) {
                $community_list = ArrayHelper::index($community_list, 'id');
            }
            $user_list = UserBasicInfo::find()->select(['mobile','nickname','avatar'])->where(['mobile'=>$mobiles])->asArray()->all();
            if (!empty($user_list)) {
                $user_list = ArrayHelper::index($user_list, 'mobile');
            }
            foreach ($data['item'] as $k => $v) {
                $data['item'][$k]['nick_name'] = ArrayHelper::getValue($user_list, $v['mobile'].'.nickname', '');
                $data['item'][$k]['avatar'] = ArrayHelper::getValue($user_list, $v['mobile'].'.avatar', '');
                $data['item'][$k]['category_name'] = ArrayHelper::getValue($category, $v['category_id'].'.name', '');
                $data['item'][$k]['category_child_name'] = ArrayHelper::getValue($category, $v['son_category_id'].'.name', '');
                $category_child_image = ArrayHelper::getValue($category, $v['son_category_id'].'.image', '');
                if (empty($category_child_image)) {
                    $data['item'][$k]['category_image'] = Common::formatImg(ArrayHelper::getValue($category, $v['category_id'].'.image', ''));

                } else {
                    $data['item'][$k]['category_image'] = Common::formatImg($category_child_image);
                }
                $data['item'][$k]['community_name'] = ArrayHelper::getValue($community_list, $v['community_id'].'.name', '');
//                $data['item'][$k]['lng'] = $v['lng'] = ArrayHelper::getValue($community_list, $v['community_id'].'.lng', 0);
//                $data['item'][$k]['lat'] = $v['lat'] = ArrayHelper::getValue($community_list, $v['community_id'].'.lat', 0);
//                $data['item'][$k]['distance'] = Common::getDistance($lat, $lng,$v['lat'], $v['lng']);
                //$data['item'][$k]['category_image'] = Common::formatImg(ArrayHelper::getValue($category, $v['category_id'].'.image', ''));
                $data['item'][$k]['love_time'] = Common::formatTime($v['create_time']);
            }
        }
       // var_dump($list);exit();
        $this->result['data'] = $data;
       return $this->response();
    }
    /**
     * 我的需求
     * @return array
     */
    public function actionList()
    {
      //  var_dump($_GET);exit();
        $mobile = RequestHelper::get('mobile', 0);
        $modelClass = $this->modelClass;
        if (empty($mobile)) {
            // $this->code = 201;
            $this->result['code'] = 512;
            $this->result['message'] = Common::C('code', '512');
            return $this->response();
        }
        $map['mobile'] = $mobile;
        $fields = ['id','mobile','category_id','son_category_id','image','price','unit', 'seeks_description'=>'description', 'community_city_id', 'community_id', 'create_time','status','sendtime'];
        $data = $modelClass::find()->select($fields)->where($map)->orderBy('id desc')->asArray()->all();
        //$model = $provider->getModels();
        if (!empty($data)) {
            //获取分类
            $category = ServiceCategory::find()->select(['id','name','image'])->asArray()->all();
            $category = ArrayHelper::index($category, 'id');
            //var_dump($category);exit();
            $community_ids = [];
            foreach ($data as $k => $v) {
                $community_ids[] = $v['community_id'];
            }
            $community_list = Community::find()->select(['id','name','lng','lat'])->where(['id'=>$community_ids])->asArray()->all();
            if (!empty($community_list)) {
                $community_list = ArrayHelper::index($community_list, 'id');
            }
            foreach ($data as $k => $v) {
                $category_image = ArrayHelper::getValue($category, $v['son_category_id'].'.image', '');

                if (empty($category_image)) {
                    $category_image = ArrayHelper::getValue($category, $v['category_id'].'.image', '');
                }
                $data[$k]['category_name'] = ArrayHelper::getValue($category, $v['category_id'].'.name', '');
                $data[$k]['category_child_name'] = ArrayHelper::getValue($category, $v['son_category_id'].'.name', '');
                $data[$k]['category_image'] = Common::formatImg($category_image);
                $data[$k]['community_name'] = ArrayHelper::getValue($community_list, $v['community_id'].'.name', '');
                $data[$k]['love_time'] = Common::formatTime($v['create_time']);
//                $data['item'][$k]['lng'] = $v['lng'] = ArrayHelper::getValue($community_list, $v['community_id'].'.lng', 0);
//                $data['item'][$k]['lat'] = $v['lat'] = ArrayHelper::getValue($community_list, $v['community_id'].'.lat', 0);
//                $data['item'][$k]['distance'] = Common::getDistance($lat, $lng,$v['lat'], $v['lng']);
            }
        }
        $this->result['data'] = $data;
        return $this->result;
    }

    /**
     * 删除服务
     * @return array
     */
    public function actionDelete()
    {
        $mobile = RequestHelper::get('mobile', 0);
        if (empty($mobile)) {
            $this->result['code'] = 512;
            $this->result['message'] = Common::C('code', '512');
        }
        $ids = Yii::$app->request->get('ids');

        if (!empty($ids)) {
            $ids_arr = explode(',', $ids);
            if (!empty($ids_arr)) {
               // $model = new Seek();
              //  var_dump($ids_arr);
                $re = Seek::deleteAll(['mobile'=>$mobile, 'id'=>$ids_arr]);
                $this->result['data'] = $re;
            }
        } else {
            $this->result['code'] = 601;
            $this->result['message'] = '无效的需求';

        }
        return $this->result;
    }
    public function actionEdit()
    {
        $id = RequestHelper::post('id', 0, 'intval');
        $mobile = RequestHelper::post('mobile', 0);
        if (empty($mobile) || empty($id)) {
            $this->result['code'] = 512;
            $this->result['message'] = '无效的参数';
            return $this->response();
        }
        $map['mobile'] = $mobile;
        $map['id'] = $id;

        $model = $this->findModel($map);
        if (empty($model)) {
            $this->result['code'] = 404;
            $this->result['message'] = '对象不存在';
        } else {
            $model->attributes = Yii::$app->request->post();
            if (!$model->save()) {
                $errors = $model->getFirstErrors();
                $error = array_values($errors);
                $this->result['code'] = 511;
                $this->result['message'] = ArrayHelper::getValue($error, 0, 'Error');
            }
        }
        return $this->result;

    }
}