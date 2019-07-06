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

use Yii;
use yii\data\Pagination;
use common\helpers\Common;
use yii\helpers\ArrayHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\ShouldDemand;
use frontend\models\i500_social\ShouldDemandOrder;
use frontend\models\i500_social\ShouldAdv;

/**
 * Demand
 *
 * @category Social
 * @package  Demand
 * @author   yaoxin <yaoxin@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     yaoxin@i500m.com
 */
class DemandController extends BaseShouldController
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
     * 需求广场列表接口/搜索接口/他发布的需求
     * @return array
     */
    public function actionDemandList()
    {
        $where = ['community_id'=>$this->community_id, 'status'=>2, 'is_public'=>0];
        $andwhere2 = [];
        //搜索条件
        $keywords = RequestHelper::post('keywords', '', '');
        if (!empty($keywords)) {
            $andwhere2 = ['like' ,'title', $keywords];
        }
        //他的手机号
        $user_mobile = RequestHelper::post('user_mobile', '', 'trim');
		if (!empty($user_mobile) && Common::validateMobile($user_mobile)) {
            $where['mobile'] = $user_mobile;
        }
        //页数
        $page = RequestHelper::post('page', '1', '');
        $page_size = RequestHelper::post('page_size', '10', '');
        //查询需求
        $fileds = ['id', 'mobile', 'title', 'content', 'price', 'create_time'];
        //查询
        $demand = new ShouldDemand();
        $model = $demand->DemandList($where, [], $andwhere2, $page, $page_size, $fileds, 1);
        //计算需求数量
        $count = $demand->DemandCount($where, [], $andwhere2);
        //广告
        $advert = ShouldAdv::find()->select(['images as image'])->where(['type'=>1])->orderBy('create_time Desc')->asArray()->all();
        //计算页数
        $pages= new Pagination(['totalCount' => $count]);
        $pages->setPageSize($page_size, true);
        $data = array();
        $data['list'] = $model;
        $data['count'] = $count;
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        $data['advert'] = empty($advert)?[]:$advert;
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
    /**
     * 广场需求详情接口
     * @return array
     */
    public function actionSquaredemandDetails()
    {
        $did = RequestHelper::post('did', '', '');
        //id和idsn都为空
        if(empty($did)) {
            $this->returnJsonMsg('2111', [], Common::C('code', '2111'));
        }
        //查询需求
        $fileds = ['id', 'idsn','mobile', 'title', 'content', 'price', 'end_time', 'create_time','community_id', 'status'];
        $where = ['community_id'=> $this->community_id, 'is_public'=>0];
        $andwhere = ['or',['id'=>$did],['idsn'=>$did]];
        //查询
        $demand = new ShouldDemand();
        $model = $demand->DemandList($where, $andwhere, [], '', '', $fileds, 2);
        //判断是否为空
        if(empty($model)) {
            $this->returnJsonMsg('2114', [], Common::C('code', '2114'));
        }
        //查询用户ID
        $user_id = User::find()->select(['id'])->where(['mobile'=>$model['mobile']])->scalar();
        $model['user']['user_id'] = $user_id;
        $data['item'] = $model;
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
    /**
     * 发布需求
     * @return array()
     */
    public function actionPushDemand()
    {
        //用户未认证认证信息
        if (!$this->checkUserCert()) {
            $this->returnJsonMsg('2106', [], Common::C('code', '2106'));
        }
        $model = new ShouldDemand();
        $data = array();
        $data['mobile'] = $this->mobile;
        $data['title'] = RequestHelper::post('title','','trim');
        $data['content'] = RequestHelper::post('content','','trim');
        $data['price'] = RequestHelper::post('price','','trim');
        $data['end_time'] = RequestHelper::post('end_time','','trim');
        //验证金额
        $data['price'] = $this->returnPrice($data['price']);
        if (empty($data['price'])) {
            $this->returnJsonMsg('2113', [], '请重新输入正确的赏金');
        }
        //到期时间
        if (empty($data['end_time'])) {
            $this->returnJsonMsg('511', [], '请选择到期时间');
        }
        if (!strtotime($data['end_time'])) {
            $this->returnJsonMsg('403', [], '请输入正确的到期时间');
        }
        $end_time = strtotime(date('Y-m-d',strtotime($data['end_time'])).' 23:59:59');
        if($end_time < time()) {
            $this->returnJsonMsg('403', [], '到期时间不能小于当前时间');
        }
        if($end_time > strtotime(date('Y-m-d',strtotime("+7 day")).' 23:59:59')) {
            $this->returnJsonMsg('403', [], '到期时间不能超过当前时间7天后');
        }
        $data['end_time'] = date('Y-m-d H:i:s', $end_time);

        //获取订单ID
        $idsn = $this->getIdsn('JD');
        //验证
        $model->attributes = $data;
        //保存
        $model->idsn = $idsn;
        $model->community_id = $this->community_id;
        $res = $model->save();

        if ($res) {
            $info['idsn'] = $idsn;
            $this->returnJsonMsg('200', [$info], Common::C('code', '200'));
        }else{
            $errors = $model->getFirstErrors();
            $errors = array_values($errors);
            $this->returnJsonMsg('2110', [], ArrayHelper::getValue($errors, 0, 'Error'));
        }
    }
    /**
     * 我的需求订单列表
     * @return array()
     */
    public function actionMydemandOrderlist()
    {
        //页数
        $page = RequestHelper::post('page', '1', '');
        $page_size = RequestHelper::post('page_size', '10', '');
        //订单状态 1全部,2待应答,3待确认,4已完成
        $status = RequestHelper::post('status', '', '');
        $and_where = [];
        switch ($status)
        {
            case 1:
                $and_where['status'] = 1;
                break;
            case 4:
                $and_where['status'] = 4;
                break;
            case 5:
                $and_where['status'] = 5;
                break;
        }
        //查询需求
        $fileds = ['id', 'mobile', 'did', 'dmobile', 'title', 'content', 'price', 'status'];
        $where = ['mobile'=>$this->mobile, 'community_id'=>$this->community_id];
        $orwhere = ['dmobile'=>$this->mobile, 'community_id'=>$this->community_id];
        //查询
        $order = new ShouldDemandOrder();
        $model = $order->DorderList($where, $orwhere, $and_where, $page, $page_size, $fileds, 1);
        //计算订单数量
        $count = $order->DorderCount($where, $orwhere, $and_where);
        //查询页数
        $pages = new Pagination(['totalCount' => $count]);
        $pages->setPageSize($page_size, true);
        //查询用户信息
        foreach($model as $key=>$value) {
            if($this->mobile == $value['dmobile']) {
                $model[$key]['user'] = $this->_getUserInfo($value['mobile']);
            }else{
                $model[$key]['user'] = $this->_getUserInfo($value['dmobile']);
            }
        }
        $data = array();
        $data['list'] = $model;
        $data['count'] = $count;
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
    /**
     * 我的需求订单详情
     * @return array()
     */
    public function actionMydemandDetails()
    {
        $oid = RequestHelper::post('oid', '', '');
        if (empty($oid)) {
            $this->returnJsonMsg('2112', [], Common::C('code', '2112'));
        }
        //查询需求
        $fileds = ['id', 'mobile', 'did', 'dmobile', 'title', 'content', 'price', 'status', 'aotu_reject_time', 'aotu_confirm_time','create_time', 'reject_remark'];
        $where = ['id'=>$oid, 'mobile'=>$this->mobile];
        $orwhere = ['id'=>$oid, 'dmobile'=>$this->mobile];
        //查询
        $order = new ShouldDemandOrder();
        $model = $order->DorderList($where, $orwhere, [], '', '', $fileds, 2);
        if(empty($model)) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        //查询用户信息
        if($this->mobile == $model['dmobile']) {
            $model['user'] = $this->_getUserInfo($model['mobile']);
        }else{
            $model['user'] = $this->_getUserInfo($model['dmobile']);
        }
        //获取订单idsn号
        $idsn = ShouldDemand::find()->select(['idsn'])->where(['id'=>$model['did']])->scalar();
        $model['idsn'] = $idsn;
        $model['aotu_reject_time'] = $this->NformatTime($model['aotu_reject_time']);
        $model['aotu_confirm_time'] = $this->NformatTime($model['aotu_confirm_time']);
        $data['item'] = $model;
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
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
        $rs['create_time'] = '';
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
}
