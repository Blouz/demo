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
namespace frontend\modules\v6\controllers;

use common\helpers\RequestHelper;
use common\helpers\Common;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\UserFriends;

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
     * 获取用户信息
     * @return array
     */
    public function actionUserInfo()
    {   
        $user_mobile = RequestHelper::post('user_mobile', '', '');
        if (empty($user_mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($user_mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $userinfo = UserBasicInfo::find()->select(['nickname','avatar','sex','personal_sign','backimg'])->where(['mobile'=>$user_mobile])->asArray()->one();
        $userinfo['is_friend'] = "0";
        $mobile = RequestHelper::post('mobile', '', '');
        if (!empty($mobile) && ($user_mobile != $mobile)) {
            $friend = UserFriends::find()->select(['id'])->where(['uid'=>$mobile,'fid'=>$user_mobile,'status'=>1])->one();
            if (!empty($friend)) {
                $userinfo['is_friend'] = "1";
            }
        }

        $this->returnJsonMsg(200, $userinfo, 'SUCCESS');
    }

    /**
     * 获取当前小区邻居
     * @return array
     */
    public function actionUserlistForCommunity()
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
        $user_list = UserBasicInfo::find()->select(['mobile', 'nickname', 'avatar','sex','personal_sign'])->where(['last_community_id'=>$community_id])->with(['user'=>function($query) {
            $query->select(['mobile'])->where(['status'=>'2','is_deleted'=>2]);
        }])->asArray()->all();

        $data = [];
        if (!empty($user_list)) {
            if (!empty($mobile)) {
                foreach ($user_list as $k=>$v) {
                    if ($v['mobile'] != $mobile) {
                        $data[] = $v;
                    }
                }
            } else {
                $data = $user_list;
            }
        }
        unset($user_list);
        $this->returnJsonMsg('200', $data, Common::C('code', '200'));
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

        $ret_date = array(
            'friend' => array(), 
            'community' => array(),
            );

        $community_query = UserBasicInfo::find()->select(['mobile', 'nickname', 'avatar','sex','personal_sign'])->where(['last_community_id'=>$community_id]);

        if(!empty($mobile)){
            $community_query = $community_query->andWhere(['<>' , 'mobile' , $mobile])->with(['friend'=>function($query) {
                                        $query->select(['uid','status'])->where(['status'=>'0']);
                                    }]);
        }
        $community_query = $community_query->with(['user'=>function($query) {
                                $query->select(['mobile'])->where(['status'=>'2','is_deleted'=>2]);
                            }]);


        $community_list = $community_query->asArray()->all();
        if(empty($mobile)){
            //数组添加friend key
            array_walk($community_list, array($this, '_addfriendkey'));
        }else{
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
                    $user_info[$key]['status'] = '0';
                }

                $apply_userlist = $user_info;
            }

            $user_list = UserFriends::find()->select(['fid','status'])->where(['uid'=>$mobile, 'status'=>1])->asArray()->all();
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
                    $user_info[$key]['status'] = '1';
                }

                $friend_userlist = $user_info;
            }
            $ret_date['friend'] = array_merge($apply_userlist,$friend_userlist);
        }
        $ret_date['community'] = $community_list;
        $this->returnJsonMsg('200', $ret_date, Common::C('code', '200'));
    }

    private function _addfriendkey(&$val, $key)
    {
        $val['friend'] = null;
    }
}
