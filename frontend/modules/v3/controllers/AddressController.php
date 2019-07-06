<?php
/**
 * 用户收货地址
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Address
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/19
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v3\controllers;

use frontend\models\i500m\Province;
use Yii;
use common\helpers\Common;
use common\helpers\SsdbHelper;
use common\helpers\CurlHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\UserAddress;
use frontend\models\i500m\OpenCity;

/**
 * Address
 *
 * @category Social
 * @package  Address
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class AddressController extends BaseController
{
    /**
     * Before
     * @param \yii\base\Action $action Action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    /**
     * 获取开通省份
     * @return array
     */
    public function actionGetOpenProvince()
    {
        $uid = RequestHelper::get('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $mobile = RequestHelper::get('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        //get缓存
        $cache_key = 'open_province';
        $cache_rs = SsdbHelper::Cache('get', $cache_key);
        if ($cache_rs) {
            $info = $cache_rs;
        } else {
            $open_city_model = new OpenCity();
            $open_city_where['status']  = '1';
            $open_city_where['display'] = '1';
            $open_city_fields = 'province as province_id';
            $info = $open_city_model->getList($open_city_where, $open_city_fields);
            //set缓存
            SsdbHelper::Cache('set', $cache_key, $info, Common::C('SSDBCacheTime'));
        }
        $info = Common::arrUnique($info, 'province_id');
        if (!empty($info)) {
            foreach ($info as $k => $v) {
                $info[$k]['province_name'] = $this->_getProvinceName($v['province_id']);
            }
        }
        $this->returnJsonMsg('200', array_values($info), Common::C('code', '200'));
    }
    /**
     * 添加
     * @return array
     */
    public function actionAdd()
    {
        $uid = RequestHelper::post('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $data['uid']    = $uid;
        $data['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($data['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($data['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $data['consignee'] = RequestHelper::post('consignee', '', '');
        if (empty($data['consignee'])) {
            $this->returnJsonMsg('628', [], Common::C('code', '628'));
        }
        $data['consignee_mobile'] = RequestHelper::post('consignee_mobile', '', '');
        if (empty($data['consignee_mobile'])) {
            $this->returnJsonMsg('629', [], Common::C('code', '629'));
        }
        $data['sex'] = RequestHelper::post('sex', '0', '');
        if (empty($data['sex'])) {
            $this->returnJsonMsg('630', [], Common::C('code', '630'));
        }
        $data['province_id'] = RequestHelper::post('province_id', '', '');
        if (empty($data['province_id'])) {
            $this->returnJsonMsg('631', [], Common::C('code', '631'));
        }
        $data['search_address'] = RequestHelper::post('search_address', '', '');
        if (empty($data['search_address'])) {
            $this->returnJsonMsg('635', [], Common::C('code', '635'));
        }
        $data['details_address'] = RequestHelper::post('details_address', '', '');
        if (empty($data['details_address'])) {
            $this->returnJsonMsg('636', [], Common::C('code', '636'));
        }
        $data['is_default'] = RequestHelper::post('is_default', '0', '');
        $data['tag'] = RequestHelper::post('tag', '0', '');
        $data['lng'] = RequestHelper::post('lng', '0', '');
        $data['lat'] = RequestHelper::post('lat', '0', '');
        $where['is_deleted'] = '2';
        $where['mobile']     = $data['mobile'];
        $user_address_model = new UserAddress();
        //get缓存
        $cache_key = 'address_list_'.$where['mobile'];
        $cache_rs = SsdbHelper::Cache('get', $cache_key);
        if ($cache_rs) {
            $count = count($cache_rs);
        } else {
            $count = $user_address_model->getCount($where);
        }
        //最多只能添加10个
        if ($count >= 10) {
            $this->returnJsonMsg('646', [], Common::C('code', '646'));
        }
        $rs = $user_address_model->insertInfo($data);
        if (empty($rs)) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        //del列表缓存
        $cache_key = 'address_list_'.$data['mobile'];
        SsdbHelper::Cache('del', $cache_key);
        //记录用户活跃时间
        $this->saveUserActiveTime(['mobile'=>$data['mobile']]);
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 编辑
     * @return array
     */
    public function actionEdit()
    {
        $uid = RequestHelper::post('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $data['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($data['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($data['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['id'] = RequestHelper::post('address_id', '', '');
        if (empty($where['id'])) {
            $this->returnJsonMsg('634', [], Common::C('code', '634'));
        }
        $data['consignee'] = RequestHelper::post('consignee', '', '');
        if (empty($data['consignee'])) {
            $this->returnJsonMsg('628', [], Common::C('code', '628'));
        }
        $data['consignee_mobile'] = RequestHelper::post('consignee_mobile', '', '');
        if (empty($data['consignee_mobile'])) {
            $this->returnJsonMsg('629', [], Common::C('code', '629'));
        }
        $data['sex'] = RequestHelper::post('sex', '0', '');
        if (empty($data['sex'])) {
            $this->returnJsonMsg('630', [], Common::C('code', '630'));
        }
        $data['province_id'] = RequestHelper::post('province_id', '', '');
        if (empty($data['province_id'])) {
            $this->returnJsonMsg('631', [], Common::C('code', '631'));
        }
        $data['search_address'] = RequestHelper::post('search_address', '', '');
        if (empty($data['search_address'])) {
            $this->returnJsonMsg('635', [], Common::C('code', '635'));
        }
        $data['details_address'] = RequestHelper::post('details_address', '', '');
        if (empty($data['details_address'])) {
            $this->returnJsonMsg('636', [], Common::C('code', '636'));
        }
        $data['is_default']  = RequestHelper::post('is_default', '0', '');
        $data['tag']         = RequestHelper::post('tag', '0', '');
        $data['lng']         = RequestHelper::post('lng', '0', '');
        $data['lat']         = RequestHelper::post('lat', '0', '');
        $data['update_time'] = date('Y-m-d H:i:s', time());
        $user_address_model = new UserAddress();
        $rs = $user_address_model->updateInfo($data, $where);
        if (empty($rs)) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        //del列表缓存
        $cache_key = 'address_list_'.$data['mobile'];
        SsdbHelper::Cache('del', $cache_key);
        //del详情缓存
        $cache_detail_key = 'address_details_'.$where['id'];
        SsdbHelper::Cache('del', $cache_detail_key);
        //记录用户活跃时间
        $this->saveUserActiveTime(['mobile'=>$data['mobile']]);
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 删除
     * @return array
     */
    public function actionDel()
    {
        $uid = RequestHelper::post('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['id'] = RequestHelper::post('address_id', '', '');
        if (empty($where['id'])) {
            $this->returnJsonMsg('634', [], Common::C('code', '634'));
        }
        $data['is_deleted'] = '1';
        $user_address_model = new UserAddress();
        $rs = $user_address_model->updateInfo($data, $where);
        if (empty($rs)) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        //del列表缓存
        $cache_list_key = 'address_list_'.$where['mobile'];
        SsdbHelper::Cache('del', $cache_list_key);
        //del详情缓存
        $cache_detail_key = 'address_details_'.$where['id'];
        SsdbHelper::Cache('del', $cache_detail_key);
        //记录用户活跃时间
        $this->saveUserActiveTime(['mobile'=>$where['mobile']]);
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 列表
     * @return array
     */
    public function actionList()
    {
        $uid = RequestHelper::get('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::get('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['is_deleted'] = '2';
        //get缓存
        $cache_key = 'address_list_'.$where['mobile'];
        $cache_rs = SsdbHelper::Cache('get', $cache_key);
        if ($cache_rs) {
            $this->returnJsonMsg('200', $cache_rs, Common::C('code', '200'));
        }
        $user_address_model  = new UserAddress();
        $user_address_fields = 'id,consignee,sex,consignee_mobile,province_id,search_address,details_address,is_default,tag';
        $list = $user_address_model->getList($where, $user_address_fields, 'id desc');
        //set缓存
        SsdbHelper::Cache('set', $cache_key, $list, Common::C('SSDBCacheTime'));
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }

    /**
     * 获取详情
     * @return array
     */
    public function actionDetails()
    {
        $uid = RequestHelper::get('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::get('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['id'] = RequestHelper::get('address_id', '', '');
        if (empty($where['id'])) {
            $this->returnJsonMsg('634', [], Common::C('code', '634'));
        }
        $where['is_deleted'] = '2';
        //get缓存
        $cache_key = 'address_details_'.$where['id'];
        $cache_rs = SsdbHelper::Cache('get', $cache_key);
        if ($cache_rs) {
            $this->returnJsonMsg('200', $cache_rs, Common::C('code', '200'));
        }
        $user_address_model  = new UserAddress();
        $user_address_fields = 'id,consignee,sex,consignee_mobile,province_id,search_address,details_address,is_default,tag';
        $info = $user_address_model->getInfo($where, true, $user_address_fields);
        //set 缓存
        SsdbHelper::Cache('set', $cache_key, $info, Common::C('SSDBCacheTime'));
        $this->returnJsonMsg('200', $info, Common::C('code', '200'));
    }

    /**
     * 检索地址
     * @return array
     */
    public function actionSearch()
    {
        $uid = RequestHelper::get('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $mobile = RequestHelper::get('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $keywords = RequestHelper::get('keywords', '', '');
        if (empty($keywords)) {
            $this->returnJsonMsg('637', [], Common::C('code', '637'));
        }
        $province_name = RequestHelper::get('province_name', '', '');
        if (empty($province_name)) {
            $this->returnJsonMsg('640', [], Common::C('code', '640'));
        }
        $url = Common::C('channelHost').'lbs/get-suggest?keywords='.$keywords.'&province='.$province_name;
        $res = CurlHelper::get($url, true);
        if (empty($res['code'])) {
            $this->returnJsonMsg('401', [], Common::C('code', '401'));
        }
        if ($res['code'] != '200' || empty($res['data'])) {
            $this->returnJsonMsg('200', [], Common::C('code', '200'));
        }
        $this->returnJsonMsg('200', $res['data'], Common::C('code', '200'));
    }

    /**
     * 省份ID
     * @param int $province_id 省份ID
     * @return array
     */
    private function _getProvinceName($province_id = 0)
    {
        if (empty($province_id)) {
            $this->returnJsonMsg('638', [], Common::C('code', '638'));
        }
        //get缓存
        $cache_key = 'province_name_'.$province_id;
        $cache_rs = SsdbHelper::Cache('get', $cache_key);
        if ($cache_rs) {
            $info = $cache_rs;
        } else {
            $province_model = new Province();
            $province_where['id'] = $province_id;
            $province_fields = 'name';
            $info = $province_model->getInfo($province_where, true, $province_fields);
            //set 缓存
            SsdbHelper::Cache('set', $cache_key, $info, Common::C('SSDBCacheTime'));
        }
        return $info['name'];
    }
}
