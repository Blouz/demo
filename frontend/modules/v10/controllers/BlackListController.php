<?php

/* 
 * 黑名单管理
 * 
 * @category  Social
 * @package   Post
 * @author    huangdekui <huangdekui@i500m.com>
 * @time      2017
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      huangdekui@i500m.com
 */

namespace frontend\modules\v10\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use common\helpers\TxyunHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserFriends;

class BlackListController extends BaseController
{
	/**
	 * 黑名单列表
	 * 
	 * @param  string $mobile 手机号
	 * @return
	 */
	public function actionIndex(){
		$mobile = RequestHelper::post('mobile','','');
		if (empty($mobile)) {
			return $this->returnJsonMsg('604',[],Common::C('code','604'));
		}
		if (!Common::validateMobile($mobile)) {
			return $this->returnJsonMsg('605',[],Common::C('code','605'));
		}

		$res = UserFriends::getBlackList($mobile);

		return $this->returnJsonMsg('200',$res,Common::C('code','200'));
	}

	/**
	 * 添加和解除黑名单
	 *
	 * @param string $mobile 手机号
	 * @param string $fid 被拉黑人手机号
	 * @param string $user_id 被拉黑用户ID
	 * @return
	 */
	public function actionSetBlack(){
		$mobile = RequestHelper::post('mobile','','');
		if (empty($mobile)) {
			return $this->returnJsonMsg('604',[],Common::C('code','604'));
		}
		if (!Common::validateMobile($mobile)) {
			return $this->returnJsonMsg('605',[],Common::C('code','605'));
		}

        $fid = RequestHelper::post('fid','','');
        $user_id = RequestHelper::post('user_id', '', '');
        if (!empty($user_id)) {
            $fid = User::find()->select(['mobile'])->where(['id'=>$user_id])->scalar();
            if (empty($fid)) {
                return $this->returnJsonMsg('670',[],'参数不合法');
            }
        }

        $id = User::find()->select(['id'])->where(['mobile'=>$mobile])->scalar();
        $user_id = User::find()->select(['id'])->where(['mobile'=>$fid])->column();
        $black_type = RequestHelper::post('black_type','0','');//默认为添加黑名单
        $type = RequestHelper::post('type','0','');//0 ios 1 安卓
        if ($black_type) {
            $editBlackUser = TxyunHelper::editBlackUser($id,$user_id);
            $editBlackUser = json_decode($editBlackUser,true);
            if ( !empty( $editBlackUser ) && $editBlackUser['ActionStatus'] == 'OK' ) {
                $userFriends = new UserFriends();
                $userFriends->setUp($mobile, $fid, 5);
            }
        } else {
            //添加好友关系
            $data['To_Account'] = $user_id[0];
            $data['AddSource']  = 'AddSource_Type_IOS';
            if ($type == 1) {
                $data['AddSource']  = 'AddSource_Type_Android';
            }
            $addFriend = TxyunHelper::addFriend($id,$data);
            //加入黑名单
            $blackUser = TxyunHelper::blackUser($id,$user_id);
            $blackUser = json_decode($blackUser,true);

            if ( !empty( $blackUser ) && $blackUser['ActionStatus'] == 'OK') {
                $userFriends = new UserFriends();
                $userFriends->setUp($mobile, $fid, 4);
            }
        }
		return $this->returnJsonMsg('200',[],Common::C('code','200'));
	}
}