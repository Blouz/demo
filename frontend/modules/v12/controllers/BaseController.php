<?php
/**
 * v12 主接口基类
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/6/21
 */

namespace frontend\modules\v12\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use common\helpers\CurlHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserSms;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserCertification;
use frontend\models\i500_social\UserTradingWithdrawal;
use frontend\models\i500_social\UserTradingDetail;
use frontend\models\i500m\Community;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii;
use frontend\models\i500_log\ApiLog;


class BaseController extends Controller {
    public $enableCsrfValidation = false;
    protected $params = null;
    public $uid = 0;//用户userid
    public $mobile = 0;//当前用户手机号
    public $community_id=0;//小区id
    public $district_id=0;//行政区id
    public $city_id=0;//市区id
    public $province_id=0;//省份id
    public $communitygroups_id = 0;//社区id
    public $token = 0;
    public $result = ['code'=>200, 'data'=>[], 'message'=>'OK'];
    public $pageSize = 20;
    //sqlite日志id（存储返回值用）
    public $log_lastid = 0;
    //无需登录的控制器
    protected $nologin = ['login', 'pay-notify']; 
    //事务回滚
    public $transaction = null;
    //交易明细类型
    public $trading_type_arr = array(
        0=>'其他', 1=>'退回', 2=>'提现', 3=>'充值', 
        4=>'消费', 5=>'收益', 6=>'手续费'
    );
    
    /**
     * 初始化
     * @return array
     */
    public function init() {
        parent::init();
        //获取请求类型
        $method = RequestHelper::getMethod();
        switch ($method) {
            case 'POST':
                $this->params = RequestHelper::post();
                break;
            case 'PUT' :
                $this->params = RequestHelper::put();
                break;
            default :
                $this->params = RequestHelper::get();
                break;
        }
        $this->saveLog(Common::C('returnLogFile'), $this->params);
        
        //获取当前访问controller名称
        if(!in_array($this->id, $this->nologin)){
            //未获取到mobile或token
            if (!isset($this->params['mobile']) || !isset($this->params['token'])){
                $this->returnJsonMsg(403, [], Common::C('code', '403'));
            }
            $this->mobile = $this->params['mobile'];
            $this->token = $this->params['token'];
            //mobile或token为空
            if (empty($this->mobile) || !Common::validateMobile($this->mobile) || empty($this->token)){
                $this->returnJsonMsg(403, [], Common::C('code', '403'));
            }
            //验证token 是否正确
            $user_status = $this->_checkToken();
            if($user_status != 200) {
                $this->returnJsonMsg($user_status, [], Common::C('code', $user_status));
            }
        }
    }
    
    /**
     * 检查Token
     * @return int
     */
    private function _checkToken() {
        $user_model = new User();
        $user_where['mobile'] = $this->mobile;
        $user_where['is_deleted'] = '2';
        $user_fields = 'id,status,token,expired_in';
        $user_info = $user_model->getInfo($user_where, true, $user_fields);
        //状态码
        $code = 200;
        if (empty($user_info)) {
            $code = 508;
        } else if($user_info['status'] != 2){
            $code = 508;
        } else if($this->token != $user_info['token']){
            $code = 508;
        } else if (time() > $user_info['expired_in']) {
            $code = 508;
        } else {
            $this->uid = $user_info['id'];
            //省市区小区
            $basic = UserBasicInfo::find()->select(['last_community_id','province_id','city_id','district_id'])->where(['mobile'=>$this->mobile])->asArray()->one();
            if (!empty($basic)) {
                $this->community_id = $basic['last_community_id'];
                $this->province_id = $basic['province_id'];
                $this->city_id = $basic['city_id'];
                $this->district_id = $basic['district_id'];
                //社区id
                $g_id = Community::find()->select(['communitygroups'])->where(['id'=>$this->community_id])->asArray()->scalar();
                $this->communitygroups_id = empty($g_id)?'0':$g_id;
            }
        }
        return $code;
    }
    
    /**
     * 返回JSON格式的数据
     * @param string $code    错误代码
     * @param array  $data    数据
     * @param string $message 错误说明
     * @return array
     */
    public function returnJsonMsg($code='', $data=array(), $message='') {
        $arr = array(
            'code' => (string)$code,
            'data' => $data,
            'message' => $message,
        );
        $ret = json_encode($arr);
        $ret_str = str_replace('(null)', '', $ret);      //出现在数据库中
        $ret_str = str_replace('null', 'null', $ret_str);  //出现在返回值中
        //更新SQLite日志
        if (Common::C('openSQLiteLog')) {
            (new ApiLog())->updateLog($ret_str, $this->log_lastid);
        }
        die($ret_str);
    }
    
    /**
     * 发送短信通道
     * @param string $mobile  手机号
     * @param string $content 短信内容
     * @return array
     */
    public function sendSmsChannel($mobile = '', $content = '') {
        $arr['mobile']  = $mobile;
        $arr['content'] = $content;
        //保存短信信息
        if (Common::C('saveSms')) {
            $user_sms_model = new UserSms();
            $rs = $user_sms_model->insertInfo($arr);
            if (empty($rs)) {
                $this->returnJsonMsg('500', [], '信息保存失败');
            }
        }
        //发送短信
        if (Common::C('openSmsChannel')) {
            $url = Common::C('channelHost').'sms/get-add';
            $rs = CurlHelper::post($url, $arr, true);
            if ($rs['code']!='200') {
                $this->returnJsonMsg('611', [], Common::C('code', '611'));
            }
        }
        return true;
    }
    
    /**
     * 推送消息(请求信鸽推送)
     * @param string $mobile 手机号
     * @param int $type 1=帖子点赞 2=评论点赞 3=评论帖子 4=别人查看自己的主页  ，
     *                  5-访客，6-加好友，7-邻居圈点赞，8-评价，9-活动，10积分等级提升，11-点亮小区,12-加入家庭组，
     *                  14-设置管理员，15-家庭组咪一下，16-活动，20-抓猫鼬，21需求订单推送，22服务订单推送
     *                  大于200-评价 （更多请见channel）
     * @param string $title 标题
     * @param string $description 内容
     * @param array $auto_data 自定义参数
     * @return bool
     */
    public function pushToApp($mobile='', $type=0, $title='', $description='', $auto_data=array()) {
        file_put_contents('/tmp/xg_push_log.log',  "执行时间：".date('Y-m-d H:i:s')." 推送开始\n", FILE_APPEND);
        //未开启推送功能
        if (!Common::C('OpenPushToApp')) {   
            return false;
        }
        file_put_contents('/tmp/xg_push_log.log',  "执行时间：".date('Y-m-d H:i:s')." 判断参数\n", FILE_APPEND);
        //参数为空
        if (empty($mobile) || empty($type) || empty($title) || !is_array($auto_data)) {
            return false;
        }
        //自定义参数，由'auto_'为前缀，传输但在客户端不带'auto_'
        foreach ($auto_data as $key=>$val) {
            unset($auto_data[$key]);
            $auto_data["auto_{$key}"] = $val;
        }
        file_put_contents('/tmp/xg_push_log.log',  "执行时间：".date('Y-m-d H:i:s')." 用户是否开启推送\n", FILE_APPEND);
        /**通过手机号获取是否开通推送**/
        $user_base_model = new UserBasicInfo();
        $user_base_where['mobile'] = $mobile;
        $user_base_fields = 'id,push_status';
        $user_base_info = $user_base_model->getInfo($user_base_where, true, $user_base_fields);
        if (empty($user_base_info) || $user_base_info['push_status'] != '1') {
            return false;
        }
        file_put_contents('/tmp/xg_push_log.log',  "执行时间：".date('Y-m-d H:i:s')." 推送channel_id是否存在\n", FILE_APPEND);
        //获取信鸽id
        $xg_channel_id = User::find()->select('xg_channel_id')->where(['mobile'=>$mobile])->scalar();
        if (empty($xg_channel_id)) {
            return false;
        }
        /**已经开启了推送服务**/
        $xg_channel_id = explode('-', $xg_channel_id);
        $data['device_type'] = ArrayHelper::getValue($xg_channel_id, 0);
        $data['channel_id'] = ArrayHelper::getValue($xg_channel_id, 1);
        $data['type'] = $type;
        $data['title'] = $title;
        $data['description'] = $description;
        $data = array_merge($data,$auto_data);
        $channel_url = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
        file_put_contents('/tmp/xg_push_log.log',  "执行时间：".date('Y-m-d H:i:s')." 推送参数:".var_export($data, true)."\n", FILE_APPEND);
        $re = CurlHelper::post($channel_url, $data);
        file_put_contents('/tmp/xg_push_log.log',  "执行时间：".date('Y-m-d H:i:s')." 推送是否成功:".var_export($re, true)."\n", FILE_APPEND);
        //推送失败
        if (empty($re)) {
            return false;
        }
        return true;
    }
    
    /**
     * 开启日志
     * @param string $path 路径
     * @param string $data 数据
     * @return bool
     */
    public function saveLog($path = '', $data = '') {
        //创建SQLite日志
        if (Common::C('openSQLiteLog')) {
            $this->log_lastid = (new ApiLog())->insertLog($data);
        }
    }
    
    /**
     * 设置事务
     */
    public function setTransaction($db='db_social') {
        if (empty($db)) return false;
        $this->transaction = \Yii::$app->$db->beginTransaction();
    }
    
    /**
     * 回滚事务
     */
    public function backTransaction() {
        if (empty($this->transaction)) return false;
        $this->transaction->rollBack();
    }
    
    /**
     * 执行事务
     */
    public function commitTransaction() {
        if (empty($this->transaction)) return false;
        $this->transaction->commit();
    }
    
    /**
     * 获取随机编码
     * @param string $prefix 前缀字符串
     * @author wyy
     * @return string
     */
    public function getIdsn($prefix='') {
        return $prefix . date('YmdHis') . substr(time(),-5) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * 验证支付密码是否正确
     * @param string 密码md5加密后
     * @author liuyanwei
     * @return boolean
     */
    public function checkPayPwd($password){
        $info = UserCertification::find()->select(['id','paysalt','paypwd','pwderror_num','pwderror_dtime'])
                ->where(['mobile'=>$this->mobile])
                ->asArray()->one();
        if(empty($info)){
            $this->returnJsonMsg('2106', [], Common::C('code', '2106'));
        }
        //未设置支付密码
        if (empty($info['paypwd'])) {
            $this->returnJsonMsg('2105', [], Common::C('code', '2105'));
        }
        //密码错误超过3次，且未解除时间
        if($info['pwderror_num']>=3 && $info['pwderror_dtime']>=date('Y-m-d H:i:s')) {
            $this->returnJsonMsg('2104', [], Common::C('code', '2104'));
        }
        //密码正确
        if(md5($password.$info['paysalt']) == $info['paypwd']){
            UserCertification::updateAll(['pwderror_num'=>0],['id'=>$info['id']]);
            return true;
        }
        
        $data = [];
        $data['pwderror_num'] = ($info['pwderror_num']>=3) ? 1 : ($info['pwderror_num']+1);
        //累计已达3次
        if ($data['pwderror_num']==3) {
            $data['pwderror_dtime'] = date('Y-m-d H:i:s', strtotime('+90 minutes'));
        }
        UserCertification::updateAll($data,['id'=>$info['id']]);
        //错误次数
        switch ($data['pwderror_num']) {
            case 1:
                $this->returnJsonMsg('2101', [], Common::C('code', '2101'));
                break;
            case 2:
                $this->returnJsonMsg('2102', [], Common::C('code', '2102'));
                break;
            default:
                $this->returnJsonMsg('2103', [], Common::C('code', '2103'));
                break;
        }
    }

    /**
     * 提现发送邮件
     * @param string $mobile 手机号
     * @return bool
     */
    public function sendEmail($to,$title,$body) {
        //未开启发送邮件功能
        if (!Common::C('OpenSendToEmail')) {   
            return false;
        }
        $data['to'] = $to;
        $data['title'] = $title;
        $data['body'] = $body;
        $channel_url = \Yii::$app->params['channelHost'] . 'v1/email/send';
        $re = CurlHelper::post($channel_url, $data);
        //发送失败
        if (empty($re)) {
            return false;
        }
        return true;
    }
    
    //查询余额
    public function getUserAccount() {
        $change = UserCertification::find()->select(['change'])->where(['mobile'=>$this->mobile])->scalar();
        //余额
        return empty($change) ? '0.00' : $change;
    }
    
    //检查并处理金额， 如需验证负数先用abs($price)处理
    public function returnPrice($price=0) {
        if (empty($price)) {
            return 0;
        }
        $price = sprintf("%.2f",$price);
        $price = floatval($price);
        if (!$price || $price<0 || $price>9999999999.99) {
            return 0;
        }
        return $price;
    }
    
    /**
     * 生成交易明细
     * @param int $type 交易类型
     * @param float $total 交易金额
     * @param string $remarks 交易内容
     * @param string $mobile 用户手机号
     * @param string $idsn 交易编号
     * @param array $data 交易信息
     * @return boolean
     */
    public function addTradingDetail($type,$total,$remarks='',$mobile='',$idsn='',$data=array()) {
        $type = intval($type);
        $total = floatval($total);
        $data = is_array($data) ? $data : array();
        //数据错误
        if (!in_array($type, array_keys($this->trading_type_arr)) || empty($total)) {
            return false;
        }
        if (!empty($mobile)) {
            $uid = User::find()->select(['id'])->where(['mobile'=>$mobile])->scalar();
        } else {
            $uid = $this->uid;
            $mobile = $this->mobile;
        }
        $data['idsn'] = $idsn;
        //交易明细
        $model = new UserTradingDetail();
        $model->uid = $uid;
        $model->mobile = $mobile;
        $model->remarks = $remarks;//内容
        $model->price = $total;//金额
        $model->type = $type;
        $model->data = serialize($data);//交易信息
        return $model->save();
    }
    
    /**
     * 更新用户余额
     * @param string $mobile 用户手机号
     * @param float $price 改变的金额
     * @param int $type 手续费类型 1收益 2消费
     */
    public function saveUserChange($mobile, $price, $type=0) {
        //获取用户认证信息对象
        $UCinfo = UserCertification::findOne(['mobile'=>$mobile]);
        if (empty($UCinfo)) {
            //用户id
            $user_model = new User();
            $user_info = $user_model->getInfo(['mobile'=>$mobile], true, 'id');
            //创建一个用户认证记录
            $UCinfo = new UserCertification();
            $UCinfo->uid = empty($user_info['id'])?0:$user_info['id'];
            $UCinfo->mobile = $mobile;
            $UCinfo->change = 0;
            $UCinfo->status = 1;
            $res = $UCinfo->save();
            if (empty($res)) {
                return false;
            }
        }
        $price = sprintf("%.2f",$price);
        $price = floatval($price);
        //合法且为负数时绝对值不大于当前余额
        if (empty($price) || ($price<0 && abs($price)>$UCinfo->change)) {
            return false;
        }
        switch ($type) {
            case 1:
                //供需收益手续费
                $fee_price = round($this->fee_earnings*abs($price),2);
                $price = $price - $fee_price;
                //添加交易明细
                $this->addTradingDetail(6, -$fee_price, '收益手续费'.($this->fee_earnings*100).'%',$mobile);
                break;
            case 2:
                //已提现总额
                $wmax = UserTradingWithdrawal::find()->where(['mobile'=>$mobile,'type'=>2])->sum('price');
                $wmax = floatval($wmax) ? floatval($wmax) : 0;
                //超出免提现费用
                if ($wmax >= $this->max_withdrawal) {
                    //提现手续费
                    $fee_price = round($this->fee_withdrawal*abs($price),2);
                    $price = $price - $fee_price;
                    if (abs($price)>$UCinfo->change) {
                        return false;
                    }
                    //添加交易明细
                    $this->addTradingDetail(6, -$fee_price, '提现手续费'.($this->fee_withdrawal*100).'%',$mobile);
                }
                break;
        }
        //更新用户余额
        $UCinfo->change += $price;
        return $UCinfo->save();
    }
    
    /**
     * 剩余时间处理
     * @param string $datetime 时间 ，2017-06-27 18:00:00
     * @return string 6天23时35分
     */
    public function NformatTime($datetime='') {
        $rest = '';
        $datetime = strtotime($datetime);
        //时间错误或小于当前时间
        if (empty($datetime) || $datetime<=time()) {
            return $rest;
        }
        $diff = $datetime - time();
        //天
        $day = floor($diff / 86400);
        if ($day > 0) {
            $rest .= $day . '天';
        }
        $free1 = $diff % 86400;
        if ($free1 <= 0) {
            return $rest;
        }
        //时
        $hour = floor($free1 / 3600);
        if ($hour > 0) {
            $rest .= $hour . '时';
        }
        $free2 = $free1 % 3600;
        if ($free2 <= 0) {
            return $rest;
        }
        //分
        $min = floor($free2 / 60);
        if ($min > 0) {
            $rest .= $min . '分';
        }
        $free3 = $free2 % 60;
        if ($free3 <= 0) {
            return $rest;
        }
        
        return $rest;
    }

    /**
     * 欺诈信息验证
     * @author wyy
     * @param string $cert_no 证件号
     * @param string $name 姓名
     * @param string $bank_card 银行卡号
     * @param string $mobile 预留手机号
     * @return boolean
     */
    public function credit_antifraud_verify($user_card, $realname, $bank_card='', $mobile='') {
        $res = false;
        include(dirname(__FILE__) . "/../../../../common/vendor/alipayzhima/AopSdk.php");
        if(function_exists('zhima_credit_antifraud_verify')){
            //欺诈信息验证
            $res = zhima_credit_antifraud_verify($user_card, $realname, $bank_card, $mobile);
        }
        return $res;
    }

    /**
     * 是否实名认证
     * @author wyy
     * @return boolean
     */
    public function checkUserCert(){
        $ret = false;
        $usercert = UserCertification::find()->select(['id'])->where(['mobile'=>$this->mobile,'status'=>2])->asArray()->one();
        if(!empty($usercert)){
            $ret = true;
        }
        return $ret;
    }
}
