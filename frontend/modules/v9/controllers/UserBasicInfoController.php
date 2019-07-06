<?php
/**
 * 用户加入社区相关接口
 *
 * PHP Version 8
 *
 * @category  Social
 * @package   Service
 * @author    yaoxin <yaoxin@i500m.com>
 * @time      2017/02/15
 * @copyright 2016 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      yaoxin@i500m.com
 */
namespace frontend\modules\v9\controllers;

use Yii;
use yii\db\Query;
use yii\data\Pagination;
use common\helpers\Common;
use yii\helpers\ArrayHelper;
use common\helpers\RequestHelper;
use frontend\models\i500m\Community;
use frontend\models\i500_social\Logincommunity;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\VerificationCode;
use frontend\models\i500_social\Identify;
use frontend\models\i500_social\User;

class UserBasicInfoController extends BaseController
{
    /**
     * 注册时添加用户信息
     * return array()
    **/
    public function actionIndex()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if(empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if(!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $userinfo = array();
        $realname = RequestHelper::post('realname', '', '');
        if(!empty($realname)) {
            $userinfo[] = 'nickname'.'='.'"'.$realname.'"';
            $userinfo[] = 'realname'.'='.'"'.$realname.'"';
        }
        $community_province_id = RequestHelper::post('community_province_id', '', '');
        $community_city_id = RequestHelper::post('community_city_id', '', '');
        $community_area_id = RequestHelper::post('community_area_id', '', '');

        $community_id = RequestHelper::post('community_id', '', '');
        if(!empty($community_id)) {
            $userinfo[] = 'last_community_id'.'='.'"'.$community_id.'"';
        }
        $address = RequestHelper::post('address', '', '');
        if(!empty($address)) {
            $userinfo[] = 'address'.'='.'"'.$address.'"';
        }
        $num = RequestHelper::post('num', '', '');
        
        //将用户填写的信息插入到userbasicinfo表中
        if(!empty($realname) || !empty($community_id) || !empty($address)) {
            if(!empty($community_id)) {
                $pcd = Community::find()->select(['province', 'city', 'district'])->where(['id' => $community_id])->asArray()->one();
                $userinfo[] = 'province_id'.'='.'"'.$pcd['province'].'"';
                $userinfo[] = 'city_id'.'='.'"'.$pcd['city'].'"';
                $userinfo[] = 'district_id'.'='.'"'.$pcd['district'].'"';
                /*$pioneer = UserBasicInfo::find()->select(['id'])->where(['last_community_id' => $community_id])->asArray()->count();
                $pion_mobile = UserBasicInfo::find()->select(['mobile'])->where(['last_community_id' => $community_id, 'is_pioneer' => '1'])->asArray()->one();
                if($pioneer == 0) {
                    $res = UserBasicInfo::updateAll(['is_pioneer'=> '1'],['mobile'=>$mobile]);
                }elseif($mobile != $pion_mobile['mobile']){
                    $res = UserBasicInfo::updateAll(['is_pioneer'=> '0'],['mobile'=>$mobile]);
                }*/
            }
            $info = implode(',', $userinfo);
            $user_model = User::find()->where(['mobile'=>$mobile])->asArray()->one();
            if(!empty($user_model)) {
                $ress = UserBasicInfo::find()->where(['mobile'=>$mobile])->asArray()->one();
                if(!empty($ress)){
                    $sql = "UPDATE i500_user_basic_info SET ".$info." WHERE mobile = '$mobile'";
                    $res = \Yii::$app->db_social->createCommand($sql)->execute();
                }else{
                    $user_base_model = new UserBasicInfo();
                    $user_base_data['mobile'] = $mobile;
                    $user_base_model->insertInfo($user_base_data);

                    $sql = "UPDATE i500_user_basic_info SET ".$info." WHERE mobile = '$mobile'";
                    $res = \Yii::$app->db_social->createCommand($sql)->execute();
                }
            }else {
                $this->returnJsonMsg('400', [], Common::C('code', '400'));
            }
            if(!empty($community_id)) {
                $tim = UserBasicInfo::updateAll(['update_time'=> date('Y-m-d H:i:s', time())],['mobile'=>$mobile]);
                $user_id = UserBasicInfo::find()->select(['id', 'join_in', 'update_time'])->where(['mobile' => $mobile])->asArray()->one();
                $join_in = UserBasicInfo::find()->where(['<=', 'create_time', $user_id['update_time']])->andWhere(['last_community_id'=> $community_id])->asArray()->count();
                $res = UserBasicInfo::updateAll(['join_in'=> $join_in],['mobile'=>$mobile]);
            }
        }
        //认证码验证
//        if(!empty($num)) {
//            $ident = Identify::find()->select(['expire_time'])->where(['mobile'=> $mobile, 'num'=>$num])->asArray()->one();
//            $valid_time = strtotime($ident['expire_time']);
//            if(empty($ident)) {
//                $this->returnJsonMsg('661', [], Common::C('code', '661'));
//            }else{
//                if ($valid_time < time()) {
//                    $this->returnJsonMsg('660', [], Common::C('code', '660'));
//                }else{
//                    $resu = UserBasicInfo::updateAll(['is_verify'=> 1],['mobile'=>$mobile]);
//                }
//            }
//        }

        if(!empty($num))
        {
            //获取用户所在小区
            $userBasicInfo = new UserBasicInfo();
            $user_community_id = $userBasicInfo->getInfo(['mobile'=>$mobile],true,['last_community_id']);
            $ident = Identify::find()->select(['community_id','expire_time','mobile'])
//                     ->where(['mobile'=> $this->mobile, 'num'=>$this->num])
                ->where(['num'=>$num,'status'=>1])
                ->andWhere(['<>','progress',3])
                ->asArray()
                ->one();

            $valid_time =date("Y-m-d H:i:s",strtotime($ident['expire_time']));
            $current_time =  date("Y-m-d H:i:s", time());

            if(empty($ident))
            {
                $this->returnJsonMsg('661', [], Common::C('code', '661'));
            }
            else
            {
                if (empty($ident['mobile'])) {
                    if($ident['community_id'] !== $user_community_id['last_community_id']){
                        $this->returnJsonMsg('661', [], Common::C('code', '661'));
                    }
                } else if($ident['mobile'] !== $mobile) {
                    $this->returnJsonMsg('661', [], Common::C('code', '661'));
                }

                if ($current_time > $valid_time) {
                    $this->returnJsonMsg('660', [], Common::C('code', '660'));
                } else {
                    //验证
                    UserBasicInfo::updateAll(['is_verify'=> 1],['mobile'=>$mobile]);
                    User::updateAll(['is_verification_code'=> 1],['mobile'=>$mobile]);
                    //修改验证通过的
                    if(empty($ident['mobile'])){
                        Identify::updateAll(['progress'=>3,'mobile'=>$mobile],['community_id'=>$user_community_id['last_community_id'],'num'=>$num]);
                    } else {
                        Identify::updateAll(['progress'=>3],['mobile'=>$mobile]);
                    }
                }
            }
        } else {
            $this->returnJsonMsg('608', [], Common::C('code', '608'));
        }

        //$step = User::find()->select(['step'])->where(['mobile'=>$mobile])->asArray()->one();
        $uid = $mobile;
        $cont = $this->_editstep($uid);
        //判断该用户填写信息到哪步
        //$this->returnJsonMsg('200', $cont, Common::C('code', '200'));
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
    public function _editstep($uid = '')
    {
        $veri = VerificationCode::find()->select(['open_invitation_code', 'open_relation_community'])->asArray()->one();  
        //查询该用户详细信息
        $res = UserBasicInfo::find()->select(['nickname', 'realname','city_id', 'province_id', 'district_id','last_community_id', 'address', 'is_verify'])->where(['mobile'=> $uid])->asArray()->one();
        
        $step = User::find()->select(['is_verification_code'])->where(['mobile'=>$uid])->asArray()->one();
        if($veri['open_relation_community'] == 1) {
            if(!empty($res['nickname']) && !empty($res['realname'])) {
                $resu = User::updateAll(['step'=> 2],['mobile'=>$uid]);
            }else{
                $resu = User::updateAll(['step'=> 1],['mobile'=>$uid]);
                $step = User::find()->select(['step'])->where(['mobile'=>$uid])->asArray()->one();
                return $step;
            }
            if($res['province_id'] != 0 && $res['city_id'] != 0 && $res['district_id'] != 0 && $res['last_community_id'] != 0) {
                $resu = User::updateAll(['step'=> 4],['mobile'=>$uid]);
            }else {
                $step = User::find()->select(['step'])->where(['mobile'=>$uid])->asArray()->one();
                return $step;
            }
            if(!empty($res['address'])) {
                $value = 'addr';
                $mobile = $uid;
                $ident = $this->_addident($value, $mobile);
                $resu = User::updateAll(['step'=> 5],['mobile'=>$uid]);
            }else{
                $step = User::find()->select(['step'])->where(['mobile'=>$uid])->asArray()->one();
                return $step;
            }
            if($res['is_verify'] == '1') {
                $resu = User::updateAll(['step'=> 6],['mobile'=>$uid]);
            }else{
                $step = User::find()->select(['step'])->where(['mobile'=>$uid])->asArray()->one();
                return $step;
            }
            $step = User::find()->select(['step'])->where(['mobile'=>$uid])->asArray()->one();
            return $step;
        }else{
            if(!empty($res['nickname']) && !empty($res['realname'])) {
                $resu = User::updateAll(['step'=> 4],['mobile'=>$uid]);
            }else{
                $resu = User::updateAll(['step'=> 1],['mobile'=>$uid]);
                $step = User::find()->select(['step'])->where(['mobile'=>$uid])->asArray()->one();
                return $step;
            }
            if($veri['open_relation_community'] == 0) {
                $resu = User::updateAll(['step'=> 4],['mobile'=>$uid]);
            }
            if(!empty($res['address'])) {
                $value = 'addr';
                $mobile = $uid;
                $ident = $this->_addident($value, $mobile);
                $resu = User::updateAll(['step'=> 5],['mobile'=>$uid]);
            }else{
                $step = User::find()->select(['step'])->where(['mobile'=>$uid])->asArray()->one();
                return $step;
            }
            if($res['is_verify'] == '1') {
                $resu = User::updateAll(['step'=> 6],['mobile'=>$uid]);
            }else{
                $step = User::find()->select(['step'])->where(['mobile'=>$uid])->asArray()->one();
                return $step;
            }
            $step = User::find()->select(['step'])->where(['mobile'=>$uid])->asArray()->one();
            return $step;
        }
    }
}

?>