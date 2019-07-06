<?php
/**
 * 我的特权
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/8/25
 */

namespace frontend\modules\v13\controllers;

use common\helpers\Common;
use frontend\models\i500_social\UserBasicInfo;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Identify;
use frontend\models\i500_social\User;

class MyPrivilegeController extends BasePrivilegeController {
    
    //有特权
    public function actionIsHavePrivilege() {
        $data['status'] = 1;
        $data['pioneer'] = 0;
        $data['tel'] = '400-8888-6666';
        //无特权 查询该用户所在小区建设者
        if (!$this->checkPrivilege()) {
            //根据小区查询建设者
            $pioneer_id = UserBasicInfo::find()->select(['i500_user.id'])
                          ->leftJoin('i500_user','i500_user.mobile=i500_user_basic_info.mobile')
                          ->where(['last_community_id'=>$this->community_id,'is_pioneer'=>1])->scalar();
            //建设者id
            $data['pioneer'] = empty($pioneer_id) ? 0 : (int)$pioneer_id;
            $data['status'] = 2;
        }
        $this->returnJsonMsg('200', [$data] , Common::C('code', '200'));
    }
    
    //开启特权
    public function actionOpenPrivilege() {
        //已有特权
        if ($this->checkPrivilege()) {
            $this->returnJsonMsg('200', [] , Common::C('code', '200'));
        }
        //邀请码
        $code = RequestHelper::post('code','','trim');
        if (empty($code)) {
            $this->returnJsonMsg('668',[],Common::C('code','668'));
        }
        //获取邀请码信息
        $userBasicInfo = new UserBasicInfo();
        $ident = Identify::find()->select(['community_id','expire_time','mobile'])
                 ->where(['num'=>$code,'status'=>1])
                 ->andWhere(['<>','progress',3])
                 ->asArray()
                 ->one();
        if(empty($ident)) {
            $this->returnJsonMsg('661', [], Common::C('code', '661'));
        }
        //有效期
        $valid_time = strtotime($ident['expire_time']);
        $current_time =  time();
        //非当前用户所在小区的邀请码
        if (empty($ident['mobile'])) {
            if ($ident['community_id'] != $this->community_id) {
                $this->returnJsonMsg('669', [], Common::C('code', '669'));
            }
        //非当前用户的邀请码
        } else if($ident['mobile'] != $this->mobile) {
            $this->returnJsonMsg('670', [], Common::C('code', '670'));
        }
        //认证码已过期
        if ($current_time > $valid_time) {
            $this->returnJsonMsg('660', [], Common::C('code', '660'));
        }
        //验证
        $resu = User::updateAll(['is_verification_code'=> 1],['mobile'=>$this->mobile]);
        //修改验证通过的
        if(empty($ident['mobile'])){
            $res = Identify::updateAll(['progress'=>3,'mobile'=>$this->mobile],['community_id'=>$this->community_id,'num'=>$code]);
        } else {
            $res = Identify::updateAll(['progress'=>3],['mobile'=>$this->mobile]);
        }
        //验证失败
        if(empty($resu) || empty($res)){
            $this->returnJsonMsg('400', [] , Common::C('code', '400'));
        }
        //创建收货地址
        $this->autoCreateAddress();
        
        $this->returnJsonMsg('200', [] , Common::C('code', '200'));
    }
}