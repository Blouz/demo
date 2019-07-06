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
namespace frontend\modules\v3\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\SsdbHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\ServiceUnit;
use frontend\models\i500_social\ServiceSetting;
use frontend\models\i500_social\ServiceWeekTime;
use frontend\models\i500_social\UserBasicInfo;
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
class ServiceController extends BaseController
{
    /**
     * Before
     * @param \yii\base\Action $action Action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function afterAction($action)
    {
        $this->returnJsonMsg($this->code, $this->data, $this->message);
    }
    /**
     * 服务主页
     * @return array
     */
    public function actionIndex()
    {
        $uuid = RequestHelper::get('uuid', '0', 'intval');
        if (empty($uuid)) {
            $this->returnJsonMsg('1035', [], Common::C('code', '1035'));
        }
        $rs['service_setting'] = [];
        $rs['service_list']    = [];
        $type = RequestHelper::get('type', '0', 'intval');
        if ($type != '1') {
            /**获取服务设置信息**/
            $service_setting_where['uid']          = $uuid;
            $service_setting_where['status']       = '2';
            $service_setting_where['is_deleted']   = '2';
            $service_setting_fields = 'mobile,name';
            $service_setting_model = new ServiceSetting();
            $service_setting_info = $service_setting_model->getInfo($service_setting_where, true, $service_setting_fields);
            if (empty($service_setting_info)) {
                $this->returnJsonMsg('1015', [], Common::C('code', '1015'));
            }
            if (!empty($service_setting_info['mobile'])) {
                $user_info = $this->_getUserInfo($service_setting_info['mobile']);
                $service_setting_info['user_avatar'] = $user_info['avatar'];
                $service_setting_info['user_sex']    = $user_info['sex'];
                $service_setting_info['user_auth']   = $user_info['card_audit_status'];
            }
            $rs['service_setting'] = $service_setting_info;
        }
        $page      = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '6', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        $service_model = new Service();
        $service_where['uid']                  = $uuid;
        $service_where['audit_status']         = '2';
        $service_where['user_auth_status']     = '1';
        $service_where['servicer_info_status'] = '1';
        $service_where['status']               = '1';
        $service_where['is_deleted']           = '2';
        $service_fields = 'id,mobile,image,title,description as service_description,price,unit,service_way';
        $list = $service_model->getPageList($service_where, $service_fields, 'id desc', $page, $page_size);
        if (!empty($list)) {
            foreach ($list as $k => $v) {
//                if ($v['image']) {
//                    $list[$k]['image'] = $this->_formatImg($v['image']);
//                }
                $list[$k]['price'] = $v['price'].$this->_getServiceUnit($v['unit']);
                unset($list[$k]['mobile']);
                unset($list[$k]['unit']);
            }
        }
        $rs['service_list'] = $list;
        $this->returnJsonMsg('200', $rs, Common::C('code', '200'));
    }

    /**
     * 检测用户是否验证过
     * @return array
     */
    public function actionCheckUserAuth()
    {
        $where['uid'] = RequestHelper::post('uid', '', '');
        if (empty($where['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $service_setting_model = new ServiceSetting();
        $info = $service_setting_model->getInfo($where, true, 'status');
        if (empty($info)) {
            $this->returnJsonMsg('1015', [], Common::C('code', '1015'));
        }
        if ($info['status'] != '2') {
            $this->returnJsonMsg('1044', [], Common::C('code', '1044'));
        }
        $user_info = $this->_getUserInfo($where['mobile']);
        if ($user_info['card_audit_status'] != '2') {
            if ($user_info['card_audit_status'] == '0') {
                $this->returnJsonMsg('1059', [], Common::C('code', '1059'));
            }
            if ($user_info['card_audit_status'] == '1') {
                $this->returnJsonMsg('1060', [], Common::C('code', '1060'));
            }
            if ($user_info['card_audit_status'] == '3') {
                $this->returnJsonMsg('1061', [], Common::C('code', '1061'));
            }
            //!=2 表示审核不成功
            $this->returnJsonMsg('1052', [], Common::C('code', '1052'));
        }
        $rs_info['user_name'] = $user_info['realname'];
        $this->returnJsonMsg('200', $rs_info, Common::C('code', '200'));
    }
    /**
     * 发布服务
     * @return array
     */
    public function actionAdd()
    {
        $data['uid'] = RequestHelper::post('uid', '', '');
        if (empty($data['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $data['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($data['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($data['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $data['category_id'] = RequestHelper::post('category_id', '0', 'intval');
        if (empty($data['category_id'])) {
            $this->returnJsonMsg('1000', [], Common::C('code', '1000'));
        }
        $data['son_category_id'] = RequestHelper::post('son_category_id', '0', 'intval');
        if (empty($data['son_category_id'])) {
            $this->returnJsonMsg('1001', [], Common::C('code', '1001'));
        }
        $data['image'] = RequestHelper::post('image', '', '');
        if (empty($data['image'])) {
            $this->returnJsonMsg('1002', [], Common::C('code', '1002'));
        }
        $data['title'] = RequestHelper::post('title', '', '');
        if (empty($data['title'])) {
            $this->returnJsonMsg('1003', [], Common::C('code', '1003'));
        }
        $data['price'] = RequestHelper::post('price', '', '');
        if (empty($data['price'])) {
            $this->returnJsonMsg('1004', [], Common::C('code', '1004'));
        }
        $data['unit'] = RequestHelper::post('unit', '0', 'intval');
        if (empty($data['unit'])) {
            $this->returnJsonMsg('1005', [], Common::C('code', '1005'));
        }
        $data['service_way'] = RequestHelper::post('service_way', '0', 'intval');
        if (empty($data['service_way'])) {
            $this->returnJsonMsg('1006', [], Common::C('code', '1006'));
        }
        $data['description'] = RequestHelper::post('description', '', '');
        if (empty($data['description'])) {
            $this->returnJsonMsg('1007', [], Common::C('code', '1007'));
        }
        $data['community_id'] = RequestHelper::post('community_id', '0', 'intval');
        if (empty($data['community_id'])) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        $data['community_city_id'] = RequestHelper::post('community_city_id', '0', 'intval');
        if (empty($data['community_city_id'])) {
            $this->returnJsonMsg('645', [], Common::C('code', '645'));
        }
        $status = $this->_getSettingInfo($data['mobile'], 'status', 1);
        if ($status == '2') {
            /**servicer_info_status=1表示服务人(店铺)信息审核成功**/
            $data['servicer_info_status'] = '1';
        } else {
            $this->returnJsonMsg('1044', [], Common::C('code', '1044'));
            /**servicer_info_status=2表示服务人(店铺)信息不审核成功**/
            $data['servicer_info_status'] = '2';
        }
        $user_info = $this->_getUserInfo($data['mobile']);
        if ($user_info['card_audit_status'] == '2') {
            /**user_auth_status=1用户认证状态成功**/
            $data['user_auth_status'] = '1';
        } else {
            //@todo 20151020 未进行实名认证也可以发布服务
            //$this->returnJsonMsg('1052', [], Common::C('code', '1052'));
            /**user_auth_status=2用户认证状态失败**/
            $data['user_auth_status'] = '2';
        }
        $service_model = new Service();
        $rs = $service_model->insertInfo($data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 编辑服务
     * @return array
     */
    public function actionEdit()
    {
        $data['uid'] = RequestHelper::post('uid', '', '');
        if (empty($data['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $data['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($data['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($data['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['id'] = RequestHelper::post('service_id', '0', 'intval');
        if (empty($where['id'])) {
            $this->returnJsonMsg('1010', [], Common::C('code', '1010'));
        }
        $where['mobile']       = $data['mobile'];
        $where['uid']          = $data['uid'];
        $where['is_deleted']   = '2';
        $data['category_id'] = RequestHelper::post('category_id', '0', 'intval');
        if (empty($data['category_id'])) {
            $this->returnJsonMsg('1000', [], Common::C('code', '1000'));
        }
        $data['son_category_id'] = RequestHelper::post('son_category_id', '0', 'intval');
        if (empty($data['son_category_id'])) {
            $this->returnJsonMsg('1001', [], Common::C('code', '1001'));
        }
        $data['image'] = RequestHelper::post('image', '', '');
        if (empty($data['image'])) {
            $this->returnJsonMsg('1002', [], Common::C('code', '1002'));
        }
        $data['title'] = RequestHelper::post('title', '', '');
        if (empty($data['title'])) {
            $this->returnJsonMsg('1003', [], Common::C('code', '1003'));
        }
        $data['price'] = RequestHelper::post('price', '', '');
        if (empty($data['price'])) {
            $this->returnJsonMsg('1004', [], Common::C('code', '1004'));
        }
        $data['unit'] = RequestHelper::post('unit', '0', 'intval');
        if (empty($data['unit'])) {
            $this->returnJsonMsg('1005', [], Common::C('code', '1005'));
        }
        $data['service_way'] = RequestHelper::post('service_way', '0', 'intval');
        if (empty($data['service_way'])) {
            $this->returnJsonMsg('1006', [], Common::C('code', '1006'));
        }
        $data['description'] = RequestHelper::post('description', '', '');
        if (empty($data['description'])) {
            $this->returnJsonMsg('1007', [], Common::C('code', '1007'));
        }
        $data['update_time'] = date('Y-m-d H:i:s', time());
        $service_model = new Service();
        $fields = 'id,audit_status';
        $info = $service_model->getInfo($where, true, $fields);
        if (empty($info)) {
            $this->returnJsonMsg('1011', [], Common::C('code', '1011'));
        }
        //审核状态 0=未审核1=审核中2=审核成功3=审核失败
        if ($info['audit_status'] == '1') {
            $this->returnJsonMsg('1020', [], Common::C('code', '1020'));
        }
        $data['audit_status'] = '0';
        $status = $this->_getSettingInfo($data['mobile'], 'status', 1);
        if ($status == '2') {
            /**servicer_info_status=1表示服务人(店铺)信息审核成功**/
            $data['servicer_info_status'] = '1';
        } else {
            $this->returnJsonMsg('1044', [], Common::C('code', '1044'));
            /**servicer_info_status=2表示服务人(店铺)信息审核成功**/
            $data['servicer_info_status'] = '2';
        }
        $user_info = $this->_getUserInfo($data['mobile']);
        if ($user_info['card_audit_status'] == '2') {
            /**user_auth_status=1用户认证状态成功**/
            $data['user_auth_status'] = '1';
        } else {
            //@todo 20151020 未进行实名认证也可以发布服务
            //$this->returnJsonMsg('1052', [], Common::C('code', '1052'));
            /**user_auth_status=2用户认证状态失败**/
            $data['user_auth_status'] = '2';
        }
        $rs = $service_model->updateInfo($data, $where);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
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
//        $where['community_id'] = RequestHelper::get('community_id', '0', 'intval');
//        if (empty($where['community_id'])) {
//            $this->returnJsonMsg('642', [], Common::C('code', '642'));
//        }
//        $where['community_city_id'] = RequestHelper::get('community_city_id', '0', 'intval');
//        if (empty($where['community_city_id'])) {
//            $this->returnJsonMsg('645', [], Common::C('code', '645'));
//        }
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
            $where['uid'] = RequestHelper::get('uid', '', '');
            if (empty($where['uid'])) {
                $this->returnJsonMsg('621', [], Common::C('code', '621'));
            }
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
    public function actionDel()
    {
        $where['uid'] = RequestHelper::post('uid', '', '');
        if (empty($where['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['id'] = RequestHelper::post('service_id', '0', 'intval');
        if (empty($where['id'])) {
            $this->returnJsonMsg('1010', [], Common::C('code', '1010'));
        }
        $update_data['is_deleted'] = '1';
        $service_model = new Service();
        $rs = $service_model->updateInfo($update_data, $where);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 更新上下架
     * @return array
     */
    public function actionUpdateStatus()
    {
        $where['uid'] = RequestHelper::post('uid', '', '');
        if (empty($where['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['id'] = RequestHelper::post('service_id', '0', 'intval');
        if (empty($where['id'])) {
            $this->returnJsonMsg('1010', [], Common::C('code', '1010'));
        }
        $where['audit_status'] = '2';
        $where['is_deleted']   = '2';
        $update_data['status'] = RequestHelper::post('status', '0', 'intval');
        if (empty($update_data['status'])) {
            $this->returnJsonMsg('1012', [], Common::C('code', '1012'));
        }
        $service_model = new Service();
        $fields = 'id,status';
        $info = $service_model->getInfo($where, true, $fields);
        if (empty($info)) {
            $this->returnJsonMsg('1011', [], Common::C('code', '1011'));
        }
        if ($update_data['status'] == $info['status']) {
            $this->returnJsonMsg('1013', [], Common::C('code', '1013'));
        }
        $rs = $service_model->updateInfo($update_data, $where);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
    /**
     * 获取首页服务
     * @return array
     */
    public function actionGetIndexService()
    {
        $community_id = RequestHelper::get('community_id', '0', 'intval');
        if (empty($community_id)) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        $community_city_id = RequestHelper::get('community_city_id', '0', 'intval');
        if (empty($community_city_id)) {
            $this->returnJsonMsg('645', [], Common::C('code', '645'));
        }
        $lat = RequestHelper::get('lat', '0', '');
        if (empty($lat)) {
            $this->returnJsonMsg('1057', [], Common::C('code', '1057'));
        }
        $lng = RequestHelper::get('lng', '0', '');
        if (empty($lng)) {
            $this->returnJsonMsg('1056', [], Common::C('code', '1056'));
        }
        $page      = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '6', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        $service_model = new Service();
        $where['community_id']         = $community_id;
        $where['community_city_id']    = $community_city_id;
        $where['audit_status']         = '2';
        $where['user_auth_status']     = '1';
        $where['servicer_info_status'] = '1';
        $where['status']               = '1';
        $where['is_deleted']           = '2';
        $fields = 'id,uid,mobile,image,title,price,unit,service_way';
        $list = $service_model->getPageList($where, $fields, 'id desc', $page, $page_size);
        if (empty($list)) {
            $this->returnJsonMsg('1009', [], Common::C('code', '1009'));
        }
        foreach ($list as $k => $v) {
//            if ($v['image']) {
//                $list[$k]['image'] = $this->_formatImg($v['image']);
//            }
            if (!empty($v['mobile'])) {
                $user_info = $this->_getUserInfo($v['mobile']);
                $list[$k]['user_avatar']    = $user_info['avatar'];
                $service_setting_info = $this->_getSettingInfo($v['mobile'], 'search_address,lng,lat', 2);
                $list[$k]['search_address'] = $service_setting_info['search_address'];
                //判断距离
                $list[$k]['distance']       = Common::getDistance($lat, $lng, $service_setting_info['lat'], $service_setting_info['lng']);
            }
            $list[$k]['price'] = $v['price'].$this->_getServiceUnit($v['unit']);
            unset($list[$k]['mobile']);
            unset($list[$k]['unit']);
        }
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }

    /**
     * 获取服务广场
     * @return array
     */
    public function actionGetServiceSquare()
    {
        $type = RequestHelper::get('type', '0', 'intval');
        if (empty($type)) {
            $this->returnJsonMsg('1008', [], Common::C('code', '1008'));
        }
        $list = [];
        if ($type == '1') {
            /**附近**/
        } elseif ($type == '2') {
            /**分类**/
            $where['category_id']     = RequestHelper::get('category_id', '0', 'intval');
            $where['son_category_id'] = RequestHelper::get('son_category_id', '0', 'intval');
            if (empty($where['category_id'])) {
                $this->returnJsonMsg('1000', [], Common::C('code', '1000'));
            }
            if (empty($where['son_category_id'])) {
                unset($where['son_category_id']);
            }
            $community_id = RequestHelper::get('community_id', '0', 'intval');
            if (empty($community_id)) {
                $this->returnJsonMsg('642', [], Common::C('code', '642'));
            }
            $community_city_id = RequestHelper::get('community_city_id', '0', 'intval');
            if (empty($community_city_id)) {
                $this->returnJsonMsg('645', [], Common::C('code', '645'));
            }
            $lat = RequestHelper::get('lat', '0', '');
            if (empty($lat)) {
                $this->returnJsonMsg('1057', [], Common::C('code', '1057'));
            }
            $lng = RequestHelper::get('lng', '0', '');
            if (empty($lng)) {
                $this->returnJsonMsg('1056', [], Common::C('code', '1056'));
            }
            $page      = RequestHelper::get('page', '1', 'intval');
            $page_size = RequestHelper::get('page_size', '6', 'intval');
            if ($page_size > Common::C('maxPageSize')) {
                $this->returnJsonMsg('705', [], Common::C('code', '705'));
            }
            $service_model = new Service();
            $where['community_id']         = $community_id;
            $where['community_city_id']    = $community_city_id;
            $where['audit_status']         = '2';
            $where['user_auth_status']     = '1';
            $where['servicer_info_status'] = '1';
            $where['status']               = '1';
            $where['is_deleted']           = '2';
            $fields = 'id,uid,mobile,image,title,price,unit,service_way';
            $list = $service_model->getPageList($where, $fields, 'id desc', $page, $page_size);
            if (empty($list)) {
                $this->returnJsonMsg('1009', [], Common::C('code', '1009'));
            }
            foreach ($list as $k => $v) {
                if ($v['image']) {
                    $list[$k]['image'] = $this->_formatImg($v['image']);
                }
                if (!empty($v['mobile'])) {
                    $user_info = $this->_getUserInfo($v['mobile']);
                    $list[$k]['user_avatar']    = $user_info['avatar'];
                    $service_setting_info = $this->_getSettingInfo($v['mobile'], 'search_address,lng,lat', 2);
                    $list[$k]['search_address'] = $service_setting_info['search_address'];
                    //判断距离
                    $list[$k]['distance']       = Common::getDistance($lat, $lng, $service_setting_info['lat'], $service_setting_info['lng']);
                }
                $list[$k]['price'] = $v['price'].$this->_getServiceUnit($v['unit']);
                unset($list[$k]['mobile']);
                unset($list[$k]['unit']);
            }
        } else {
            $this->returnJsonMsg('1014', [], Common::C('code', '1014'));
        }
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }

    /**
     * 我的服务
     * @return array
     */
    public function actionMyService()
    {

        $where['mobile'] = RequestHelper::get('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $page      = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '6', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        $service_model = new Service();
        $where['is_deleted']   = '2';
        $fields = 'id,mobile,image,title,description as service_description,price,unit,service_way,audit_status,status';
        $list = $service_model->getPageList($where, $fields, 'id desc', $page, $page_size);
        if (empty($list)) {
            $this->returnJsonMsg('1009', [], Common::C('code', '1009'));
        }
        foreach ($list as $k => $v) {
//            if ($v['image']) {
//                $list[$k]['image'] = $this->_formatImg($v['image']);
//            }
            $list[$k]['price'] = $v['price'].$this->_getServiceUnit($v['unit']);
            unset($list[$k]['mobile']);
            unset($list[$k]['unit']);
        }
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }

    /**
     * 获取设置信息
     * @return array
     */
    public function actionGetSetting()
    {
        $where['uid'] = RequestHelper::get('uid', '', '');
        if (empty($where['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::get('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $fields = 'name,description,province_id,search_address,details_address,lng,lat,status';
        $service_setting_model = new ServiceSetting();
        $info = $service_setting_model->getInfo($where, true, $fields);
        if (empty($info)) {
            $this->returnJsonMsg('1015', [], Common::C('code', '1015'));
        }
        $this->returnJsonMsg('200', $info, Common::C('code', '200'));
    }

    /**
     * 设置服务信息
     * @return array
     */
    public function actionSet()
    {
        $where['uid'] = RequestHelper::post('uid', '', '');
        if (empty($where['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $name = RequestHelper::post('name', '', '');
        $update_data = [];
        if (!empty($name)) {
            $update_data['name'] = $name;
        }
        $description = RequestHelper::post('description', '', '');
        if (!empty($description)) {
            $update_data['description'] = $description;
        }
        $province_id = RequestHelper::post('province_id', '', '');
        if (!empty($province_id)) {
            $update_data['province_id'] = $province_id;
        }
        $search_address = RequestHelper::post('search_address', '', '');
        if (!empty($search_address)) {
            $update_data['search_address'] = $search_address;
        }
        $details_address = RequestHelper::post('details_address', '', '');
        if (!empty($details_address)) {
            $update_data['details_address'] = $details_address;
        }
        $lng = RequestHelper::post('lng', '0', '');
        if (!empty($lng)) {
            $update_data['lng'] = $lng;
        }
        $lat = RequestHelper::post('lat', '0', '');
        if (!empty($lat)) {
            $update_data['lat'] = $lat;
        }
        if (empty($update_data)) {
            $this->returnJsonMsg('1016', [], Common::C('code', '1016'));
        }
        $service_setting_model = new ServiceSetting();
        $info = $service_setting_model->getInfo($where, true, 'id');
        if (empty($info)) {
            /**执行添加**/
            $update_data['uid']    = $where['uid'];
            $update_data['mobile'] = $where['mobile'];
            if (empty($update_data['name'])) {
                $this->returnJsonMsg('1058', [], Common::C('code', '1058'));
            }
            if (empty($update_data['province_id'])) {
                $this->returnJsonMsg('638', [], Common::C('code', '638'));
            }
            if (empty($update_data['search_address'])) {
                $this->returnJsonMsg('1054', [], Common::C('code', '1054'));
            }
            if (empty($update_data['details_address'])) {
                $this->returnJsonMsg('1055', [], Common::C('code', '1055'));
            }
            if (empty($update_data['lng'])) {
                $this->returnJsonMsg('1056', [], Common::C('code', '1056'));
            }
            if (empty($update_data['lat'])) {
                $this->returnJsonMsg('1057', [], Common::C('code', '1057'));
            }
            $rs = $service_setting_model->insertInfo($update_data);
            //@todo 初始化服务时间
            $service_week_time_model = new ServiceWeekTime();
            $service_week_time_model->setDefaultTime($where['uid'], $where['mobile']);
        } else {
            $update_data['update_time'] = date('Y-m-d H:i:s', time());
            /**执行更新**/
            $rs = $service_setting_model->updateInfo($update_data, $where);
        }
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
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

    /**
     * 获取服务单位
     * @return array
     */
    public function actionGetUnit()
    {
        $data['uid'] = RequestHelper::get('uid', '', '');
        if (empty($data['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $data['mobile'] = RequestHelper::get('mobile', '', '');
        if (empty($data['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($data['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        //get缓存
        $cache_key = 'service_unit';
        $cache_rs = SsdbHelper::Cache('get', $cache_key);
        if ($cache_rs) {
            $unit_list = $cache_rs;
        } else {
            $unit_model = new ServiceUnit();
            $unit_where['status'] = '2';
            $unit_list = $unit_model->getList($unit_where, 'id,unit', 'id asc');
            if (empty($unit_list)) {
                $this->returnJsonMsg('1039', [], Common::C('code', '1039'));
            }
            //set缓存
            SsdbHelper::Cache('set', $cache_key, $unit_list, Common::C('SSDBCacheTime'));
        }
        $this->returnJsonMsg('200', $unit_list, Common::C('code', '200'));
    }
    /**
     * 获取设置信息
     * @param string $mobile 手机号
     * @param string $params 参数名
     * @param int    $type   表示 1=一个参数 返回一个字段值 2=多个参数 返回数组
     * @return string
     */
    private function _getSettingInfo($mobile = '', $params = '', $type = 1)
    {
        if (!empty($mobile) && !empty($params)) {
            $service_setting_model = new ServiceSetting();
            $where['mobile'] = $mobile;
            $fields = $params;
            $info = $service_setting_model->getInfo($where, true, $fields);
            if (!empty($info)) {
                if ($type == 1) {
                    return $info[$params];
                } else {
                    return $info;
                }
            }
        }
        return ($type == 1) ? '' : [] ;
    }
    /**
     * 格式化图片
     * @param string $image 图片地址
     * @return string
     */
    private function _formatImg($image = '')
    {
        if (!empty($image)) {
            if (!strstr($image, 'http')) {
                return Common::C('imgHost').$image;
            }
        }
        return '';
    }

    /**
     * 获取子类的信息
     * @param int $pid  父类ID
     * @param int $type 类型ID 当type!=0的时候返回子类中返回"全部"
     * @return array
     */
    private function _getSonCategory($pid = 0, $type = 0)
    {
        $rs = [];
        if (!empty($type)) {
            $rs['id']    = '0';
            $rs['name']  = '全部';
            $rs['image'] = '';
        }
        if (!empty($pid)) {
            //get缓存
            $cache_key = 'service_son_category_'.$pid;
            $cache_rs = SsdbHelper::Cache('get', $cache_key);
            if ($cache_rs) {
                $info = $cache_rs;
            } else {
                $service_category_model = new ServiceCategory();
                $fields = 'id,name,image';
                $where['pid']        = $pid;
                $where['status']     = '2';
                $where['is_deleted'] = '2';
                $order  = 'sort desc';
                $info = $service_category_model->getList($where, $fields, $order);
                //set缓存
                SsdbHelper::Cache('set', $cache_key, $info, Common::C('SSDBCacheTime'));
            }
            if (!empty($type)) {
                array_unshift($info, $rs);//向数组插入元素
            }
            if (!empty($info)) {
                foreach ($info as $k => $v) {
                    if ($v['image']) {
                        $info[$k]['image'] = $this->_formatImg($v['image']);
                    }
                }
            }
            return $info;
        }
        return $rs;
    }

    /**
     * 获取用户信息
     * @param string $mobile 电话
     * @return array
     */
    private function _getUserInfo($mobile = '')
    {
        $rs['realname'] = '';
        $rs['avatar']   = '';
        $rs['nickname'] = '';
        $rs['sex']      = '0';
        $rs['card_audit_status'] = '0';
        //get缓存
        $cache_key = 'profile_'.$mobile;
        $cache_rs = SsdbHelper::Cache('get', $cache_key);
        if ($cache_rs) {
            $rs = $cache_rs;
        } else {
            $user_base_info_model = new UserBasicInfo();
            $user_base_info_where['mobile'] = $mobile;
            $user_base_info_fields = 'realname,avatar,nickname,sex,card_audit_status';
            $rs = $user_base_info_model->getInfo($user_base_info_where, true, $user_base_info_fields);
        }
        if (!empty($rs)) {
            if ($rs['avatar']) {
                if (!strstr($rs['avatar'], 'http')) {
                    $rs['avatar'] = Common::C('imgHost').$rs['avatar'];
                }
            }
            $rs['sex'] = empty($rs['sex']) ? "0" : $rs['sex'];
            $rs['card_audit_status'] = empty($rs['card_audit_status']) ? "0" : $rs['card_audit_status'];
        }
        return $rs;
    }

    /**
     * 获取服务单位
     * @param int $unit_id 单位ID
     * @return string
     */
    private function _getServiceUnit($unit_id = 0)
    {
        $unit = '';
        if (!empty($unit_id)) {
            //get缓存
            $cache_key = 'service_unit';
            $cache_rs = SsdbHelper::Cache('get', $cache_key);
            if ($cache_rs) {
                $unit_list = $cache_rs;
            } else {
                $unit_model = new ServiceUnit();
                $unit_where['status'] = '2';
                $unit_list = $unit_model->getList($unit_where, 'id,unit', 'id asc');
                //set缓存
                SsdbHelper::Cache('set', $cache_key, $unit_list, Common::C('SSDBCacheTime'));
            }
            if (!empty($unit_list)) {
                foreach ($unit_list as $k => $v) {
                    if ($unit_list[$k]['id'] == $unit_id) {
                        $unit = $unit_list[$k]['unit'];
                        break;
                    }
                }
            }
        }
        return $unit;
    }
}
