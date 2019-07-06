<?php
/**
 * 一行的文件介绍
 *
 * PHP Version 5
 * 可写多行的文件相关说明
 *
 * @category  I500M
 * @package   Member
 * @author    xuxiaoyu <xuxiaoyu@i500m.com>
 * @time      16/10/13
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      xuxiaoyu@i500m.com
 */
namespace frontend\modules\v8\controllers;

use common\helpers\RequestHelper;
use common\helpers\Common;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserFriends;
use frontend\models\i500_social\Logincommunity;
use yii\db\Query;

class UserController extends BaseController
{
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
        
        $userinfo = UserBasicInfo::find()->select(['nickname','mobile','avatar', 'sex', 'personal_sign', 'backimg','birthday','is_recruit'])
                                         ->where(['mobile'=>$user_mobile])
                                         ->with(['usercommlist'=>function ($query){$query->select(['id','mobile','community_id','community_city_id','community_name','address'])->where(['is_deleted'=>0]);}])
                                         ->asArray()
                                         ->one();
        $userinfo['is_friend'] = "0";
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!empty($mobile) && ($user_mobile != $mobile)) {
            $friend = UserFriends::find()->select(['id', 'remark'])->where(['uid'=>$mobile, 'fid'=>$user_mobile, 'status'=>1])->asArray()->one();
            if (!empty($friend)) {
                $userinfo['is_friend'] = "1";
                $userinfo['remark'] = $friend['remark']; 
                $userinfo['fid'] = $user_mobile;
            }
        }else{
            $userinfo['is_friend'] = "2";
        }

        $this->returnJsonMsg(200, $userinfo, 'SUCCESS'); 
    }
    public function actionEditUserInfo()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $data  = array();
        $avatar = RequestHelper::post('avatar', '', '');
        if(!empty($avatar))
        {
            $data['avatar'] = $avatar;
        }
        $backimg = RequestHelper::post('backimg', '', '');
        if(!empty($backimg))
        {
            $data['backimg'] = $backimg;
        }
        $nickname = RequestHelper::post('nickname', '', '');
        if(!empty($nickname))
        {
            $data['nickname'] = $nickname;
            $data['realname'] = $nickname;
        }
        $sex = RequestHelper::post('sex', '', '');
        if(!empty($sex))
        {
            $data['sex'] = $sex;
        }
        $birthday = RequestHelper::post('birthday', '', '');
        if(!empty($birthday))
        {
            $data['birthday'] = $birthday;
        }
        $personal_sign = RequestHelper::post('personal_sign', '', '');
        if(!empty($personal_sign))
        {
            $data['personal_sign'] = Common::userTextEncode($personal_sign);
        }
        $nation = RequestHelper::post('nation', '', '');
        if(!empty($nation))
        {
            $data['nation'] = $nation;
        }
//        $res = UserBasicInfo::updateAll(['avatar'=>$avatar,'backimg'=>$backimg,'nickname'=>$nickname,
//            'sex'=>$sex,'birthday'=>$birthday,'personal_sign'=>$personal_sign],['mobile'=>$mobile]);
        $res = UserBasicInfo::updateAll($data,['mobile'=>$mobile]);
        $this->returnJsonMsg('200', [], Common::C('code', '200', 'data', '[]'));
    }
    /**
     * 设置好友备注
     * @return array
     */
    public function actionSetRemark()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $fid = RequestHelper::post('fid', '', '');
        if (empty($fid)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $remark = RequestHelper::post('remark', '', '');
        if (empty($remark)) {
            $this->returnJsonMsg('404', [], Common::C('code', '404'));
        }else{
            $res = UserFriends::updateAll(['remark'=>$remark],['uid'=>$mobile, 'fid'=>$fid]);
            $this->returnJsonMsg('200', [], Common::C('code', '200', 'data', '[]'));
        }
    }
    /**
     * 更换个人主页背景
     * @return array
     */
    public function actionEditBackimg()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $userinfo = UserBasicInfo::find()->select(['backimg'])->where(['mobile'=>$mobile])->asArray()->one();
        if (empty($userinfo)) {
            $this->returnJsonMsg('404', [], Common::C('code', '404'));
        }else{
            $res = UserBasicInfo::updateAll(['backimg'=>$userinfo['backimg']], ['mobile'=>$mobile]);
            $this->returnJsonMsg('200', [], Common::C('code', '200', 'data', '[]'));
        }
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
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $user_list = Logincommunity::find()->select(['id','mobile'])->where(['is_deleted'=>0,'community_id'=>$community_id])->andWhere(['<>','mobile',$mobile])->with(['userBasicInfo'=>function($query) {
            $query->select(['mobile', 'nickname', 'avatar','sex','personal_sign']);
        }])->asArray()->all();
        $data = [];
        foreach($user_list as $k =>$v){
            if ($v['mobile'] != $mobile) {
                $res['mobile'] = $v['mobile'];
                $data[$k]['is_friend'] = 0;
            }
            $user_firends = UserFriends::find()->select(['uid'])->where(['uid'=>$mobile,'fid'=>$res,'status'=>1])->asArray()->all();
            $user_firends1 = UserFriends::find()->select(['uid'])->where(['uid'=>$res,'fid'=>$mobile,'status'=>1])->asArray()->all();
            if (!empty($user_firends) && !empty($user_firends1)) {
                $data[$k]['is_friend'] = 1;
            }
        }
        if (!empty($user_list)) {
            foreach ($user_list as $key =>$value) {
                if ($value['mobile'] != $mobile) {
                    $data[$key]['nickname'] = $value['userBasicInfo']['nickname'];
                    $data[$key]['avatar'] = $value['userBasicInfo']['avatar'];
                    $data[$key]['personal_sign'] = $value['userBasicInfo']['personal_sign'];
                    $data[$key]['mobile'] = $value['mobile'];
                    /*if (empty($value['personal_sign'])) {
                        $data[$key]['personal_sign'] = '太懒了!!';
                    }*/
                }
            }
        }
        unset($user_list);
        $this->returnJsonMsg('200', $data, Common::C('code', '200'));
    }

    /**
     * 删除好友
     * @return array
     */
    public function actionDelete()
    {
        $mobile = RequestHelper::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $fmobile = RequestHelper::post('fid','','');
		if (!empty($fmobile)) {
            if (!Common::validateMobile($fmobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }
        
        $fid = RequestHelper::post('friend_id', '', '');
        if(!empty($fid)) {
            $fmobile = User::find()->select(['mobile'])->where(['id'=>$fid])->scalar();
        }
        $userfriends = UserFriends::findOne(['uid'=>$mobile,'fid'=>$fmobile,'status'=>1]);
		$userfriends1 = UserFriends::findOne(['uid'=>$fmobile,'fid'=>$mobile,'status'=>1]);
        if (empty($userfriends) && empty($userfriends1)) {
            return $this->returnJsonMsg('422',[],Common::C('code','422'));
        }
        $userfriends -> status = 3;
        $res = $userfriends -> save();
		
		$userfriends1 -> status = 3;
		$res1 = $userfriends1 ->save();
        if ($res && $res1) {
            UserFriends::deleteAll(['uid'=>$mobile,'fid'=>$fmobile]);
            UserFriends::deleteAll(['uid'=>$fmobile,'fid'=>$mobile]);
            return $this->returnJsonMsg('200',[],Common::C('code','200'));
        } else {
            return $this->returnJsonMsg('500',[],Common::C('code','500'));
        }
    }

    /**
     * 批量获取用户信息
     * @param string  $mobile    电话
     * @param string $json_mobiles    电话json
     * @return array
     * @author liuyanwei <liuyanwei@i500m.com>
     */
    public function actionUserinfoForList()
    {   
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $json_user_mobiles = $_POST['json_user_mobiles'];
        $user_mobiles = json_decode($json_user_mobiles);
        $userinfo = UserBasicInfo::find()->select(['mobile','nickname','avatar','sex','personal_sign','backimg'])->where(['mobile'=>$user_mobiles])->asArray()->all();
        $userlist = array();
        foreach ($userinfo as $value) {
            $userlist[$value['mobile']] = $value;
        }
        $this->returnJsonMsg(200, $userlist, 'SUCCESS');
    }
}
