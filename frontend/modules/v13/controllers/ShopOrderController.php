<?php
/**
 * 我的订单
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/8/25
 */

namespace frontend\modules\v13\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\PrivilegeOrder;
use frontend\models\i500_social\PrivilegeOrderDetail;
use frontend\models\i500_social\PrivilegeShopCarts;
use frontend\models\i500_social\PrivilegeAddress;
use yii\data\Pagination;

class ShopOrderController extends BasePrivilegeController {
    //预加载
    public function beforeAction($action) {
        //处理订单超时关闭问题
        $this->autoTimeOrder();
        return parent::beforeAction($action);
    }
    
    //立即购买
    public function actionBuy() {
        //规格id
        $s_id = RequestHelper::post('s_id','0','intval');
        if (empty($s_id)) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        //商品数量
        $num = RequestHelper::post('num','0','intval');
        if (empty($num) || $num<1) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        
        //商品
        $data = PrivilegeShopCarts::getBuyList($s_id, $num);
        if (!empty($data['error'])) {
            $this->returnJsonMsg($data['error'],[],Common::C('code',$data['error']));
        }
        
        $this->returnJsonMsg('200', [$data] , Common::C('code', '200'));
    }
    
    //立即购买-下单
    public function actionBuyConfirm() {
        //规格id
        $s_id = RequestHelper::post('s_id','0','intval');
        if (empty($s_id)) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        //商品数量
        $num = RequestHelper::post('num','0','intval');
        if (empty($num)) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        //收货地址id
        $ad_id = RequestHelper::post('ad_id','0','intval');
        if (empty($ad_id)) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }

        //商品
        $order_goods = PrivilegeShopCarts::getBuyList($s_id, $num);
        if (!empty($order_goods['error'])) {
            $this->returnJsonMsg($order_goods['error'],[],Common::C('code',$order_goods['error']));
        }
        
        //生成订单
        $data = $this->_createOrder($order_goods,$ad_id);
        
        $this->returnJsonMsg('200', [$data] , Common::C('code', '200'));
    }
    
    //去结算
    public function actionGoBuy() {
        $ids = RequestHelper::post('ids', '' , 'trim');
        $ids = @json_decode($ids, true);
        //购物车id为空
        if (empty($ids)) {
            $this->returnJsonMsg('511', [] , Common::C('code', '511'));
        }
        //我的购物车商品
        $data = PrivilegeShopCarts::getCartList($this->mobile, $ids);
        //筛选可下单商品
        foreach ($data['list'] as $key=>$val) {
            //商品有错误信息跳过
            if(!empty($val['error'])) {
                unset($data['list'][$key]);
                $data['price_all'] -= $val['total'];
                $data['num_all'] -=  $val['num'];
                continue;
            }
        }
        if (empty($data['list'])) {
            $this->returnJsonMsg('2203', [] , Common::C('code', '2203'));
        }
        $data['list'] = array_values($data['list']);
        
        $this->returnJsonMsg('200', [$data] , Common::C('code', '200'));
    }
    
    //去结算-下单
    public function actionGoBuyConfirm() {
        $ids = RequestHelper::post('ids', '' , 'trim');
        $ids = @json_decode($ids, true);
        //购物车id为空
        if (empty($ids)) {
            $this->returnJsonMsg('511', [] , Common::C('code', '511'));
        }
        //收货地址id
        $ad_id = RequestHelper::post('ad_id','0','intval');
        if (empty($ad_id)) {
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        
        //我的购物车商品
        $order_goods = PrivilegeShopCarts::getCartList($this->mobile, $ids);
        //筛选可下单商品
        foreach ($order_goods['list'] as $key=>$val) {
            //商品有错误信息跳过
            if(!empty($val['error'])) {
                unset($order_goods['list'][$key]);
                $order_goods['price_all'] -= $val['total'];
                $order_goods['num_all'] -=  $val['num'];
                continue;
            }
        }
        if (empty($order_goods['list'])) {
            $this->returnJsonMsg('2203', [] , Common::C('code', '2203'));
        }
        //生成订单
        $data = $this->_createOrder($order_goods,$ad_id);
        //移除购物车商品
        PrivilegeShopCarts::deleteAll(['id'=>$ids]);
        
        $this->returnJsonMsg('200', [$data] , Common::C('code', '200'));
    }
    
    /**
     * 生成订单
     * @param array $order_goods 商品列表
     * @param int $ad_id 收货地址id
     */
    private function _createOrder($order_goods=[],$ad_id) {
        //商品为空
        if (empty($order_goods['list'])) {
            $this->returnJsonMsg('2203', [] , Common::C('code', '2203'));
        }
        //收货地址
        $address = PrivilegeAddress::find()->select('*')->where(['mobile'=>$this->mobile,'id'=>$ad_id])->asArray()->one();
        if (empty($address)) {
            $this->returnJsonMsg('2204', [] , Common::C('code', '2204'));
        }
        
        $data = [
            'order_sn'  => $this->getIdsn('SC'),
            'num_all' => 0,
            'price_all' => 0,
            'account'   => $this->getUserAccount(),
        ];
        
        $this->setTransaction('db_social');
        $order_model = new PrivilegeOrder();
        $order_detail_model = new PrivilegeOrderDetail();
        //筛选可下单商品
        foreach ($order_goods['list'] as $val) {
            //商品有错误信息跳过
            if(!empty($val['error'])) {
                continue;
            }
            //订单商品
            $goods = [
                'order_sn' => $data['order_sn'],
                'g_id' => $val['g_id'],
                's_id' => $val['s_id'],
                'num' => $val['num'],
                'title' => $val['title'],
                'image' => $val['image'],
                'price' => $val['price'],
                'total' => $val['total'],
            ];
            $res1 = $order_detail_model->insertInfo($goods);
            //更新库存
            $res2 = $this->_dnGoodsStock($val['g_id'], $val['s_id'], $val['num']);
            if (empty($res1) || empty($res2)) {
                $this->backTransaction();
                $this->returnJsonMsg('400', [] , Common::C('code', '400'));
            }
            $data['num_all'] += $val['num'];
            $data['price_all'] += $val['total'];
        }
        //无订单商品
        if (empty($data['num_all'])) {
            $this->backTransaction();
            $this->returnJsonMsg('2203', [] , Common::C('code', '2203'));
        }
        //生成订单
        $order = [
            'order_sn' => $data['order_sn'],
            'uid'      => $this->uid,
            'mobile'   => $this->mobile,
            'address_message' => serialize($address),
            'total_number'    => $data['num_all'],
            'price_all'       => $data['price_all'],
            'status'          => 1,
            'aotu_offa_time'  => date('Y-m-d H:i:s', time() + $this->aotu_offa_time),
        ];
        $res3 = $order_model->insertInfo($order);
        if (empty($res3)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [] , Common::C('code', '400'));
        }
        //保存数据
        $this->commitTransaction();
        
        return $data;
    }

    /**
     * 订单列表
     * @return array
     */
    public function actionList()
    {
        $page  = RequestHelper::post('page','1','intval');
        //0全部 1 待支付 2 进行中(待发货,待收货) 4已完成
        $status = RequestHelper::post('status','0','intval');
        if(!in_array($status,[0,1,2,4])){
            return $this->returnJsonMsg('403',[],Common::C('code','403'));
        }

        $where = ['is_del'=>0,'mobile'=>$this->mobile,'status'=>$status];
        if($status == 0){
            $where = ['is_del'=>0,'mobile'=>$this->mobile];
        } else if($status == 2) {
            $where = ['is_del'=>0,'mobile'=>$this->mobile,'status'=>[2,3]];
        }

        //订单列表
        $model = PrivilegeOrder::find()->select(['order_sn','total_number','price_all','status'])
                 ->with(['details'=>function($query){
                    $query->select(['order_sn','g_id','title','image','price','num','total']);
                 }])
                 ->where($where);
        $count = $model->count();
        $list  = $model->offset(($page-1)*$this->pageSize)
                 ->limit($this->pageSize)
                 ->orderBy('create_time DESC')
                 ->asArray()
                 ->all();

        $pages = new Pagination(['totalCount'=>$count,'pageSize'=>$this->pageSize]);
        $data = [];
        $data['list'] = $list;
        $data['count'] = $count;
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        $this->returnJsonMsg('200', [$data] , Common::C('code', '200'));
    }

    /**
     * 删除订单(4已完成 5用户取消 6商家取消 7未支付订单自动关闭)
     * @return array
     */
    public function actionOrderDel(){
        $order_sn = RequestHelper::post('order_sn','','');
        if (empty($order_sn)) {
            $this->returnJsonMsg('805',[],Common::C('code','805'));
        }

        //订单信息
        $order = PrivilegeOrder::find()->select(['status'])->where(['order_sn'=>$order_sn,'mobile'=>$this->mobile,'status'=>[4,5,6,7],'is_del'=>0])->asArray()->scalar();
        if (empty($order)) {
            $this->returnJsonMsg('816',[],Common::C('code','816'));
        }

        //删除
        $res = PrivilegeOrder::updateAll(['is_del'=>1],['order_sn'=>$order_sn]);
        if (!$res) {
            $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        $this->returnJsonMsg('200', [] , Common::C('code', '200'));
    }

    /**
     * 交易关闭
     * @return array
     */
    public function actionOrderClose()
    {
        $order_sn = RequestHelper::post('order_sn','','');
        if (empty($order_sn)) {
            $this->returnJsonMsg('805',[],Common::C('code','805'));
        }
        //reject_remark取消原因
        $reject_remark = RequestHelper::post('reject_remark','','');

        //订单信息(未支付订单)
        $order = PrivilegeOrder::find()->select(['status'])->where(['order_sn'=>$order_sn,'mobile'=>$this->mobile,'status'=>1,'is_del'=>0])->asArray()->scalar();
        if (empty($order)) {
            $this->returnJsonMsg('816',[],Common::C('code','816'));
        }

        $res = PrivilegeOrder::updateAll(['status'=>5,'reject_remark'=>$reject_remark],['order_sn'=>$order_sn]);

        //查询出该订单的商品
        $order_details = PrivilegeOrderDetail::find()->select(['g_id','s_id','num'])->where(['order_sn'=>$order_sn])->asArray()->all();
        foreach ($order_details as $key => $value) {
            //增加库存，减少销量
            $this->_upGoodsStock($value['g_id'],$value['s_id'],$value['num']);
        }
        if (!$res) {
            $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        $this->returnJsonMsg('200', [] , Common::C('code', '200'));
    }

    /**
     * 确认收货
     * @return array
     */
    public function actionOrderConfirm()
    {
        $order_sn = RequestHelper::post('order_sn','','');
        if (empty($order_sn)) {
            $this->returnJsonMsg('805',[],Common::C('code','805'));
        }

        //订单信息(待收货已支付的订单)
        $order = PrivilegeOrder::find()->select(['status'])->where(['order_sn'=>$order_sn,'mobile'=>$this->mobile,'status'=>3,'is_del'=>0])->asArray()->scalar();
        if (empty($order)) {
            $this->returnJsonMsg('816',[],Common::C('code','816'));
        }
        //更新订单状态
        $res = PrivilegeOrder::updateAll(['status'=>4,'receive_time'=>date('Y-m-d H:i:s')],['order_sn'=>$order_sn]);
        if (!$res) {
            $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        //更新物流信息
        $this->getExpress($order_sn);
        
        $this->returnJsonMsg('200', [] , Common::C('code', '200'));
    }

    /**
     * 物流列表
     * @return array
     */
    public function actionExpress()
    {
        $order_sn = RequestHelper::post('order_sn','','trim');
        if (empty($order_sn)) {
            $this->returnJsonMsg('511', [] , Common::C('code', '511'));
        }
        //查询订单信息
        $order = PrivilegeOrder::find()->select(['order_sn','status','express_message'])
                 ->with(['details'=>function($query){
                     $query->select(['order_sn','title','image']);
                 }])
                 ->where(['order_sn'=>$order_sn,'mobile'=>$this->mobile,'is_del'=>0])
                 ->asArray()
                 ->one();
        if (empty($order)) {
            return $this->returnJsonMsg('818',[],Common::C('code','818'));
        }
        //物流信息
        $express = [];
        if (!empty($order['express_message'])) {
            $express = @unserialize($order['express_message']);
        }
        $express_sn   = empty($express['express_sn'])   ? '' : $express['express_sn'];
        $express_name = empty($express['express_name']) ? '' : $express['express_name'];
        //物流信息
        $exp = $this->getExpress($order_sn);
        
        $data = [
            'image' => empty($order['details'][0]) ? '' : $order['details'][0]['image'],//商品图片
            'total_number' => count($order['details']),
            'state' => empty($exp['describe']) ? '' : $exp['describe'],//物流状态
            'com' => $express_name,//快递公司
            'nu' => $express_sn,//快递单号
            'list' => empty($exp['list']) ? [] : $exp['list'],
        ];
        $this->returnJsonMsg('200', [$data] , Common::C('code', '200'));
    }

    /**
     * 我的订单页面-订单数量
     * @return array
     */
    public function actionOrderNum()
    {
        //订单数量
        $order = PrivilegeOrder::find()->select(['id']);
        //待支付的
        $data['no_pay_num'] = $order->where(['status'=>1,'mobile'=>$this->mobile])->count();
        //进行中的
        $data['pay_num'] = $order->where(['status'=>[2,3],'mobile'=>$this->mobile])->count();
        //已完成的
        $data['finish_num'] = $order->where(['status'=>4,'mobile'=>$this->mobile])->count();
        //订单总数量
        $data['ordercount'] = $data['no_pay_num']+$data['pay_num'].'';
        //购物车数量
        $data['cartcount'] = PrivilegeShopCarts::getCartCount($this->mobile);
        
        $this->returnJsonMsg('200', [$data] , Common::C('code', '200'));
    }

    /**
     * 订单详情
     * @return array
     */
    public function actionDetails()
    {
        $message = '';
        $aotu_offa_time = '0000-00-00 00:00:00';
        $express = [];
        $order_sn = RequestHelper::post('order_sn','','');
        //查询订单信息
        $fields = ['status','price_all','total_number','order_sn','address_message','express_message','pay_time','aotu_offa_time','aotu_confirm_time','delivery_time','receive_time','reject_remark','create_time','update_time'];
        $order = PrivilegeOrder::find()->select($fields)
                 ->with(['details'=>function($query){
                     $query->select(['g_id','order_sn','title','image','price','num','total']);
                 }])
                 ->where(['order_sn'=>$order_sn,'mobile'=>$this->mobile,'is_del'=>0])
                 ->asArray()
                 ->one();
        if (empty($order)) {
            return $this->returnJsonMsg('818',[],Common::C('code','818'));
        }
        switch ($order['status'])
        {
            case 1:
                //等待支付
                $message = '剩余'.$this->NformatTime($order['aotu_offa_time']).'订单自动关闭';
                break;
            case 2:
                //待发货
                $message = '等待卖家发货';
                break;
            case 3:
                //待收货
                $message = '剩余'.$this->NformatTime($order['aotu_confirm_time']).'自动确认';
                break;
            case 4:
                $message = '超时关闭';
                break;
            case 5:
                $message = '用户取消';
                $aotu_offa_time = $order['update_time'];
                break;
            case 6:
                $aotu_offa_time = $order['update_time'];
                $message = '商家取消';
                break;
            case 7:
                //订单关闭
                $message = '超时关闭';
                $aotu_offa_time = $order['aotu_offa_time'];
                break;
            default:
                return $this->returnJsonMsg('818',[],Common::C('code','818'));
        }
        //如果是待收货或者已完成才会有
        if(in_array($order['status'],[3,4])){
            //获取物流信息
            $express = $this->getExpress($order_sn);
            $express = empty($express['list']) ? [] : current($express['list']);
        }

        //物流信息
        $address = [];
        if (!empty($order['address_message'])) {
            $address = unserialize($order['address_message']);
        }
        //收货地址
        $priovince_name = empty($address['priovince_name'])?'':$address['priovince_name'];
        $city_name = empty($address['city_name'])?'':$address['city_name'];
        $district_name = empty($address['district_name'])?'':$address['district_name'];
        $ress = empty($address['address'])?'':$address['address'];

        $item = [
            'status'=>$order['status'],
            'message' => $message,
            'express_message'=>empty($express['context'])?'包裹正在等待揽收':$express['context'],//一条快递信息
            'express_time'=>empty($express['time'])?$order['delivery_time']:$express['time'],//时间
            'address_name'=>empty($address['name'])?'':$address['name'],
            'address_tel'=>empty($address['tel'])?'':$address['tel'],
            'address'=>$priovince_name.$city_name.$district_name.$ress,
            'goods'=>empty($order)?[]:$order['details'],
            'total_number'=>$order['total_number'],
            'price_all'=>$order['price_all'],
            'order_sn'=>$order['order_sn'],
            'create_time'=>$order['create_time'],
            'pay_time'=>$order['pay_time'],
            'aotu_offa_time'=>$aotu_offa_time,
            'aotu_confirm_time'=>$order['aotu_confirm_time'],
            'delivery_time'=>$order['delivery_time'],
            'receive_time'=>$order['receive_time']
        ];

        $data = [];
        $data['item'] = $item;
        $this->returnJsonMsg('200', [$data] , Common::C('code', '200'));
    }

    //付款时查金额
    public function actionOrderPrice(){
        //订单号
        $idsn = RequestHelper::post('idsn','','');
        if(empty($idsn)){
            return $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        //查询订单是否存在
        $order = PrivilegeOrder::find()->select(['price_all'])->where(['order_sn'=>$idsn,'mobile'=>$this->mobile])->asArray()->one();
        if (empty($order)) {
            return $this->returnJsonMsg('404',[],Common::C('code','404'));
        }
        
        //返回的数据格式
        $data['price'] = $order['price_all'];
        $data['account'] = $this->getUserAccount();
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
}