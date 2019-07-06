<?php
/**
 * 个人中心
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/8/25
 */

namespace frontend\modules\v13\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\PrivilegeAddress;
use frontend\models\i500m\Province;
use frontend\models\i500m\City;
use frontend\models\i500m\District;

class UserAddressController extends BaseUserController {
    //收货地址列表
    public function actionList() {
        $data['list'] = PrivilegeAddress::find()->select([
                            'id','name','tel','priovince_id','priovince_name','city_id',
                            'city_name','district_id','district_name','address','is_default'
                        ])
                        ->where(['mobile'=>$this->mobile])->orderBy('is_default desc,id asc')->asArray()->all();
        $this->returnJsonMsg('200', [$data] , Common::C('code', '200'));
    }
    
    //添加-收货地址
    public function actionCreate() {
        //收货人姓名
        $name = RequestHelper::post('name', '', 'trim');
        if (empty($name)) {
            $this->returnJsonMsg('511', [] , '请填写收货人姓名');
        }
        //手机号
        $tel = RequestHelper::post('tel', '', 'trim');
        if (empty($tel)) {
            $this->returnJsonMsg('511', [] , '请填写联系电话');
        }
        if (!Common::validateMobile($tel)) {
            $this->returnJsonMsg('511', [] , '请填写正确的联系电话');
        }
        //省id
        $priovince_id = RequestHelper::post('priovince_id', 0, 'intval');
        $priovince_name = (new Province())->find()->select(['name'])->where(['id'=>$priovince_id])->scalar();
        if (empty($priovince_id) || empty($priovince_name)) {
            $this->returnJsonMsg('511', [] , '请选择省份');
        }
        //城市id
        $city_id = RequestHelper::post('city_id', 0, 'intval');
        $city_name = (new City())->find()->select(['name'])->where(['id'=>$city_id])->scalar();
        if (empty($city_id) || empty($city_name)) {
            $this->returnJsonMsg('511', [] , '请选择城市');
        }
        //行政区id
        $district_id = RequestHelper::post('district_id', 0, 'intval');
        $district_name = (new District())->find()->select(['name'])->where(['id'=>$district_id])->scalar();
        if (empty($district_id)) {
            $this->returnJsonMsg('511', [] , '请选择区域');
        }
        //详细地址
        $address = RequestHelper::post('address', '', 'trim');
        if (empty($address)) {
            $this->returnJsonMsg('511', [] , '请填写详细地址');
        }
        if (strlen($address)<5) {
            $this->returnJsonMsg('511', [] , '详细地址不能小于5个字符');
        }
        //默认地址
        $is_default = RequestHelper::post('is_default', '', 'intval');
        $is_default = $is_default==2 ? $is_default : 1;
        
        $this->setTransaction('db_social');
        $default = PrivilegeAddress::findOne(['mobile'=>$this->mobile, 'is_default'=>2]);
        //该用户无默认地址
        if (empty($default)) {
            $is_default = 2;
        //替换默认地址
        }else if($is_default == 2) {
            $default->is_default = 1;
            $default->save();
        }
        //数据
        $insert = [
            'uid'            => $this->uid,
            'mobile'         => $this->mobile,
            'name'           => $name,
            'tel'            => $tel,
            'priovince_id'   => $priovince_id,
            'priovince_name' => $priovince_name,
            'city_id'        => $city_id,
            'city_name'      => $city_name,
            'district_id'    => $district_id,
            'district_name'  => $district_name,
            'address'        => $address,
            'is_default'     => $is_default,
        ];
        $address_model = new PrivilegeAddress();
        $res = $address_model->insertInfo($insert);
        //保存失败
        if (empty($res)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [] , Common::C('code', '400'));
        }
        $this->commitTransaction();
        
        $this->returnJsonMsg('200', [] , Common::C('code', '200'));
    }
    
    //编辑-收货地址
    public function actionUpdate() {
        //收货地址id
        $ad_id = RequestHelper::post('ad_id','0','intval');
        if (empty($ad_id)) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        //收货地址
        $name = RequestHelper::post('name', '', 'trim');
        if (empty($name)) {
            $this->returnJsonMsg('511', [] , Common::C('code', '511'));
        }
        //手机号
        $tel = RequestHelper::post('tel', '', 'trim');
        if (empty($tel)) {
            $this->returnJsonMsg('511', [] , Common::C('code', '511'));
        }
        //省id
        $priovince_id = RequestHelper::post('priovince_id', '', 'intval');
        $priovince_name = (new Province())->find()->select(['name'])->where(['id'=>$priovince_id])->scalar();
        if (empty($priovince_id) || empty($priovince_name)) {
            $this->returnJsonMsg('511', [] , Common::C('code', '511'));
        }
        //城市id
        $city_id = RequestHelper::post('city_id', '', 'intval');
        $city_name = (new City())->find()->select(['name'])->where(['id'=>$city_id])->scalar();
        if (empty($city_id) || empty($city_name)) {
            $this->returnJsonMsg('511', [] , Common::C('code', '511'));
        }
        //行政区id
        $district_id = RequestHelper::post('district_id', '', 'intval');
        $district_name = (new District())->find()->select(['name'])->where(['id'=>$district_id])->scalar();
        if (empty($district_id)) {
            $this->returnJsonMsg('511', [] , Common::C('code', '511'));
        }
        //详细地址
        $address = RequestHelper::post('address', '', 'trim');
        if (empty($address)) {
            $this->returnJsonMsg('511', [] , Common::C('code', '511'));
        }
        $info = PrivilegeAddress::findOne(['mobile'=>$this->mobile, 'id'=>$ad_id]);
        //收货地址不存在
        if (empty($info)) {
            $this->returnJsonMsg('2204', [] , Common::C('code', '2204'));
        }
        //默认地址
        $is_default = RequestHelper::post('is_default', '', 'intval');
        $is_default = $is_default==2 ? $is_default : 1;
        
        $this->setTransaction('db_social');
        $default = PrivilegeAddress::findOne(['mobile'=>$this->mobile, 'is_default'=>2]);
        //该用户无默认地址，或默认地址为该地址
        if (empty($default) || $default->id==$ad_id) {
            $is_default = 2;
        //替换默认地址
        }else if($is_default == 2) {
            $default->is_default = 1;
            $default->save();
        }
        //数据
        $update = [
            'name'           => $name,
            'tel'            => $tel,
            'priovince_id'   => $priovince_id,
            'priovince_name' => $priovince_name,
            'city_id'        => $city_id,
            'city_name'      => $city_name,
            'district_id'    => $district_id,
            'district_name'  => $district_name,
            'address'        => $address,
            'is_default'     => $is_default,
        ];
        $address_model = new PrivilegeAddress();
        $res = $address_model->updateInfo($update,['id'=>$ad_id,'mobile'=>$this->mobile]);
        //保存失败
        if (empty($res)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [] , Common::C('code', '400'));
        }
        $this->commitTransaction();
        
        $this->returnJsonMsg('200', [] , Common::C('code', '200'));
    }
    
    //设置默认-收货地址
    public function actionDefault() {
        //收货地址id
        $ad_id = RequestHelper::post('ad_id','0','intval');
        if (empty($ad_id)) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        $info = PrivilegeAddress::findOne(['mobile'=>$this->mobile, 'id'=>$ad_id]);
        //收货地址不存在
        if (empty($info)) {
            $this->returnJsonMsg('2204', [] , Common::C('code', '2204'));
        }
        $is_default = 2;
        
        $this->setTransaction('db_social');
        $default = PrivilegeAddress::findOne(['mobile'=>$this->mobile, 'is_default'=>2]);
        //该用户无默认地址，或默认地址为该地址
        if (empty($default) || $default->id==$ad_id) {
            $is_default = 2;
        //替换默认地址
        }else if($is_default == 2) {
            $default->is_default = 1;
            $default->save();
        }
        //数据
        $update = [
            'is_default' => $is_default,
        ];
        $address_model = new PrivilegeAddress();
        $res = $address_model->updateInfo($update,['id'=>$ad_id,'mobile'=>$this->mobile]);
        //保存失败
        if (empty($res)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [] , Common::C('code', '400'));
        }
        $this->commitTransaction();
        
        $this->returnJsonMsg('200', [] , Common::C('code', '200'));
    }
    
    //删除-收货地址
    public function actionDel() {
        //收货地址id
        $ad_id = RequestHelper::post('ad_id','0','intval');
        if (empty($ad_id)) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        
        $this->setTransaction('db_social');
        $address_model = new PrivilegeAddress();
        $info = PrivilegeAddress::findOne(['mobile'=>$this->mobile, 'id'=>$ad_id]);
        //收货地址不存在
        if (empty($info)) {
            $this->backTransaction();
            $this->returnJsonMsg('2204', [] , Common::C('code', '2204'));
        //替换默认地址
        }else if($info->is_default == 2) {
            $info_one = $address_model->find()->select(['id'])->where(['and',['mobile'=>$this->mobile],['!=','id',$ad_id]])->orderBy('create_time asc')->one();
            //设置其他为默认地址
            if (!empty($info_one->id)) {
                $info_one->is_default = 2;
                $info_one->save();
            }
        }
        //数据
        $res = $address_model->deleteAll(['id'=>$ad_id,'mobile'=>$this->mobile]);
        //删除失败
        if (empty($res)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [] , Common::C('code', '400'));
        }
        $this->commitTransaction();
        
        $this->returnJsonMsg('200', [] , Common::C('code', '200'));
    }
    
    //收货地址详情
    public function actionDetails() {
        //收货地址id
        $ad_id = RequestHelper::post('ad_id','0','intval');
        if (empty($ad_id)) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        $data['item'] = PrivilegeAddress::find()->select([
                            'id','name','tel','priovince_id','priovince_name','city_id',
                            'city_name','district_id','district_name','address','is_default'
                        ])
                        ->where(['mobile'=>$this->mobile,'id'=>$ad_id])->orderBy('is_default desc,id asc')->asArray()->one();
        $this->returnJsonMsg('200', [$data] , Common::C('code', '200'));
    }
    
    //下单收货地址（返回默认地址）
    public function actionOrderDetails() {
        //收货地址id
        $ad_id = RequestHelper::post('ad_id',0,'intval');
        //默认取收货地址
        $address = PrivilegeAddress::find()->select(['id','name','tel','priovince_name','city_name','district_name','address'])
                   ->where(['mobile'=>$this->mobile])->orderBy('is_default desc,id asc')->asArray()->one();
        //id不为空
        if (!empty($ad_id)) {
            //获取详情
            $address1 = PrivilegeAddress::find()->select(['id','name','tel','priovince_name','city_name','district_name','address'])
                       ->where(['mobile'=>$this->mobile,'id'=>$ad_id])->asArray()->one();
            //详情不存在则取默认
            $address = empty($address1) ? $address : $address1;
        }
        //收货地址为空
        if (empty($address)) {
            $this->returnJsonMsg('2204', [] , Common::C('code', '2204'));
        }
        $data['item'] = [
            'id'      => $address['id'],
            'name'    => $address['name'],
            'tel'     => $address['tel'],
            'address' => "{$address['priovince_name']}{$address['city_name']}{$address['district_name']} {$address['address']}",
        ];
        $this->returnJsonMsg('200', [$data] , Common::C('code', '200'));
    }
}