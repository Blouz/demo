<?php
/**
 * 需求接口
 *
 * PHP Version 12
 *
 * @category  Social
 * @package   Demand
 * @author    yaoxin <yaoxin@i500m.com>
 * @time      2017/06/21
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      yaoxin@i500m.com
 */
namespace frontend\modules\v12\controllers;

use yii\data\Pagination;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\ShouldDemand;
use frontend\models\i500_social\ShouldDemandOrder;

/**
 * Demand
 *
 * @category Social
 * @package  Demand
 * @author   yaoxin <yaoxin@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     yaoxin@i500m.com
 */
class MyDemandController extends BaseShouldController
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
     * 我发布的(需求)
     */
    public function actionMyPushDemand()
    {
        //页数,每页大小
        $page = RequestHelper::post('page', '1', '');
        $page_size = RequestHelper::post('page_size', '10', '');
        //查询需求
        $fileds = ['id', 'mobile', 'title', 'content', 'price', 'create_time', 'idsn', 'status'];
        $where = ['mobile'=>$this->mobile, 'status' => ['1', '2', '3', '4', '5']];
        //查询
        $demand = new ShouldDemand();
        $model = $demand->DemandList($where, [], [], $page, $page_size, $fileds, 1);
        //计算数量
        $count = $demand->DemandCount($where, [], []);
        //计算页数
        $pages=new Pagination(['totalCount' => $count]);
        $pages->setPageSize($page_size, true);
        $data = array();
        $data['list'] = $model;
        $data['count'] = $count;
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
    /**
     * 删除我的需求
     * @return array
     */
    public function actionDelMydemand()
    {
        //获取需求ID
        $did = RequestHelper::post('did', '', '');
        if(empty($did)) {
            $this->returnJsonMsg('2111', [], Common::C('code', '2111'));
        }
        //查询需求服务用户
        $info = ShouldDemand::find()->select(['mobile','status','price','title','idsn'])->where(['id'=>$did])->asArray()->one();
        if (empty($info)) {
            return $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        if($info['mobile'] != $this->mobile) {
            $this->returnJsonMsg('1067', [], Common::C('code', '1067'));
        }

        $this->setTransaction('db_social');
        //删除操作
        $res = ShouldDemand::updateAll(['status'=>6],['id'=>$did,'mobile'=>$this->mobile]);
        if (!$res) {
            $this->backTransaction();
            return $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        
        //待接单状态删除需求可退款
        if ($info['status']==2) {
            $datalog = [
                'type' => 1,//1需求 2服务
                'idsn' => $info['idsn'],
                'type_txt' => '删除，退回需求赏金',
            ];
            //退款明细
            $res2 = $this->addTradingDetail(1, $info['price'], $info['title'], $info['mobile'], $info['idsn'], $datalog);
            //更新用户余额
            $res3 = $this->saveUserChange($info['mobile'], $info['price']);
            if (empty($res2)||empty($res3)) {
                $this->backTransaction();
                $this->returnJsonMsg('400', [], Common::C('code','400'));
            }
        }
        $this->commitTransaction();
        
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
    /**
     * 接受(需求帮助)
     * @return array()
     */
    public function actionAcceptDemand()
    {
        //获取需求订单ID
        $oid = RequestHelper::post('oid', '', '');
        if(empty($oid)) {
            $this->returnJsonMsg('2112', [], Common::C('code', '2112'));
        }
        //查询用户名称
        $realname = UserBasicInfo::find()->select(['realname'])->where(['mobile'=>$this->mobile])->scalar();
        //查询信息
        $info = ShouldDemandOrder::find()->select(['mobile', 'did', 'dmobile', 'aotu_reject_time'])
                                         ->where(['id'=>$oid, 'status'=>1])->asArray()->one();
        if(empty($info)) {
            $this->returnJsonMsg('2114', [], Common::C('code', '2114'));
        }
        //判断是否为自己的订单
        if($this->mobile != $info['dmobile']) {
            $this->returnJsonMsg('2115', [], Common::C('code', '2115'));
        }
        //判断订单是否已经自动拒绝
        if(strtotime($info['aotu_reject_time']) < time()) {
            $this->returnJsonMsg('2118', [], Common::C('code', '2118'));
        }
        //修改订单状态为待确认, 自动确认时间为7天后
        $order = new ShouldDemandOrder();
        $order_info = $order->updateAll(['status'=>4, 'aotu_confirm_time'=>date('Y-m-d H:i:s', strtotime("+7 day"))], ['id'=>$oid]);
        if(!$order_info) {
            $this->returnJsonMsg('400', [], Common::C('code','400'));
        }
        //消息推送
        $this->pushToAppDemand($oid);
        //查询用户名称
        $drealname = UserBasicInfo::find()->select(['realname'])->where(['mobile'=>$info['mobile']])->scalar();
        //生成日志记录
        $order_detail = $this->getGenerateorder('1', $oid, $realname.'接受了'.$drealname.'预约需求订单'.$oid);
        $this->returnJsonMsg('200', [], Common::C('code','200'));
    }
    /**
     * 拒绝(需求帮助)
     * @return array()
     */
    public function actionRefuseDemand()
    {
        //获取需求订单ID
        $oid = RequestHelper::post('oid', '', '');
        if(empty($oid)) {
            $this->returnJsonMsg('2112', [], Common::C('code', '2112'));
        }
        $reject_remark = RequestHelper::post('reject_remark', '', '');
        if(empty($reject_remark)) {
            $this->returnJsonMsg('2120', [], Common::C('code', '2120'));
        }
        //查询用户名称
        $realname = UserBasicInfo::find()->select(['realname'])->where(['mobile'=>$this->mobile])->scalar();
        //查询信息
        $info = ShouldDemandOrder::find()->select(['mobile', 'did', 'dmobile', 'aotu_reject_time','status'])
                                         ->where(['id'=>$oid, 'status'=>1])->asArray()->one();
        if(empty($info)) {
            $this->returnJsonMsg('2114', [], Common::C('code', '2114'));
        }
        //判断是否为自己的订单
        if($this->mobile != $info['dmobile']) {
            $this->returnJsonMsg('2116', [], Common::C('code', '2116'));
        }
        //判断订单是否已经自动拒绝
        if(strtotime($info['aotu_reject_time']) < time()) {
            $this->returnJsonMsg('2118', [], Common::C('code', '2118'));
        }
        //修改订单状态为已拒绝,添加拒绝备注
        $order = new ShouldDemandOrder();
        $order_info = $order->updateAll(['status'=>3, 'reject_remark'=>$reject_remark], ['id'=>$oid]);
        if(empty($order_info)) {
            $this->returnJsonMsg('400', [], Common::C('code','400'));
        }
        //当前为已接单状态，改为待接单
        $demand = new ShouldDemand();
        $demand->updateAll(['status'=>2], ['id'=>$info['did'],'status'=>3]);
        //消息推送
        $this->pushToAppDemand($oid);
        //查询用户名称
        $drealname = UserBasicInfo::find()->select(['realname'])->where(['mobile'=>$info['mobile']])->scalar();
        //生成日志记录
        $order_detail = $this->getGenerateorder('1', $oid, $realname.'拒绝了'.$drealname.'预约需求订单'.$oid);
        $this->returnJsonMsg('200', [], Common::C('code','200'));
    }
    /**
     * 完成(需求帮助)
     * @return array()
     */
    public function actionFinishDemand()
    {
        //获取需求订单ID
        $oid = RequestHelper::post('oid', '', '');
        if(empty($oid)) {
            $this->returnJsonMsg('2112', [], Common::C('code', '2112'));
        }
        //查询用户名称
        $realname = UserBasicInfo::find()->select(['realname'])->where(['mobile'=>$this->mobile])->scalar();
        //查询信息
        $info = ShouldDemandOrder::find()->select(['mobile', 'did', 'dmobile', 'aotu_confirm_time','price','title'])
                                         ->where(['id'=>$oid, 'status'=>4])->asArray()->one();
        if(empty($info)) {
            $this->returnJsonMsg('2114', [], Common::C('code', '2114'));
        }
        //判断是否为自己的订单
        if($this->mobile != $info['dmobile']) {
            $this->returnJsonMsg('2117', [], Common::C('code', '2117'));
        }
        //判断订单是否已经自动完成
        if(strtotime($info['aotu_confirm_time']) < time()) {
            $this->returnJsonMsg('2119', [], Common::C('code', '2119'));
        }
        $this->setTransaction('db_social');
        //修改订单状态为已完成
        $order = new ShouldDemandOrder();
        $order_info = $order->updateAll(['status'=>5], ['id'=>$oid]);
        if(!$order_info) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [], Common::C('code','400'));
        }
        //消息推送
        $this->pushToAppDemand($oid);
        //查询用户名称
        $drealname = UserBasicInfo::find()->select(['realname'])->where(['mobile'=>$info['mobile']])->scalar();
        //生成日志记录
        $order_detail = $this->getGenerateorder('1', $oid, $realname.'确认了'.$drealname.'完成需求订单'.$oid);
        
        //获取订单idsn号
        $idsn = ShouldDemand::find()->select(['idsn'])->where(['id'=>$info['did']])->scalar();
        $datalog = [
            'type' => 1,//1需求 2服务
            'idsn' => $idsn,
            'type_txt' => '交易完成，获得需求赏金',
            'dmobile' => $info['dmobile'],
        ];
        //收益明细
        $price_all = floatval($info['price'])-round($this->fee_earnings*floatval($info['price']),2);
        $res2 = $this->addTradingDetail(5, $price_all, $info['title'], $info['mobile'], $idsn, $datalog);
        if (empty($res2)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [], Common::C('code','400'));
        }
        //更新用户余额
        $res3 = $this->saveUserChange($info['mobile'], $info['price'], 1);
        if (empty($res3)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [], Common::C('code','400'));
        }
        $this->commitTransaction();
        
        $this->returnJsonMsg('200', [], Common::C('code','200'));
    }
}
