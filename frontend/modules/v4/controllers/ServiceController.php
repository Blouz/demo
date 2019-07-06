<?php
/**
 * 服务
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Service
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/9/14
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v4\controllers;

use frontend\controllers\RestController;
use frontend\models\i500_social\Recruit;
use frontend\models\i500m\Community;
use Yii;
use common\helpers\Common;
use common\helpers\SsdbHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\ServiceSetting;
use frontend\models\i500_social\UserBasicInfo;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;

/**
 * Service
 *
 * @category Social
 * @package  Service
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class ServiceController extends RestController
{
    public $modelClass = 'frontend\models\i500_social\Service';
    /**
     * Before
     * @param \yii\base\Action $action Action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
//    public function afterAction($action)
//    {
//     //   $this->response();
////        $this->returnJsonMsg($this->code, $this->data, $this->message);
//    }
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
            //return $this->response();
        }

        if (!empty($category_id)) {
            $map[Service::tableName().'.category_id'] = $category_id;
            //var_dump($category_id);exit();
        }
        if (!empty($child_id)) {
            $map[Service::tableName().'.son_category_id'] = $child_id;
        }
//        $map[UserBasicInfo::tableName().'.last_community_city_id'] = $community_city_id;
//        $map['last_community_id'] = $community_id;
        $map[Service::tableName().'.community_city_id'] = $community_city_id;

        $map[Service::tableName().'.community_id'] = $community_id;
        $map[Service::tableName().'.audit_status'] = 2;
        $map[Service::tableName().'.status'] = 1;
        $map['is_recruit'] = 1;
        //$model = new $modelClass;
        $fields = [UserBasicInfo::tableName().'.id', UserBasicInfo::tableName().'.mobile','nickname','avatar','personal_sign','community_id','community_city_id'];
        //$data = $model->getPageItem($map, [], $fields);
        //var_dump($map);exit();
        $query = UserBasicInfo::find()->select($fields)->innerJoinWith('service')->where($map)->groupBy([UserBasicInfo::tableName().'.mobile']);
        //echo $query->createCommand()->sql;exit();
        $countQuery = clone $query;
        $count = $countQuery->count();
        $pages = new Pagination(['totalCount' =>$count, 'pageSize' => 20]);

        $data['item'] = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();
      //  var_dump($data['item']);exit();
//        if(isset($config['order'])) {
//            $rows = $rows->orderBy($config['order']);
//        }
       // var_dump($data);exit();
        if (!empty($data['item'])) {
            foreach ($data['item'] as $k => $v) {
                if (empty($v['nickname'])) {
                    $data['item'][$k]['nickname'] = $v['mobile'];
                }
                unset($data['item'][$k]['service']);
            }
        }
        $data['pageCount'] = $pages->pageCount;
        $data['count'] = $count;
        $this->result['data'] = $data;
        return $this->result;
        //return $this->response();
    }
    /**
     * 发布服务
     * @return array
     */
    public function actionAdd()
    {
        $model = new Service();
        $model->attributes = Yii::$app->request->post();
        if ($model->validate()) {
            if ($model->save()) {
                //echo 233;exit();
            } else {
                $this->result['code'] = 400;
                $this->result['message'] = Common::C('code', '400');
            }
            // 若所有输入都是有效的
        } else {
            // 有效性验证失败：$errors 属性就是存储错误信息的数组
            $errors = $model->getFirstErrors();
            $error = array_values($errors);
            $this->result['code'] = 511;
            $this->result['message'] = ArrayHelper::getValue($error, 0, 'Error');
        }
        return $this->result;
    }

    /**
     * 编辑服务
     * @return array
     */
    public function actionEdit()
    {
        $id = RequestHelper::post('id', 0, 'intval');
        $mobile = RequestHelper::post('mobile', 0);
        if (empty($mobile) || empty($id)) {
            $this->result['code'] = 512;
            $this->result['message'] = '无效的参数';
            return;
        }
        $model = Service::findOne($id);
        if (empty($model)) {
            $this->result['code'] = 404;
            $this->result['message'] = '数据不存在';
        } else {
            $model->attributes = Yii::$app->request->post();
            if (!$model->save()) {
                $errors = $model->getFirstErrors();
                $error = array_values($errors);
                $this->result['code'] = 511;
                $this->result['message'] = ArrayHelper::getValue($error, 0, 'Error');
            }
        }
        //var_dump($model->attributes);exit();
        return $this->result;

    }

    /**
     * 服务详情
     * @return array
     */
    public function actionDetail()
    {
        $where['id'] = RequestHelper::get('service_id', '0', 'intval');
        if (empty($where['id'])) {
            $this->returnJsonMsg('1010', [], Common::C('code', '1010'));
        }
        $type = RequestHelper::get('type', '0', 'intval');
        if (empty($type)) {
            $this->returnJsonMsg('1008', [], Common::C('code', '1008'));
        }
        $fields = '*';
        if ($type == '1') {
            $lat = RequestHelper::get('lat', '0', '');
            if (empty($lat)) {
                $this->returnJsonMsg('1057', [], Common::C('code', '1057'));
            }
            $lng = RequestHelper::get('lng', '0', '');
            if (empty($lng)) {
                $this->returnJsonMsg('1056', [], Common::C('code', '1056'));
            }
            /**在首页或服务广场页查看服务详情**/
            $where['status']               = '1';
            $where['user_auth_status']     = '1';
            $where['servicer_info_status'] = '1';
            $where['audit_status']         = '2';
            $where['is_deleted']           = '2';
            $fields = 'id,uid,category_id,son_category_id,image,title,price,unit,service_way,description';
        } elseif ($type =='2') {
            /**在我的服务中查看服务详情**/
//            $where['uid'] = RequestHelper::get('uid', '', '');
//            if (empty($where['uid'])) {
//                $this->returnJsonMsg('621', [], Common::C('code', '621'));
//            }
            $where['mobile'] = RequestHelper::get('mobile', '', '');
            if (empty($where['mobile'])) {
                $this->returnJsonMsg('604', [], Common::C('code', '604'));
            }
            if (!Common::validateMobile($where['mobile'])) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
            $where['is_deleted']   = '2';
            $fields = 'id,category_id,son_category_id,image,title,price,unit,service_way,description,status,audit_status';
        } else {
            $this->returnJsonMsg('1014', [], Common::C('code', '1014'));
        }
        $service_model = new Service();
        $info = $service_model->getInfo($where, true, $fields);
        if (empty($info)) {
            $this->returnJsonMsg('1011', [], Common::C('code', '1011'));
        }
//        if ($info['image']) {
//            $info['image'] = $this->_formatImg($info['image']);
//        }
        $info['price'] = $info['price'].$this->_getServiceUnit($info['unit']);
        unset($info['unit']);
        if ($type == '1') {
            /**获取服务设置信息**/
            $service_setting_where['uid']          = $info['uid'];
            $service_setting_where['status']       = '2';
            $service_setting_where['is_deleted']   = '2';
            $service_setting_fields = 'uid,mobile,name,search_address,lat,lng';
            $service_setting_model = new ServiceSetting();
            $service_setting_info = $service_setting_model->getInfo($service_setting_where, true, $service_setting_fields);
            if (empty($service_setting_info)) {
                $this->returnJsonMsg('1015', [], Common::C('code', '1015'));
            }
            if (!empty($service_setting_info['mobile'])) {
                $user_info = $this->_getUserInfo($service_setting_info['mobile']);
                $service_setting_info['user_avatar'] = $user_info['avatar'];
            }
            $service_setting_info['star']     = '5';
            //计算距离
            $service_setting_info['distance'] = Common::getDistance($lat, $lng, $service_setting_info['lat'], $service_setting_info['lng']);
            $info['service_setting'] = $service_setting_info;
            unset($info['uid']);
            unset($info['service_setting']['lat']);
            unset($info['service_setting']['lng']);
        }
        $this->returnJsonMsg('200', $info, Common::C('code', '200'));
    }

    /**
     * 删除服务
     * @return array
     */
    /**
     * 删除服务
     * @return array
     */
    public function actionDel()
    {
        $mobile = RequestHelper::post('mobile', 0);
        if (empty($mobile)) {
            $this->result['code'] = 512;
            $this->result['message'] = Common::C('code', '512');
        }
        $ids = Yii::$app->request->post('ids');

        if (!empty($ids)) {
            $ids_arr = explode(',', $ids);
            if (!empty($ids_arr)) {
                // $model = new Seek();
                //  var_dump($ids_arr);
                $re = Service::deleteAll(['mobile'=>$mobile, 'id'=>$ids_arr]);
                if ($re == 0) {
                    $this->result['message'] = '已经删除或不存在';
                }

                $this->result['data'] = $re;
            }
        } else {
            $this->result['code'] = 601;
            $this->result['message'] = '无效的服务id';

        }
        return $this->result;
        //return $this->result;
    }

    /**
     * 更新上下架
     * @return array
     */
    public function actionUpdateStatus()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $ids = RequestHelper::post('service_ids', '');
        $status = RequestHelper::post('status', '0', 'intval');
        if (!in_array($status, [1,2])) {
            $this->result['code'] = 601;
            $this->result['message'] = '状态无效';
            return;
        }
        if (empty($ids)) {
            $this->result['code'] = 602;
            $this->result['message'] = '无效的服务id';
            return;
        } else {
            $ids_arr = explode(',', $ids);
            if (!empty($ids_arr)) {
                // $model = new Seek();
                //  var_dump($ids_arr);
                $re = Service::updateAll(['status'=>$status], ['mobile'=>$mobile, 'id'=>$ids_arr]);
                $this->result['data'] = $re;
            }
        }
        return $this->result;
    }
    /**
     * 我的服务
     * @return array
     */
    public function actionMyService()
    {

        $mobile = RequestHelper::get('mobile', '', '');
        //$modelClass = $this->modelClass;
        if (empty($mobile)) {
            // $this->code = 201;
            $this->result['code'] = 512;
            $this->result['message'] = Common::C('code', '512');
            return;
//            return $this->response();
        }
        //判断该用户是否招募过
        $recruit_model = Recruit::findOne(['mobile'=>$mobile]);
        if (empty($recruit_model)) {
            $this->result['code'] = 405;
            $this->result['message'] = '请先申请招募';
            return $this->result;
        } else if ($recruit_model->status != 1) {
            $this->result['code'] = 405;
            $this->result['message'] = '请先申请招募';
            return $this->result;
        }
        $modelClass = new Service();
        $map['mobile'] = $mobile;
        $fields = ['id','mobile','category_id','son_category_id','image','price','unit', 'service_description'=>'description', 'community_city_id', 'community_id', 'create_time','status','audit_status'];
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
     * 个人服务主页
     * @return array
     */
    public function actionHomePage()
    {

        $mobile = RequestHelper::get('shop_mobile', '', '');
        $community_id = RequestHelper::get('community_id', '', '');
        $community_city_id = RequestHelper::get('community_city_id', 1, '');
        //$modelClass = $this->modelClass;
        if (empty($mobile) || empty($community_id)) {
            // $this->code = 201;
            $this->result['code'] = 422;
            $this->result['message'] = '无效的参数';
            return $this->result;
//            return $this->response();
        }
        $modelClass = new Service();
        $map['mobile'] = $mobile;
        $map['status'] = 1;
        $map['audit_status'] = 2;
        $map['community_id'] = $community_id;
        $map['community_city_id'] = $community_city_id;
        $fields = ['id','mobile','category_id','son_category_id','image','price','unit', 'service_description'=>'description', 'community_city_id', 'community_id', 'create_time'];
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
     * 获取服务分类
     * @return array
     */
    public function actionGetCategory()
    {
        $type = RequestHelper::get('type', '0', 'intval');
        if (empty($type)) {
            $this->returnJsonMsg('1008', [], Common::C('code', '1008'));
        }
        $info = [];
        //get缓存
        $cache_key = 'service_top_category';
        $cache_rs = SsdbHelper::Cache('get', $cache_key);
        if ($cache_rs) {
            $info = $cache_rs;
        } else {
            $service_category_model = new ServiceCategory();
            $where['pid']        = '0';
            $where['status']     = '2';
            $where['is_deleted'] = '2';
            $fields = 'id,name,image';
            $order  = 'sort desc';
            $info = $service_category_model->getList($where, $fields, $order);
            //set缓存
            SsdbHelper::Cache('set', $cache_key, $info, Common::C('SSDBCacheTime'));
        }
        if (!empty($info)) {
            foreach ($info as $k => $v) {
                if ($v['image']) {
                    $info[$k]['image'] = $this->_formatImg($v['image']);
                }
                //判断子类中是否存在 不存在子类则不展示该分类
                $son = $this->_getSonCategory($v['id']);
                if ($type == '2') {
                    $info[$k]['son'] = $son;
                }
                if ($type == '3') {
                    $info[$k]['son'] = $this->_getSonCategory($v['id'], '1');
                }
                $count = count($son);
//                if ($count == 0) {
//                    unset($info[$k]);
//                }
            }
            $info = array_values($info);
        }
        $this->returnJsonMsg('200', $info, Common::C('code', '200'));
    }

}
