<?php
/**
 * 一行的文件介绍
 *
 * PHP Version 5
 * 可写多行的文件相关说明
 *
 * @category  I500M
 * @package   Member
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      16/8/9
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */
namespace frontend\modules\v7\controllers;

use common\helpers\RequestHelper;
use common\helpers\Common;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\UserFriends;
use frontend\models\i500_social\User;

class UserController extends BaseController
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
     * 获取好友列表
     * @return array
     */
    public function actionUserlistForFriend()
    {  
        $community_id = RequestHelper::post('community_id', 0, 'intval');
        if (empty($community_id)) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        $mobile = RequestHelper::post('mobile', '', '');
        if (!empty($mobile)) {
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }
		
		//查询用户step
		$step = User::find()->select(['step'])->where(['mobile'=>$mobile])->asArray()->all();
		foreach($step as $k =>$v){
			if($v['step'] != 8){
				$this->returnJsonMsg('606',"", '您还未认证');
			}
		}

        $ret_date = array(
            'friend' => array(), 
            'community' => array(),
            );

        $community_query = UserBasicInfo::find()->select(['mobile', 'nickname', 'avatar','sex','personal_sign'])
												->where(['last_community_id'=>$community_id])
												->with(['user'=>function($query) {
													$query->select(['mobile'])->where(['status'=>'2','is_deleted'=>2]);
												}]);
        $friend_list = array();
        $apply_users = array();
        if(!empty($mobile)){
            $community_query = $community_query -> andWhere(['<>','mobile', $mobile])->with(['friend'=>function($query) {
                                        $query->select(['uid'])->where(['status'=>'1']);
                                    }]);
            $is_apply_userlist = UserFriends::find()->select(['fid','status'])->where(['uid'=>$mobile,'status'=>array(0,1)])->asArray()->all();
            foreach ($is_apply_userlist as $key => $value) {
                if($value['status'] == 1){
                    $friend_list[] = $value['fid'];
                }else{
                    $apply_users[] = $value['fid'];
                }
            }
        }

        $community_list = $community_query ->asArray()->all();
        $tmp_list = [];
        foreach ($community_list as $key => $value) {
            if(!in_array($community_list[$key]['mobile'], $friend_list)) {
                $community_list[$key]['is_apply'] = 0;
                if(empty($community_list[$key]['friend'])){
                    if(in_array($community_list[$key]['mobile'], $apply_users)) {
                        $community_list[$key]['is_apply'] = 1;
                    }
                }
                $tmp_list[] = $community_list[$key];
            }
        }
        $community_list = $tmp_list;

        if(!empty($mobile)){
            $apply_friend_list = UserFriends::find()->select(['uid','status'])->where(['fid'=>$mobile,'status'=>0])->asArray()->all();
            $apply_userlist = [];
            $friend_userlist = [];

            if(!empty($apply_friend_list)) {
                foreach ($apply_friend_list as $k => $v) {
                    $user_ids[] = $v['uid'];
                }
                $user_info = UserBasicInfo::find()
                    ->select(['mobile','nickname','avatar','sex','personal_sign'])
                    ->where(['mobile'=>$user_ids])
                    ->with(['user'=>function($query) {
                        $query->select(['mobile'])->where(['status'=>'2','is_deleted'=>2]);
                    }])->asArray()->all();

                foreach ($user_info as $key => $value) {
                    $user_info[$key]['is_apply'] = '1';
                }

                $apply_userlist = $user_info;
            }

            $user_list = UserFriends::find()->select(['fid'])->where(['uid'=>$mobile, 'status'=>1])->asArray()->all();
            if(!empty($user_list)) {
                foreach ($user_list as $k => $v) {
                    $user_ids[] = $v['fid'];
                }
				
                $user_info = UserBasicInfo::find()
                    ->select(['mobile','nickname','avatar','sex','personal_sign'])
                    ->where(['mobile'=>$user_ids])
                    ->with(['user'=>function($query) {
                        $query->select(['mobile'])->where(['status'=>'2','is_deleted'=>2]);
                    }])->asArray()->all();

                foreach ($user_info as $key => $value) {
                    $user_info[$key]['is_apply'] = '0';
                }

                $friend_userlist = $user_info;
            }
            $ret_date['friend'] = array_merge($apply_userlist,$friend_userlist);
        }
        $ret_date['community'] = $community_list;
        $this->returnJsonMsg('200', $ret_date, Common::C('code', '200'));
    }
}
