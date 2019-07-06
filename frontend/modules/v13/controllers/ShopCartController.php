<?php
/**
 * 购物车
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/8/25
 */

namespace frontend\modules\v13\controllers;

use common\helpers\Common;
use frontend\models\i500_social\PrivilegeShopCarts;
use common\helpers\RequestHelper;

class ShopCartController extends BasePrivilegeController {
    //加入购物车
    public function actionAdd() {
        //规格id
        $s_id = RequestHelper::post('s_id',0,'intval');
        if (empty($s_id)) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        //商品数量
        $num = RequestHelper::post('num',0,'intval');
        if (empty($num) || $num<1) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        
        //商品
        $data = PrivilegeShopCarts::getBuyList($s_id, $num);
        if (!empty($data['error'])) {
            $this->returnJsonMsg($data['error'],[],Common::C('code',$data['error']));
        }
        //商品第一列为空
        if (empty($data['list'][0])) {
            $this->returnJsonMsg('2201',[],Common::C('code','2201'));
        }
        $list0 = $data['list'][0];
        
        //加入购物车
        $cart = new PrivilegeShopCarts();
        $data = [
            'uid'    => $this->uid,
            'mobile' => $this->mobile,
            'g_id'   => $list0['g_id'],
            's_id'   => $list0['s_id'],
            'num'    => $list0['num'],
            'price'  => $list0['price'],
        ];
        //根据商品获取购物车信息
        $old_cart = (new PrivilegeShopCarts())->find()->select(['id','num'])->where(['mobile'=>$this->mobile,'s_id'=>$s_id])->asArray()->one();
        //不存在，则添加
        if (empty($old_cart)) {
            $res = $cart->insertInfo($data);
        //存在，则修改
        } else {
            $data['num'] = $data['num'] + $old_cart['num'];
            $res = $cart->updateInfo($data,['id'=>$old_cart['id']]);
        }
        if (empty($res)) {
            $this->returnJsonMsg('400', [], Common::C('code','400'));
        }
        
        $this->returnJsonMsg('200', [], Common::C('code','200'));
    }
    
    //列表
    public function actionList() {
        //我的购物车商品
        $data = PrivilegeShopCarts::getCartList($this->mobile);
        
        $this->returnJsonMsg('200', [$data], Common::C('code','200'));
    }
    
    //删除商品
    public function actionDel() {
        //购物车id
        $id = RequestHelper::post('ids',0,'trim');
        $id = @json_decode($id,true);
        if (empty($id)) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        $res = PrivilegeShopCarts::deleteAll(['mobile'=>$this->mobile,'id'=>$id]);
        //删除失败
        if (empty($res)) {
            $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
    
    //修改数量
    public function actionUpNum() {
        //购物车id
        $id = RequestHelper::post('id',0,'intval');
        if (empty($id)) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        //商品数量
        $num = RequestHelper::post('num',0,'intval');
        if (empty($num) || $num<1) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        $info = PrivilegeShopCarts::findOne(['mobile'=>$this->mobile,'id'=>$id]);
        if (empty($info)) {
            $this->returnJsonMsg('404',[],Common::C('code','404'));
        }
        
        //商品信息
        $data = PrivilegeShopCarts::getBuyList($info->s_id, $num);
        if (!empty($data['error'])) {
            //库存不足
            if ($data['error']==2202) {
                $num = $data['total_num']<2 ? 1 : $data['total_num']-1;
            } else {
                $this->returnJsonMsg($data['error'],[],Common::C('code',$data['error']));
            }
        }
        PrivilegeShopCarts::updateAll(['num'=>$num],['mobile'=>$this->mobile,'id'=>$id]);
        
        $this->returnJsonMsg('200', ['num'=>$num], Common::C('code', '200'));
    }
}