<?php
/**
 * v13 商城基类
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/8/25
 */

namespace frontend\modules\v13\controllers;

use common\helpers\Common;
use frontend\models\i500_social\User;
use frontend\models\i500_social\PrivilegeOrder;
use frontend\models\i500_social\PrivilegeGoods;
use frontend\models\i500_social\PrivilegeSpecification;
use frontend\models\i500_social\PrivilegeAddress;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500m\Province;
use frontend\models\i500m\City;
use frontend\models\i500m\District;
use frontend\models\i500_social\PrivilegeOrderLogistics;

class BasePrivilegeController extends BaseController {
    
    //未支付自动关闭时间(s)
    public $aotu_offa_time = 1800;//30分钟
    //超时自动确认时间(s)
    public $aotu_confirm_time = 604800;//7天
    
    /**
     * 初始化
     * @return array
     */
    public function init() {
        parent::init();
        //规定的post移除表情
        $this->postTextEmpty();
    }
    
    //规定的post移除表情
    private function postTextEmpty() {
        $u_arr = ['search','reject_remark','name','tel','address'];//搜索，取消订单原因
        foreach ($u_arr as $key) {
            if (isset($_POST[$key])) {
                $_POST[$key] = Common::userTextEmpty($_POST[$key]);
            }
        }
    }
    
    //有特权返回 true
    public function checkPrivilege() {
        return true;//暂不检查特权
        $ispriv = User::find()->select(['is_verification_code'])->where(['mobile'=>$this->mobile])->scalar();
        if (empty($ispriv)) {
            return false;
        }
        return true;
    }
    
    //减少库存，增加销量
    public function _dnGoodsStock($g_id, $s_id, $num) {
        //商品详情
        $goods_obj = PrivilegeGoods::findOne(['id'=>$g_id]);
        if (empty($goods_obj) || $goods_obj->total_num<$num) {
            return false;
        }
        //规格详情
        $goods_spec_obj = PrivilegeSpecification::findOne(['id'=>$s_id]);
        if (empty($goods_spec_obj) || $goods_spec_obj->total_num<$num) {
            return false;
        }
        
        $goods_obj->total_num -= $num;
        $goods_obj->sales_num += $num;
        //库存为0标记下架
        if ($goods_spec_obj->total_num < 1) {
            $goods_obj->status = 3;
        }
        $res1 = $goods_obj->save();
        
        $goods_spec_obj->total_num -= $num;
        $goods_spec_obj->sales_num += $num;
        $res2 = $goods_spec_obj->save();
        
        return !(empty($res1)||empty($res2));
    }
    
    //增加库存，减少销量
    public function _upGoodsStock($g_id, $s_id, $num) {
        //商品详情
        $goods_obj = PrivilegeGoods::findOne(['id'=>$g_id]);
        if (empty($goods_obj) || $goods_obj->sales_num<$num) {
            return false;
        }
        //规格详情
        $goods_spec_obj = PrivilegeSpecification::findOne(['id'=>$s_id]);
        if (empty($goods_spec_obj) || $goods_spec_obj->sales_num<$num) {
            return false;
        }
        
        $goods_obj->total_num += $num;
        $goods_obj->sales_num -= $num;
        //库存不为0下架转上架
        if ($goods_obj->total_num>0 && $goods_obj->status==3) {
            $goods_obj->status = 2;
        }
        $res1 = $goods_obj->save();
        
        $goods_spec_obj->total_num += $num;
        $goods_spec_obj->sales_num -= $num;
        $res2 = $goods_spec_obj->save();
        
        return !(empty($res1)||empty($res2));
    }
    
    //超时订单
    public function autoTimeOrder() {
        //1待支付，3待收货
        $order_list = PrivilegeOrder::find()->select(['id','order_sn','status'])
                    ->with(['details'=>function($query){
                        $query->select(['order_sn','g_id','s_id','num']);
                    }])
                    ->where([
                        'or',[
                            'and',
                            ['status'=>1],
                            ['<','aotu_offa_time',date('Y-m-d H:i:s')]
                        ],[
                            'and',
                            ['status'=>3],
                            ['<','aotu_confirm_time',date('Y-m-d H:i:s')]
                        ],
                    ])
                    ->andWhere(['mobile'=>$this->mobile])
                    ->orderBy('create_time DESC')
                    ->asArray()
                    ->all();
        //遍历订单，改变状态
        foreach ($order_list as $val) {
            $this->setTransaction('db_social');
            //修改状态
            switch ($val['status']) {
                case 1:
                    $res = PrivilegeOrder::updateAll(['status'=>7],['id'=>$val['id']]);
                    break;
                case 3:
                    $res = PrivilegeOrder::updateAll(['status'=>4,'receive_time'=>date('Y-m-d H:i:s')],['id'=>$val['id']]);
                    break;
            }
            //遍历商品，还原库存销量
            foreach ($val['details'] as $val2) {
                $res1 = $this->_upGoodsStock($val2['g_id'],$val2['s_id'],$val2['num']);
                !empty($res1) || $err[] = $res1;
            }
            //处理失败
            if (empty($res) || !empty($err)) {
                $this->backTransaction();
                continue;
            }
            $this->commitTransaction();
        }
    }
    
    //无收货地址，自动创建一条（开启特权时调用）
    public function autoCreateAddress() {
        $address_model = new PrivilegeAddress();
        $info = $address_model->find()->select(['id'])->where(['mobile'=>$this->mobile])->scalar();
        //有收货地址
        if (!empty($info)) {
            return false;
        }
        //地址
        $basic = UserBasicInfo::find()->select(['realname','address'])->where(['mobile'=>$this->mobile])->asArray()->one();
        $priovince_name = (new Province())->find()->select(['name'])->where(['id'=>$this->province_id])->scalar();
        $city_name = (new City())->find()->select(['name'])->where(['id'=>$this->city_id])->scalar();
        $district_name = (new District())->find()->select(['name'])->where(['id'=>$this->district_id])->scalar();
        //数据
        $insert = [
            'uid'            => $this->uid,
            'mobile'         => $this->mobile,
            'name'           => empty($basic['realname'])?'':$basic['realname'],
            'tel'            => $this->mobile,
            'priovince_id'   => $this->province_id,
            'priovince_name' => $priovince_name,
            'city_id'        => $this->city_id,
            'city_name'      => $city_name,
            'district_id'    => $this->district_id,
            'district_name'  => $district_name,
            'address'        => empty($basic['address'])?'':$basic['address'],
            'is_default'     => 2,
        ];
        $res = $address_model->insertInfo($insert);
        return $res;
    }
    
    /**
     * 获取物流信息
     * @author wyy
     * @param string $order_sn 订单编号
     * @return array
     */
    public function getExpress($order_sn) {
        $data = [];
        //查询订单信息
        $order = PrivilegeOrder::findOne(['order_sn'=>$order_sn]);
        if (empty($order)) {
            return $data;
        }
        //物流信息
        $express = @unserialize($order->express_message);
        if (empty($express)) {
            return $data;
        }
        $express_sn  = empty($express['express_sn'])  ? '' : $express['express_sn'];
        $express_com = empty($express['express_com']) ? '' : $express['express_com'];
        //待收货
        if ($order->status==3) {
            $data = Common::getExpressKdB($express_com, $express_sn);
            $order->express_data = serialize($data);
            $res = $order->save();
            //记录物流次数
            (new PrivilegeOrderLogistics())->insertInfo(['order_sn'=>$order_sn,'express_sn'=>$express_sn,'type'=>1]);
        //已完成
        } elseif($order->status==4) {
            $data = @unserialize($order->express_data);
            $data = empty($data) ? [] : $data;
        }
        return $data;
    }
}
