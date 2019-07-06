<?php
/**
 * 供需个人主页
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/6/21
 */

namespace frontend\modules\v12\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use common\helpers\BankHelper;
use frontend\models\i500_social\ShouldDemandOrder;
use frontend\models\i500_social\ShouldSupplyOrder;
use frontend\models\i500_social\UserCertification;
use frontend\models\i500_social\UserBankcardNew;
use frontend\models\i500_social\UserTradingDetail;
use frontend\models\i500_social\UserTradingWithdrawal;
use frontend\models\i500_social\UserVerifyCode;

class MyShouldController extends BaseShouldController {
    
    //我的主页
    public function actionIndex() {
        //未读需求订单个数
        $demand = ShouldDemandOrder::find()->select(['id'])->where(['mobile'=>$this->mobile,'is_read'=>2])->orWhere(['dmobile'=>$this->mobile,'dis_read'=>2])->count();
        $data['demand'] = $demand;
        //未读服务订单个数
        $service = ShouldSupplyOrder::find()->select(['id'])->where(['mobile'=>$this->mobile,'is_read'=>2])->orWhere(['dmobile'=>$this->mobile,'dis_read'=>2])->count();
        $data['service'] = $service;
        
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
    
    //用户设置实名认证
    public function actionUserSetCertification() {
        //真实姓名
        $realname = RequestHelper::post('realname', '', 'trim');
        if (empty($realname)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //身份证号
        $user_card = RequestHelper::post('user_card', '', 'trim');
        if (empty($user_card)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //获取用户信息对象
        $info = UserCertification::findOne(['mobile'=>$this->mobile]);
        //已认证
        if (!empty($info) && $info->status==2) {
            $this->returnJsonMsg('648', [], Common::C('code', '648'));
        }
        //获取用户信息对象
        $card = UserCertification::findOne(['user_card'=>$user_card]);
        //身份证号已存在
        if (!empty($card) && $card->mobile!=$this->mobile) {
            $this->returnJsonMsg('620', [], '身份证号已存在');
        }
        //实名认证审核失败
        if (!$this->credit_antifraud_verify($user_card, $realname)) {
            $this->returnJsonMsg('1061', [], Common::C('code', '1061'));
        }
        $info = empty($info) ? new UserCertification() : $info;
        $info->uid = $this->uid;
        $info->mobile = $this->mobile;
        $info->realname = $realname;
        $info->user_card = $user_card;
        $info->status = 2;
        $res = $info->save();
        //保存失败
        if (empty($res)) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $data['status'] = '2';
        $data['realname'] = $realname;
        $data['user_card'] = substr($user_card,0,3).'******'.substr($user_card,-4);
        
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
    
    //用户是否实名认证
    public function actionUserCertification() {
        $data['status'] = '1';
        $data['realname'] = '';
        $data['user_card'] = '';
        $info = UserCertification::find()->select(['status','realname','user_card'])->where(['mobile'=>$this->mobile])->asArray()->one();
        //用户认证信息
        if (!empty($info) && $info['status']==2) {
            $data['status'] = '2';
            $data['realname'] = $info['realname'];
            $data['user_card'] = substr($info['user_card'],0,3).'******'.substr($info['user_card'],-4);
        }
        
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
    
    //查询余额
    public function actionUserAccount() {
        //余额
        $data['account'] = $this->getUserAccount();
        
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
    
    //银行卡列表
    public function actionBankcardList() {
        //获取我的银行卡
        $list = UserBankcardNew::find()->select(['id','bank_number','bank_type','bank_belong','is_default','bank_belong_code','bank_color'])
                ->where(['mobile'=>$this->mobile])->orderBy('create_time asc')->asArray()->all();
        foreach ($list as $key=>$val) {
            $val['bank_number'] = str_pad(substr($val['bank_number'],-4),strlen($val['bank_number'])-4,'*',STR_PAD_LEFT);
            $list[$key] = $val;
        }
        $data['list'] = $list;
        
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
    
    //验证银行卡
    public function actionBankcardCheck() {
        //银行卡号
        $bank_number = RequestHelper::post('bank_number', '', 'trim');
        if (empty($bank_number)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //根据银行卡号获取卡信息
        $bank_arr = BankHelper::getBankInfo($bank_number);
        if (empty($bank_arr)) {
            $this->returnJsonMsg('1109', [], Common::C('code', '1109'));
        }
        $data['bank_type'] = empty($bank_arr['cardTypeName']) ? '未知' : $bank_arr['cardTypeName'];
        $data['bank_belong'] = empty($bank_arr['bankName']) ? '未知' : $bank_arr['bankName'];
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
    
    //发送银行卡预留手机号的验证码
    public function actionBankcardSendCode() {
        //预留手机号
        $bmobile = RequestHelper::post('bmobile', '', '');
		if (empty($bmobile) || !Common::validateMobile($bmobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $code = Common::getRandomNumber();
        $sms_content = Common::getSmsTemplate(9, $code);
        //保存验证码
        $user_verify_code_model = new UserVerifyCode();
        $user_verify_code_data['mobile']     = $bmobile;
        $user_verify_code_data['code']       = $code;
        $user_verify_code_data['type']       = 9;
        $user_verify_code_data['expires_in'] = date('Y-m-d H:i:s', (time()+ Common::C('verify_code_timeout')));
        $rs = $user_verify_code_model->insertInfo($user_verify_code_data);
        if (empty($rs)) {
            $this->returnJsonMsg('500', [], '信息保存失败');
        }
        //发送短信
        $this->sendSmsChannel($bmobile, $sms_content);
        
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
    
    //添加银行卡
    public function actionBankcardAdd() {
        //银行卡号
        $bank_number = RequestHelper::post('bank_number', '', 'trim');
        if (empty($bank_number)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //预留手机号
        $bmobile = RequestHelper::post('bmobile', '', 'trim');
		if (empty($bmobile) || !Common::validateMobile($bmobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        //验证码
        $code = RequestHelper::post('code', '', 'trim');
        if (empty($code)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        $where = ['mobile'=>$bmobile, 'code'=>$code, 'type'=>9];
        //获取验证码信息
        $code_model = new UserVerifyCode();
        $code_info = $code_model->getInfo($where, true, 'id,expires_in', '', 'id desc');
        //不存在
        if (empty($code_info)) {
            $this->returnJsonMsg('610', [], Common::C('code', '610'));
        }
        //超时
        if (strtotime($code_info['expires_in']) <= time()) {
            $this->returnJsonMsg('609', [], Common::C('code', '609'));
        }
        //设置该验证码失效
        UserVerifyCode::updateAll(['expires_in'=>date('Y-m-d H:i:s')],['id'=>$code_info['id']]);
        
        //银行卡号已存在
        $info = UserBankcardNew::findOne(['mobile'=>$this->mobile, 'bank_number'=>$bank_number]);
        if (!empty($info)) {
            $this->returnJsonMsg('1102', [], Common::C('code', '1102'));
        }
        //用户未认证认证信息
        $usercert = UserCertification::find()->select(['realname','user_card'])->where(['mobile'=>$this->mobile,'status'=>2])->asArray()->one();
        if(empty($usercert)){
            $this->returnJsonMsg('1061', [], Common::C('code', '1061'));
        }
        /*
        //银行卡认证失败
        if (!$this->credit_antifraud_verify($usercert['user_card'], $usercert['realname'], $bank_number, $bmobile)) {
            $this->returnJsonMsg('1109', [], Common::C('code', '1109'));
        }
        */
        //根据银行卡号获取卡信息
        $bank_arr = BankHelper::getBankInfo($bank_number);
        if (empty($bank_arr)) {
            $this->returnJsonMsg('1109', [], Common::C('code', '1109'));
        }
        
        $model = new UserBankcardNew();
        $model->uid = $this->uid;
        $model->mobile = $this->mobile;
        $model->bmobile = $bmobile;
        $model->bank_number = $bank_number;
        $model->bank_type = empty($bank_arr['cardTypeName']) ? '未知' : $bank_arr['cardTypeName'];
        $model->bank_type_code = empty($bank_arr['cardType']) ? '' : $bank_arr['cardType'];
        $model->bank_belong = empty($bank_arr['bankName']) ? '未知' : $bank_arr['bankName'];
        $model->bank_belong_code = empty($bank_arr['bank']) ? '' : $bank_arr['bank'];
        $model->bank_color = empty($bank_arr['bankColor']) ? 1 : $bank_arr['bankColor'];
        $model->bank_code = '';
        //无默认银行卡，此卡为默认
        $dinfo = UserBankcardNew::findOne(['mobile'=>$this->mobile, 'is_default'=>1]);
        if (empty($dinfo)) {
            $model->is_default = 1;
        }
        $res = $model->save();
        //保存失败
        if (empty($res)) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $data['id'] = $model->id;
        
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
    
    //解除银行卡
    public function actionBankcardDel() {
        //详情id
        $id= RequestHelper::post('id', 0, 'intval');
        if (empty($id)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //支付密码
        $password = RequestHelper::post('password', '', 'trim');
        if (empty($password)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //密码错误
        if (!$this->checkPayPwd($password)) {
            $this->returnJsonMsg('607', [], Common::C('code', '607'));
        }
        
        //根据id获取银行卡
        $info = UserBankcardNew::findOne(['id'=>$id,'mobile'=>$this->mobile]);
        if (empty($info)) {
            $this->returnJsonMsg('1103', [], Common::C('code', '1103'));
        }
        //删除
        $res = $info->delete();
        if (empty($res)) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
    
    //设置默认银行卡
    public function actionBankcardSetDefault() {
        //详情id
        $id= RequestHelper::post('id', 0, 'intval');
        if (empty($id)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        
        //根据id获取银行卡
        $info = UserBankcardNew::findOne(['id'=>$id,'mobile'=>$this->mobile]);
        if (empty($info)) {
            $this->returnJsonMsg('1103', [], Common::C('code', '1103'));
        }
        UserBankcardNew::updateAll(['is_default'=>0],['is_default'=>1,'mobile'=>$this->mobile]);
        
        //设置默认
        $info->is_default = 1;
        $res = $info->save();
        if (empty($res)) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
    
    //交易明细列表
    public function actionTradingList() {
        //分页
        $page = RequestHelper::post('page', 1, 'intval');
        //个数
        $limit = RequestHelper::post('limit', 20, 'intval');
        
        //获取我的交易明细
        $list_temp = UserTradingDetail::find()->select(['id','remarks','price','type','create_time'])
                ->where(['mobile'=>$this->mobile])->orderBy('create_time desc')
                ->offset(($page-1)*$limit)->limit($limit)
                ->asArray()->all();
        $list = [];
        foreach ($list_temp as $key=>$val) {
            $item = [];
            $item['id'] = $val['id'];
            //类型
            $type = !empty($this->trading_type_arr[$val['type']]) ? $this->trading_type_arr[$val['type']] : '其他';
            //标题(退回,消费,收益 明细)
            $item['title'] = in_array($val['type'], [1,4,5]) ? $type.' - '.$val['remarks'] : $type;
            //价格, 时间
            $item['price'] = $val['price'];
            $item['create_time'] = $val['create_time'];
            
            $list[$key] = $item;
        }
        $data['list'] = $list;
        
        $this->returnJsonMsg('200', [$data], Common::C('code', '200'));
    }
    
    //提现申请
    public function actionWithdrawal() {
        //银行卡id
        $id= RequestHelper::post('id', 0, 'intval');
        if (empty($id)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //支付密码
        $password = RequestHelper::post('password', '', 'trim');
        if (empty($password)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //密码错误
        if (!$this->checkPayPwd($password)) {
            $this->returnJsonMsg('607', [], Common::C('code', '607'));
        }
        //金额
        $price = RequestHelper::post('price', 0, 'floatval');
        $price = $this->returnPrice($price);
        if (empty($price)) {
            $this->returnJsonMsg('403', [], Common::C('code', '403'));
        }
        if ($price<100 || $price>5000) {
            $this->returnJsonMsg('2032', [], Common::C('code', '2032'));
        }
        //获取用户认证信息对象
        $UCinfo = UserCertification::findOne(['mobile'=>$this->mobile,'status'=>2]);
        if (empty($UCinfo)) {
            $this->returnJsonMsg('1061', [], Common::C('code', '1061'));
        }
        //余额不足
        if ($price > $UCinfo->change) {
            $this->returnJsonMsg('2030', [], Common::C('code', '2030'));
        }
        //提现总计
        $wmax = UserTradingWithdrawal::find()->where(['mobile'=>$this->mobile,'type'=>2])->sum('price');
        $wmax = floatval($wmax) ? floatval($wmax) : 0;
        if ($wmax >= $this->max_withdrawal) {
            //提现手续费,余额不足
            $price1 = abs($price) + round($this->fee_withdrawal*abs($price),2);
            if ($price1 > $UCinfo->change) {
                $this->returnJsonMsg('2035', [], Common::C('code', '2035'));
            }
        }
        //根据id获取银行卡
        $Bankinfo = UserBankcardNew::findOne(['id'=>$id,'mobile'=>$this->mobile]);
        if (empty($Bankinfo)) {
            $this->returnJsonMsg('1103', [], Common::C('code', '1103'));
        }
        //今日凌晨后是否有提现
        $dayTrading = UserTradingWithdrawal::find()->select('id')
                      ->where(['mobile'=>$this->mobile,'type'=>2,'pay_type'=>3])
                      ->andWhere(['>=','create_time',date('Y-m-d 00:00:00')])->asArray()->one();
        if (!empty($dayTrading)) {
            $this->returnJsonMsg('2033', [], Common::C('code', '2033'));
        }
        
        $this->setTransaction('db_social');
        //更新用户余额
        $res1 = $this->saveUserChange($this->mobile, -$price, 2);
        if (empty($res1)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        
        $idsn = $this->getIdsn('TC');
        $ytime = strtotime('+24 hours');
        //提现充值流程
        $model = new UserTradingWithdrawal();
        $model->idsn = $idsn;
        $model->uid = $this->uid;
        $model->mobile = $this->mobile;
        $model->type = 2;//1充值 2提现
        $model->pay_type = 3;//1支付宝 2微信 3银行卡
        $model->price = $price;
        $model->bank_number = $Bankinfo->bank_number;
        $model->bank_type = $Bankinfo->bank_type;
        $model->bank_belong = $Bankinfo->bank_belong;
        $model->expect_time = date('Y-m-d H:i:s', $ytime);//预计到账时间
        $model->status = 1;//1未完成 2已完成
        $res = $model->save();
        if (empty($res)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        
        $datalog = [
            'idsn' => $idsn,
            'type_txt' => '提现',
            'pay_type' => '银行卡',
            'bank_number' => $Bankinfo->bank_number,
        ];
        //添加交易明细
        $res3 = $this->addTradingDetail(2, -$price, '提现', $this->mobile, $idsn, $datalog);
        if (empty($res3)) {
            $this->backTransaction();
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->commitTransaction();
        
        $data = [
            'expect_time' => date('Y-m-d H:i', $ytime),
            'bank_type' => $Bankinfo->bank_type,
            'bank_belong' => $Bankinfo->bank_belong,
            'bank_number' => '尾号'.substr($Bankinfo->bank_number, -4),
            'price' => $price,
        ];
        
        //给财务发送邮件
        $to = 'xingliwen@i500m.com';
        $title = '用户提现通知';
        $body = "手机号为<b>{$this->mobile}</b>的用户，申请提现<b>{$price}</b>元，至{$Bankinfo->bank_belong}<b>{$Bankinfo->bank_number}</b>，具体以crm系统为准，请到crm系统查看！";
        $this->sendEmail($to, $title, $body);
        
        $this->returnJsonMsg('200', [$data], '提现申请已提交');
    }
}