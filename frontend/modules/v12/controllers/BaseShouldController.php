<?php
/**
 * v12 供需基类
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/6/21
 */

namespace frontend\modules\v12\controllers;

use common\helpers\Common;
use frontend\models\i500_social\ShouldOrderDetail;
use frontend\models\i500_social\ShouldDemand;
use frontend\models\i500_social\ShouldDemandOrder;
use frontend\models\i500_social\ShouldSupplyOrder;

class BaseShouldController extends BaseController {
    //收益手续费
    public $fee_earnings = 0.03;
    //提现手续费
    public $fee_withdrawal = 0.005;
    //提现累计超出收取手续费
    public $max_withdrawal = 20000;
    
    /**
     * 初始化
     * @return array
     */
    public function init() {
        parent::init();
        //规定的post移除表情
        $this->postTextEmpty();
        //自动处理需求状态
        $this->autoTimeDemandStatus();
        //自动处理供求状态
        $this->autoTimeSupplyStatus();
    }
    
    //规定的post移除表情
    private function postTextEmpty() {
        $u_arr = ['keywords','title','content','remark','reject_remark'];
        foreach ($u_arr as $key) {
            if (isset($_POST[$key])) {
                $_POST[$key] = Common::userTextEmpty($_POST[$key]);
            }
        }
    }
    
    //自动处理需求状态
    private function autoTimeDemandStatus() {
        $deman = new ShouldDemand();
        $order = new ShouldDemandOrder();
        
        //***需求到期退款
        $end_time_data = $deman->find()->select(['id','idsn','mobile','title','price','status'])
                         ->where(['status'=>['1', '2'], 'community_id'=>$this->community_id])
                         ->andWhere(['<', 'end_time', date('Y-m-d H:i:s')])
                         ->asArray()->all();
        foreach ($end_time_data as $val) {
            $this->setTransaction('db_social');
            //已支付需退款
            if ($val['status']==2) {
                $datalog = [
                    'type' => 1,//1需求 2服务
                    'idsn' => $val['idsn'],
                    'type_txt' => '到期，退回需求赏金',
                ];
                //退款明细
                $rs = $this->addTradingDetail(1, $val['price'], $val['title'], $val['mobile'], '' , $datalog);
                //更新用户余额
                $rs1 = $this->saveUserChange($val['mobile'], $val['price']);
                
                if (empty($rs)||empty($rs1)){
                    //$this->backTransaction();
                    //continue;
                }
            }
            //到期状态
            $rs2 = $deman->updateAll(['status'=>4], ['id'=>$val['id']]);
            //生成一条订单记录
            $rs3 = $this->getGenerateorder(1, $val['id'], '到期自动处理');
            
            if (empty($rs2)||empty($rs3)){
                $this->backTransaction();
                continue;
            }
            $this->commitTransaction();
        }
        
        //***需求待应答-自动拒绝时间
        $aotu_reject_time_data = $order->find()->select(['id','did'])
                                 ->where(['mobile'=>$this->mobile])
                                 ->orWhere(['dmobile'=>$this->mobile])
                                 ->andWhere(['status'=>1])
                                 ->andWhere(['<', 'aotu_reject_time', date('Y-m-d H:i:s')])
                                 ->asArray()->all();
        foreach ($aotu_reject_time_data as $val) {
            $this->setTransaction('db_social');
            //需求状态改为待接单
            $rs1 = $deman->updateAll(['status'=>2], ['id'=>$val['did']]);
            //订单自动拒绝
            $rs2 = $order->updateAll(['status'=>2], ['id'=>$val['id']]);
            //生成一条订单记录
            $rs3 = $this->getGenerateorder(1, $val['id'], '待应答超时自动拒绝');
            
            if (empty($rs1)||empty($rs2)||empty($rs3)){
                $this->backTransaction();
                continue;
            }
            $this->commitTransaction();
        }
        
        //***需求待确认-自动确认时间
        $aotu_confirm_data = $order->find()->select(['id','did','mobile','title','price','dmobile'])
                             ->where(['mobile'=>$this->mobile])
                             ->orWhere(['dmobile'=>$this->mobile])
                             ->andWhere(['status'=>4])
                             ->andWhere(['<', 'aotu_confirm_time', date('Y-m-d H:i:s')])
                             ->asArray()->all();
        foreach ($aotu_confirm_data as $val) {
            $this->setTransaction('db_social');
            $datalog = [
                'type' => 1,//1需求 2服务
                'did' => $val['did'],
                'type_txt' => '确认超时，获得需求赏金',
                'dmobile' => $val['dmobile'],
            ];
            //收益明细
            $rs1 = $this->addTradingDetail(5, $val['price'], $val['title'], $val['mobile'], '', $datalog);
            //更新用户余额
            $rs2 = $this->saveUserChange($val['mobile'], $val['price'], 1);
            //订单自动拒绝
            $rs3 = $order->updateAll(['status'=>5], ['id'=>$val['id']]);
            //生成一条订单记录
            $rs4 = $this->getGenerateorder(1, $val['id'], '待确认超时自动确认');
            
            if (empty($rs1)||empty($rs2)||empty($rs3)||empty($rs4)){
                $this->backTransaction();
                continue;
            }
            $this->commitTransaction();
        }
    }
    
    //自动处理供求状态
    private function autoTimeSupplyStatus() {
        $order = new ShouldSupplyOrder();
        
        //***供求待支付-自动关闭时间
        $aotu_offa_time_data = $order->find()->select(['id','did'])
                               ->where(['mobile'=>$this->mobile])
                               ->orWhere(['dmobile'=>$this->mobile])
                               ->andWhere(['status'=>1])
                               ->andWhere(['<', 'aotu_offa_time', date('Y-m-d H:i:s')])
                               ->asArray()->all();
        foreach ($aotu_offa_time_data as $val) {
            $this->setTransaction('db_social');
            //待支付超时自动关闭
            $rs1 = $order->updateAll(['status'=>2], ['id'=>$val['id']]);
            //生成一条订单记录
            $rs2 = $this->getGenerateorder(2, $val['id'], '待支付超时自动关闭');
            
            if (empty($rs1)||empty($rs2)){
                $this->backTransaction();
                continue;
            }
            $this->commitTransaction();
        }
        
        //***供求待接单-自动关闭时间
        $aotu_offb_time_data = $order->find()->select(['id','idsn','mobile','title','price_all','dmobile'])
                               ->where(['mobile'=>$this->mobile])
                               ->orWhere(['dmobile'=>$this->mobile])
                               ->andWhere(['status'=>3])
                               ->andWhere(['<', 'aotu_offb_time', date('Y-m-d H:i:s')])
                               ->asArray()->all();
        foreach ($aotu_offb_time_data as $val) {
            $this->setTransaction('db_social');
            $datalog = [
                'type' => 2,//1需求 2服务
                'idsn' => $val['idsn'],
                'type_txt' => '接单超时，退回服务消费',
                'dmobile' => $val['dmobile'],
            ];
            //退款明细
            $rs1 = $this->addTradingDetail(1, $val['price_all'], $val['title'], $val['mobile'], '', $datalog);
            //更新用户余额
            $rs2 = $this->saveUserChange($val['mobile'], $val['price_all']);
            //待接单超时自动关闭
            $rs3 = $order->updateAll(['status'=>4], ['id'=>$val['id']]);
            //生成一条订单记录
            $rs4 = $this->getGenerateorder(2, $val['id'], '待接单超时自动关闭');
            
            if (empty($rs1)||empty($rs2)||empty($rs3)||empty($rs4)){
                $this->backTransaction();
                continue;
            }
            $this->commitTransaction();
        }

        //***供求待确认-自动确认时间
        $aotu_confirm_time_data = $order->find()->select(['id','idsn','mobile','dmobile','title','price_all'])
                                  ->where(['mobile'=>$this->mobile])
                                  ->orWhere(['dmobile'=>$this->mobile])
                                  ->andWhere(['status'=>6])
                                  ->andWhere(['<', 'aotu_confirm_time', date('Y-m-d H:i:s')])
                                  ->asArray()->all();
        foreach ($aotu_confirm_time_data as $val) {
            $this->setTransaction('db_social');
            $datalog = [
                'type' => 2,//1需求 2服务
                'idsn' => $val['idsn'],
                'type_txt' => '自动确认，获得服务收益',
                'dmobile' => $val['mobile'],
            ];
            //收益明细
            $rs1 = $this->addTradingDetail(5, $val['price_all'], $val['title'], $val['dmobile'], '', $datalog);
            //更新用户余额
            $rs2 = $this->saveUserChange($val['dmobile'], $val['price_all'], 1);
            //待确认超时自动确认
            $rs3 = $order->updateAll(['status'=>8], ['id'=>$val['id']]);
            //生成一条订单记录
            $rs4 = $this->getGenerateorder(2, $val['id'], '待确认超时自动确认');
            
            if (empty($rs1)||empty($rs2)||empty($rs3)||empty($rs4)){
                $this->backTransaction();
                continue;
            }
            $this->commitTransaction();
        }
    }

    /**
     * 生成一条订单记录
     * @param string $type      类别 1需求订单 2供求订单
     * @param string $did       订单ID
     * @param string $content   明细内容
     * @return boolean
     */
    public function getGenerateorder($type, $did, $content) {
        $res = false;
        if(!empty($type) && !empty($did) && !empty($content)) {
            $model_data = [
                'type'=>$type,
                'did'=>$did,
                'remarks'=>$content
            ];
            //保存数据
            $model = new ShouldOrderDetail();
            $order_detail = $model->insertInfo($model_data);
            if(!empty($order_detail)) {
                $res = true;
            }
        }
        
        return $res;
    }
    
    /**
     * 需求订单-推送消息(继承父类)
     * @param int $id 需求订单id
     * @return bool
     */
    public function pushToAppDemand($id=0) {
        //参数为空
        if (empty($id)) {
            return false;
        }
        //查询订单详情
        $order = ShouldDemandOrder::findOne(['id'=>$id]);
        if (empty($order)) {
            return false;
        }
        switch ($order->status) {
            case 1:
                $mobile = $order->dmobile;
                $description = "您的需求有人可以帮助啦，快去接受帮助吧。";
                break;
            case 3:
                $mobile = $order->mobile;
                $description = "对方拒绝，{$order->reject_remark}，继续加油！";
                break;
            case 4:
                $mobile = $order->mobile;
                $description = "对方愿意接受你的帮助！";
                break;
            case 5:
                $mobile = $order->mobile;
                $description = "{$order->title}，订单已完结，请查看账户余额！";
                break;
            default:
                return false;
                break;
        }
        //自定义参数
        $auto_data = [
            'order_id' => $order->id,//订单id
            'status' => $order->status,//订单状态
            'is_red' => in_array($order->status, [1,4,5]) ? 1 : 0,//是否置红
        ];
        return $this->pushToApp($mobile, 21, '需求订单消息', $description, $auto_data);
    }
    
    /**
     * 服务订单-推送消息(继承父类)
     * @param int $id 服务订单id
     * @return bool
     */
    public function pushToAppSupply($id=0) {
        //参数为空
        if (empty($id)) {
            return false;
        }
        //查询订单详情
        $order = ShouldSupplyOrder::findOne(['id'=>$id]);
        if (empty($order)) {
            return false;
        }
        switch ($order->status) {
            case 1:
                $mobile = $order->dmobile;
                $description = "您的服务有人下单啦，正在等待对方付款。";
                break;
            case 3:
                $mobile = $order->dmobile;
                $description = "有人买了你的服务了，请在6小时内接单，否则订单会关闭哦。";
                break;
            case 5:
                $mobile = $order->mobile;
                $description = "对方拒绝，{$order->reject_remark}，再去找找其他服务提供者吧。";
                break;
            case 6:
                $mobile = $order->mobile;
                $description = "你购买的服务有人接单啦，快去看看吧。";
                break;
            case 8:
                $mobile = $order->dmobile;
                $description = "{$order->title}，订单已完结，请查看账户余额。";
                break;
            default:
                return false;
                break;
        }
        //自定义参数
        $auto_data = [
            'order_id' => $order->idsn,//订单idsn
            'status' => $order->status,//订单状态
            'is_red' => in_array($order->status, [1,3,6,8]) ? 1 : 0,//是否置红
        ];
        return $this->pushToApp($mobile, 22, '服务订单消息', $description, $auto_data);
    }
}
