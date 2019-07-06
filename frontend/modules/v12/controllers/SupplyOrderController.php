<?php
/**
 * SupplyOrderController.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/6/22 14:26
 */

namespace frontend\modules\v12\controllers;


use common\helpers\Common;
use yii\data\Pagination;
use common\helpers\RequestHelper;
use frontend\models\i500_social\ShouldSupply;
use frontend\models\i500_social\ShouldSupplyComments;
use frontend\models\i500_social\ShouldSupplyOrder;
use frontend\models\i500_social\UserBasicInfo;

class SupplyOrderController extends BaseShouldController
{
    /**
     * 立即预约
     *
     * @return array
     */
    public function actionSupplyItem(){
        //服务ID
        $did = RequestHelper::post('did','','');
        if (empty($did)) {
            return $this->returnJsonMsg('1010',[],Common::C('code','1010'));
        }
        //用户未认证认证信息
        if (!$this->checkUserCert()) {
            $this->returnJsonMsg('2106', [], Common::C('code', '2106'));
        }

        //详情
        $supply = new ShouldSupply();
        $model = $supply->SupplyList([],['status'=>1, 'community_id'=>$this->community_id ,'id'=>$did],'','',['id','mobile','title','content','price','unit'],2);
        if (empty($model)) {
            return $this->returnJsonMsg('2028',[],Common::C('code','2028'));
        }
        unset($model['photo']);

        //个人信息
        $users = UserBasicInfo::find()->select(['nickname','mobile','avatar'])->where(['mobile'=>$model['mobile']])->asArray()->one();

        $data = [];
        $data['item'] = $model;
        $data['item']['user'] = $users;
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     * 确认预约
     *
     * @return array
     */
    public function actionSupplyOrder(){
        //服务ID
        $did = RequestHelper::post('did','','');
        if (empty($did)) {
            return $this->returnJsonMsg('1010',[],Common::C('code','1010'));
        }

        //服务数量
        $num = RequestHelper::post('num','','intval');
        if (empty($num)) {
            return $this->returnJsonMsg('1066',[],Common::C('code','1066'));
        }
        if ($num < 0) {
            return $this->returnJsonMsg('2031',[],Common::C('code','2031'));
        }
        //用户未认证认证信息
        if (!$this->checkUserCert()) {
            $this->returnJsonMsg('2106', [], Common::C('code', '2106'));
        }
        //生成订单号
        $idsn = $this->getIdsn('YD');
        if(empty($idsn)){
            return $this->returnJsonMsg('1053',[],Common::C('code','1053'));
        }

        //查询服务发布者手机号
        $supply = new ShouldSupply();
        $supply_data = $supply->SupplyList([],['status'=>1,'community_id'=>$this->community_id, 'id'=>$did],'','',['id','mobile','community_id','title','content','price','unit'],2);
        if (empty($supply_data)) {
            return $this->returnJsonMsg('2028',[],Common::C('code','2028'));
        }

        //自己不能预约自己的服务
        if($this->mobile == $supply_data['mobile']){
            return $this->returnJsonMsg('1045',[],Common::C('code','1045'));
        }
//        $count = ShouldSupplyOrder::find()->select(['idsn'])->where(['mobile'=>$this->mobile,'did'=>$did])->asArray()->count();
//        //同一个服务不能预约多次
//        if($count>0){
//            return $this->returnJsonMsg('1064',[],Common::C('code','1064'));
//        }

        //总价
        $price_all = $num*$supply_data['price'];
        $data = [
            'idsn'=>$idsn,
            'mobile'=>$this->mobile,
            'did'=>$did,
            'dmobile'=>$supply_data['mobile'],
            'community_id'=>$supply_data['community_id'],
            'title'=>$supply_data['title'],
            'content'=>$supply_data['content'],
            'price'=>$supply_data['price'],
            'unit'=> $supply_data['unit'],
            'image'=>empty($supply_data['photo'][0]['image'])?'':$supply_data['photo'][0]['image'],
            'num'=>$num,
            'price_all'=>$price_all,
            'status' => 1,
            'aotu_offa_time' =>date('Y-m-d H:i:s',time()+1800)
        ];
        //保存数据
        $supply_order = new ShouldSupplyOrder();
        $res = $supply_order->insertInfo($data);
        if (empty($res)) {
            return $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        //消息推送
        $this->pushToAppSupply($res);

        //查询登陆用户的信息
        $users = UserBasicInfo::find()->select(['nickname'])->where(['mobile'=>$this->mobile])->asArray()->one();
        //生成订单记录
        $this->getGenerateorder(2,$did,$users['nickname'].',确认预约订单：'.$idsn);

        //返回订单号
        $r_data = [];
        $r_data['idsn'] = $idsn;
        return $this->returnJsonMsg('200',[$r_data],Common::C('code','200'));
    }

    /**
     * 付款页面接口
     *
     * @return array
     */
    public function actionOrderPrice(){
        //生成订单号
        $idsn = RequestHelper::post('idsn','','');
        if(empty($idsn)){
            return $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        //查询订单是否存在
        $sipply_order = ShouldSupplyOrder::find()->select(['did','mobile','price_all'])->where(['idsn'=>$idsn,'community_id'=>$this->community_id,'mobile'=>$this->mobile])->asArray()->one();
        if (empty($sipply_order)) {
            return $this->returnJsonMsg('1034',[],Common::C('code','1034'));
        }

        //返回的数据格式
        $data['price'] = $sipply_order['price_all'];
        $data['account'] = $this->getUserAccount();
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     * 我的服务订单
     *
     * @return array
     */
    public function actionMyOrder(){
        //状态
        $status = RequestHelper::post('status','','');
        $where = [];
        $orWhere = [];
        $andWhere = [];
        switch ($status)
        {
            case 1:
                $andWhere['status'] = 1;
                break;
            case 3:
                $andWhere['status'] = 3;
                break;
            case 6:
                $andWhere['status'] = 6;
                break;
            case 8:
                $andWhere['status'] = 8;
                break;
        }
        $andWhere['community_id'] = $this->community_id;
        //自己接的单和别人接的我发的服务的单
        if (!empty($this->mobile)) {
            $where['mobile'] = $this->mobile;
            $orWhere['dmobile'] = $this->mobile;
        }

        //页数
        $page = RequestHelper::post('page', '1', 'intval');
        //个数
        $size = RequestHelper::post('page_size', '10', 'intval');
        //订单列表(我接的服务单以及我发的服务对方接的单)
        $supply_order = new ShouldSupplyOrder();
        $order_data = $supply_order->find()->select(['id','mobile','title','dmobile','content','price','unit','num','price_all','image','status','idsn'])
            ->where($where)
            ->orWhere($orWhere)
            ->andWhere($andWhere)
            ->orderBy('create_time DESC')
            ->offset(($page-1)*$page)
            ->limit($size)
            ->asArray()
            ->all();
        //计算服务订单数量
        $count = $supply_order->SupplyorderCount($where, $orWhere, $andWhere);
        //查询页数
        $pages = new Pagination(['totalCount' => $count]);
        $pages->setPageSize($size, true);
        if (!empty($order_data)) {
            foreach ($order_data as $key => $value) {
                //是否已经评价
                $order_data[$key]['evaluate'] = '0';
                if ($value['status'] == 8 && $value['mobile'] == $this->mobile) {
                    $count = ShouldSupplyComments::find()->select(['id'])->where(['oid'=>$value['id'],'mobile'=>$this->mobile])->count();
                    if($count>0){
                        $order_data[$key]['evaluate'] = '1';
                    }
                }
                //用户信息
                if ($this->mobile == $value['dmobile']) {
                    $order_data[$key]['user'] = $this->_getUserInfo($value['mobile']);
                } else {
                    $order_data[$key]['user'] = $this->_getUserInfo($value['dmobile']);
                }
            }
        }

        $data = [];
        $data['list'] = $order_data;
        $data['count'] = $count;
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     * 订单详情
     *
     * @return array
     */
    public function actionOrderDetail(){
        //订单号
        $idsn = RequestHelper::post('idsn','','');
        if (empty($idsn)) {
            return $this->returnJsonMsg('1068',[],Common::C('code','1068'));
        }

        $order_data = ShouldSupplyOrder::find()->select(['id','mobile','did','title','dmobile','content','price','unit','num','price_all','image','status','idsn','create_time','reject_remark','aotu_offa_time','aotu_offb_time','aotu_confirm_time'])
            ->where(['or',['mobile'=>$this->mobile],['dmobile'=>$this->mobile]])
            ->andWhere(['or',['id'=>$idsn],['idsn'=>$idsn]])
            ->asArray()
            ->one();
        //订单是否存在
        if (empty($order_data)) {
            return $this->returnJsonMsg('1034',[],Common::C('code','1034'));
        }
        //判断是否评价过订单
        $order_data['evaluate'] = '0';
        if ($order_data['status'] == 8 && $order_data['mobile'] == $this->mobile) {
            $count = ShouldSupplyComments::find()->select(['id'])->where(['oid'=>$order_data['id'],'mobile'=>$this->mobile])->count();
            if($count>0){
                $order_data['evaluate'] = '1';
            }
        }
        //获取用户信息
        if ($this->mobile == $order_data['dmobile']) {
            $order_data['user'] = $this->_getUserInfo($order_data['mobile']);
        } else {
            $order_data['user'] = $this->_getUserInfo($order_data['dmobile']);
        }
        //处理时间
        $order_data['aotu_offa_time'] = $this->NformatTime($order_data['aotu_offa_time']);
        $order_data['aotu_offb_time'] = $this->NformatTime($order_data['aotu_offb_time']);
        $order_data['aotu_confirm_time'] = $this->NformatTime($order_data['aotu_confirm_time']);

        $data = [];
        $data['item'] = $order_data;
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }



    /**
     * 获取用户信息
     * @param string $mobile 电话
     * @return array
     */
    private function _getUserInfo($mobile = '')
    {
        $user_base_info_model = new UserBasicInfo();
        $user_base_info_where['mobile'] = $mobile;
        $user_base_info_fields = 'nickname,avatar,mobile';
        $rs['avatar']   = '';
        $rs['nickname'] = '';
        $rs = $user_base_info_model->getInfo($user_base_info_where, true, $user_base_info_fields);
        if (!empty($rs)) {
            if ($rs['avatar']) {
                if (!strstr($rs['avatar'], 'http')) {
                    $rs['avatar'] = Common::C('imgHost').$rs['avatar'];
                }
            }
        }
        return $rs;
    }

    /**
     * 取消订单
     *
     * @return array
     */
    public function actionSupplyRemove(){
        //订单号
        $idsn = RequestHelper::post('idsn','','');
        if (empty($idsn)) {
            return $this->returnJsonMsg('511',[],Common::C('code','511'));
        }

        //查看订单是否存在
        $supply_order = ShouldSupplyOrder::find()->select(['did','mobile','price_all','status','pay_status'])->where(['idsn'=>$idsn,'community_id'=>$this->community_id])->asArray()->one();
        if (empty($supply_order)) {
            return $this->returnJsonMsg('1034',[],Common::C('code','1034'));
        }

        //查看是否是自己的订单
        if (!empty($supply_order['mobile']) && $supply_order['mobile'] != $this->mobile) {
            return $this->returnJsonMsg('2021',[],Common::C('code','2021'));
        }

        //查看是否是未支付的订单
        if($supply_order['pay_status'] == 1 && $supply_order['status'] == 1){
            //取消订单
            $res = ShouldSupplyOrder::updateAll(['status'=>7],['idsn'=>$idsn]);
            if(!$res){
                return $this->returnJsonMsg('400',[],Common::C('code','400'));
            }

            //查询登陆用户的信息
            $users = UserBasicInfo::find()->select(['nickname'])->where(['mobile'=>$this->mobile])->asArray()->one();
            //生成订单记录
            $this->getGenerateorder(2,$supply_order['did'],$users['nickname'].',取消了订单：'.$idsn);

            return $this->returnJsonMsg('200',[],Common::C('code','200'));
        } else {
            return $this->returnJsonMsg('1050',[],Common::C('code','1050'));
        }
    }

    /**
     * 拒绝订单
     *
     * @return array
     */
    public function actionSupplyRefuse(){
        //订单号
        $idsn = RequestHelper::post('idsn','','');
        if (empty($idsn)) {
            return $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        //查看订单是否存在
        $supply_order = ShouldSupplyOrder::find()->select(['id','did','mobile','dmobile','price_all','pay_status','status','title'])->where(['idsn'=>$idsn,'community_id'=>$this->community_id])->asArray()->one();
        if (empty($supply_order)) {
            return $this->returnJsonMsg('1034',[],Common::C('code','1034'));
        }

        //查看是否发布的服务
        if (!empty($supply_order['dmobile']) && $supply_order['dmobile'] != $this->mobile) {
            return $this->returnJsonMsg('2024',[],Common::C('code','2024'));
        }
        //查看是否是未支付的订单
        if ($supply_order['pay_status'] != 2) {
            return $this->returnJsonMsg('2025',[],Common::C('code','2025'));
        }
        if($supply_order['status'] != 3){
            return $this->returnJsonMsg('2025',[],Common::C('code','2025'));
        }
        //拒绝理由
        $remark = RequestHelper::post('remark','','');
        if (empty($remark)) {
            return $this->returnJsonMsg('2120',[],Common::C('code','2120'));
        }
        $this->setTransaction('db_social');
        //拒绝
        $res = ShouldSupplyOrder::updateAll(['status'=>5,'reject_remark'=>$remark],['idsn'=>$idsn]);
        if(!$res){
            $this->backTransaction();
            return $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        //消息推送
        $this->pushToAppSupply($supply_order['id']);

        //查询登陆用户的信息
        $users = UserBasicInfo::find()->select(['nickname'])->where(['mobile'=>$this->mobile])->asArray()->one();
        //生成订单记录
        $this->getGenerateorder(2,$supply_order['did'],$users['nickname'].',拒绝了订单：'.$idsn);

        $datalog = [
            'type' => 2,//1需求 2服务
            'idsn' => $idsn,
            'type_txt' => '对方拒绝接单，退回服务消费金额',
            'dmobile' => $supply_order['dmobile'],
        ];
        //退款明细
        $res2 = $this->addTradingDetail(1, $supply_order['price_all'], $supply_order['title'], $supply_order['mobile'], $idsn, $datalog);
        if (empty($res2)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [], Common::C('code','400'));
        }
        //更新用户余额
        $res3 = $this->saveUserChange($supply_order['mobile'], $supply_order['price_all']);
        if (empty($res3)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [], Common::C('code','400'));
        }
        
        $this->commitTransaction();
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }

    /**
     * 确认订单
     *
     * @return array
     */
    public function actionSupplyConfirm(){
        //订单号
        $idsn = RequestHelper::post('idsn','','');
        if (empty($idsn)) {
            return $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        //查看订单是否存在
        $supply_order = ShouldSupplyOrder::find()->select(['id','did','mobile','dmobile','price_all','status','pay_status'])->where(['idsn'=>$idsn,'community_id'=>$this->community_id])->asArray()->one();
        if (empty($supply_order)) {
            return $this->returnJsonMsg('1034',[],Common::C('code','1034'));
        }

        //查看是否发布的服务
        if (!empty($supply_order['dmobile']) && $supply_order['dmobile'] != $this->mobile) {
            return $this->returnJsonMsg('2024',[],Common::C('code','2024'));
        }

        //查看是否是已支付的订单
        if ($supply_order['pay_status'] != 2) {
            return $this->returnJsonMsg('2025',[],Common::C('code','2025'));
        }

        if ($supply_order['status'] !=3) {
            return $this->returnJsonMsg('2025',[],Common::C('code','2025'));
        }

        //确认
        $res = ShouldSupplyOrder::updateAll(['status'=>6,'aotu_confirm_time'=>date('Y-m-d H:i:s',strtotime('+7 days'))],['idsn'=>$idsn]);
        if(!$res){
            return $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        //消息推送
        $this->pushToAppSupply($supply_order['id']);

        //查询登陆用户的信息
        $users = UserBasicInfo::find()->select(['nickname'])->where(['mobile'=>$this->mobile])->asArray()->one();
        //生成订单记录
        $this->getGenerateorder(2,$supply_order['did'],$users['nickname'].',确认了订单：'.$idsn);

        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }

    /**
     * 确认完成
     *
     * @return array
     */
    public function actionOrderFinish(){
        //订单号
        $idsn = RequestHelper::post('idsn','','');
        if (empty($idsn)) {
            return $this->returnJsonMsg('511',[],Common::C('code','511'));
        }

        //查看订单是否存在
        $supply_order = ShouldSupplyOrder::find()->select(['id','did','mobile','price_all','status','pay_status','dmobile','title'])->where(['idsn'=>$idsn,'community_id'=>$this->community_id])->asArray()->one();
        if (empty($supply_order)) {
            return $this->returnJsonMsg('1034',[],Common::C('code','1034'));
        }

        //查看是否是自己的订单
        if ($supply_order['mobile'] != $this->mobile) {
            return $this->returnJsonMsg('2021',[],Common::C('code','2021'));
        }
        //查看是否已支付和已确认的订单
        if ($supply_order['pay_status'] != 2) {
            return $this->returnJsonMsg('2026',[],Common::C('code','2026'));
        }
        if ($supply_order['status'] != 6) {
            return $this->returnJsonMsg('2026',[],Common::C('code','2026'));
        }
        
        $this->setTransaction('db_social');
        //转账
        $res = ShouldSupplyOrder::updateAll(['status'=>8],['idsn'=>$idsn]);
        if(!$res){
            $this->backTransaction();
            return $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        //消息推送
        $this->pushToAppSupply($supply_order['id']);

        //查询登陆用户的信息
        $users = UserBasicInfo::find()->select(['nickname'])->where(['mobile'=>$this->mobile])->asArray()->one();
        //完成 生成订单记录
        $this->getGenerateorder(2,$supply_order['did'],$users['nickname'].',确认完成了订单：'.$idsn);
        
        $datalog = [
            'type' => 2,//1需求 2服务
            'idsn' => $idsn,
            'type_txt' => '交易完成，获得服务收益',
            'dmobile' => $supply_order['dmobile'],
        ];
        //收益明细
        $price_all = floatval($supply_order['price_all'])-round($this->fee_earnings*floatval($supply_order['price_all']),2);
        $res2 = $this->addTradingDetail(5, $price_all, $supply_order['title'], $supply_order['dmobile'], $idsn, $datalog);
        if (empty($res2)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [], Common::C('code','400'));
        }
        //更新用户余额
        $res3 = $this->saveUserChange($supply_order['dmobile'], $supply_order['price_all'], 1);
        if (empty($res3)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [], Common::C('code','400'));
        }
        $this->commitTransaction();
        
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
}