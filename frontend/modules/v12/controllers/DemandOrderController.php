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
class DemandOrderController extends BaseShouldController
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
     * 预约需求单
     */
    public function actionBookingDemand()
    {
        //获取需求ID
        $did = RequestHelper::post('did', '', '');
        if(empty($did)) {
            $this->returnJsonMsg('2111', [], Common::C('code', '2111'));
        }
        //用户未认证认证信息
        if (!$this->checkUserCert()) {
            $this->returnJsonMsg('2106', [], Common::C('code', '2106'));
        }
        //获取用户信息
        $nickname = UserBasicInfo::find()->select(['nickname'])->where(['mobile'=>$this->mobile])->asArray()->one();
        //获取需求用户信息
        $demand_info = ShouldDemand::find()->select(['id', 'mobile', 'title', 'content', 'price', 'status', 'community_id', 'end_time'])
                                           ->where(['id'=>$did,'community_id'=>$this->community_id, 'is_public'=>0])->asArray()->one();
        //判断订单是否存在
        if(empty($demand_info)) {
            $this->returnJsonMsg('2114', [], Common::C('code', '2114'));
        }
        //已接单
        if($demand_info['status']==3) {
            $this->returnJsonMsg('2127', [], Common::C('code', '2127'));
        }
        //非正常状态
        if($demand_info['status']!=2) {
            $this->returnJsonMsg('2114', [], Common::C('code', '2114'));
        }
        //判断用户是否接取自己的订单
        if($this->mobile == $demand_info['mobile']) {
            $this->returnJsonMsg('2121', [], Common::C('code', '2121'));
        }
        //判断用户是否与订单属于同一小区
        if($this->community_id != $demand_info['community_id']) {
            $this->returnJsonMsg('2122', [], Common::C('code', '2122'));
        }
        //判断订单是否过了截止时间
        if(strtotime($demand_info['end_time']) < time()) {
            $this->returnJsonMsg('2124', [], Common::C('code', '2124'));
        }
        $model_data = [
            'mobile'=>$this->mobile,
            'did'=>$demand_info['id'],
            'dmobile'=>$demand_info['mobile'],
            'community_id'=>$demand_info['community_id'],
            'title'=>$demand_info['title'],
            'content'=>$demand_info['content'],
            'price'=>$demand_info['price'],
            'status' => 1,//待应答
            'aotu_reject_time'=>date('Y-m-d H:i:s', strtotime('+6 hour')),
        ];
        //保存数据
        $model = new ShouldDemandOrder();
        $res = $model->insertInfo($model_data);
        if(empty($res)) {
            $this->returnJsonMsg('2125', [], Common::C('code', '2125'));
        }
        //消息推送
        $this->pushToAppDemand($res);
        //修改需求服务状态,改为已接单
        $demand = new ShouldDemand();
        $update_demand = $demand->updateAll(['status'=>3], ['id'=>$did]);
        //添加订单记录
        $order_detail = $this->getGenerateorder('1', $res, $nickname['nickname'].'预约了需求'.$did);
        if(empty($update_demand) || empty($order_detail)) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $data['oid'] = $res;
        
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
    /**
     * 立即预约页面信息接口
     * @return array()
     */
    public function actionImmediately()
    {
        //获取需求ID
        $did = RequestHelper::post('did', '', '');
        if(empty($did)) {
            $this->returnJsonMsg('2111', [], Common::C('code', '2111'));
        }
        //用户未认证认证信息
        if (!$this->checkUserCert()) {
            $this->returnJsonMsg('2106', [], Common::C('code', '2106'));
        }

        $info = ShouldDemand::find()->select(['id', 'mobile', 'idsn', 'title', 'price','status'])
                                    ->with(['user'=>function($query){
                                        $query->select(['mobile', 'nickname', 'avatar']);
                                    }])
                                    ->where(['id'=>$did, 'community_id'=>$this->community_id, 'is_public'=>0])
                                    ->asArray()
                                    ->one();
        if(empty($info)) {
            $this->returnJsonMsg('2114', [], Common::C('code', '2114'));
        }
        //已接单
        if($info['status']==3) {
            $this->returnJsonMsg('2127', [], Common::C('code', '2127'));
        }
        //非正常状态
        if($info['status']!=2) {
            $this->returnJsonMsg('2114', [], Common::C('code', '2114'));
        }
        $data['item'] = $info;
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
    /**
     * 需求支付信息接口
     * @return array()
     */
    public function actionDemandPayment()
    {
        //获取需求ID
        $idsn = RequestHelper::post('idsn', '', '');
        if(empty($idsn)) {
            $this->returnJsonMsg('2126', [], Common::C('code', '2126'));
        }

        $info = ShouldDemand::find()->select(['price'])
                                    ->where(['idsn'=>$idsn, 'status'=>1, 'community_id'=>$this->community_id])
                                    ->asArray()
                                    ->one();
        if(empty($info)) {
            $this->returnJsonMsg('2114', [], Common::C('code', '2114'));
        }
        
        $data['price'] = $info['price'];
        $data['account'] = $this->getUserAccount();
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
}
