<?php
/**
 * 好友相关
 *
 * PHP Version 5
 * 可写多行的文件相关说明
 *
 * @category  I500M
 * @package   Member
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      16/10/14
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */
namespace frontend\modules\v8\controllers;

use common\helpers\RequestHelper;
use common\helpers\Common;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserFriends;
use frontend\models\i500_social\Integral;
use frontend\models\i500_social\IntegralLevel;
use frontend\models\i500_social\User;
use yii\helpers\ArrayHelper;
use common\helpers\CurlHelper;


class FriendController extends BaseController
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
     * 获取 待同意好友
     * @param string  $mobile    电话
     * @return array
 	 * @author liuyanwei <liuyanwei@i500m.com>
     */
    public function actionNewfriendList()
    {   
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $friendlist = UserFriends::find()->select(['uid','create_time'])->where(['fid'=>$mobile,'status'=>0])->asArray()->all();
        $user_ids = array();
        $create_time = [];
        foreach ($friendlist as $key => $value) {
        	$user_ids[] = $value['uid'];
        	$create_time[$value['uid']] = $value['create_time'];
        }	
		
        $userlist = UserBasicInfo::find()
                    ->select(['i500_user_basic_info.id','mobile','nickname','avatar','sex','personal_sign'])
					->join('LEFT JOIN','i500_user_friends','i500_user_friends.uid=i500_user_basic_info.mobile')
                    ->where(['mobile'=>$user_ids])
					
                    ->with(['user'=>function($query) {
                        $query->select(['mobile'])->where(['status'=>'2','is_deleted'=>2]);
                    }])
					->orderBy('i500_user_friends.create_time desc')
					->asArray()
					->all();
					
        foreach ($userlist as $key => $value) {
        	$userlist[$key]['create_time'] = $create_time[$value['mobile']];
            $userlist[$key]['level'] = $this->_getLevel($value['mobile']);
        }
        $this->returnJsonMsg(200, $userlist, 'SUCCESS');
    }

	
    /**
     * 获取 好友列表
     * @param string  $mobile    电话
     * @return array
 	 * @author liuyanwei <liuyanwei@i500m.com>
     */
    public function actionFriendList()
    {   
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $friendlist = UserFriends::find()->select(['fid'])->where(['uid'=>$mobile,'status'=>1])->asArray()->all();
        $user_ids = array();
        foreach ($friendlist as $key => $value) {
        	$user_ids[] = $value['fid'];
        }
        $userlist = UserBasicInfo::find()
                    ->select(['i500_user_basic_info.id','i500_user_basic_info.mobile','i500_user_basic_info.nickname','i500_user_basic_info.avatar','i500_user_basic_info.sex','i500_user_basic_info.personal_sign','i500_user_basic_info.is_recruit','i500_user_friends.remark'])
                    ->join('LEFT JOIN','i500_user_friends','i500_user_basic_info.mobile=i500_user_friends.fid')
                    ->where(['mobile'=>$user_ids])
                    ->with(['user'=>function($query) {
                        $query->select(['mobile'])->where(['status'=>'2','is_deleted'=>2]);
                    }])->asArray()->all();

       
        $this->returnJsonMsg(200, $userlist, 'SUCCESS');
    }

    //获取等级
    private function _getLevel($mobile = ''){
        if(!empty($mobile)){
            $score = Integral::find()->select(['score'])->where(['mobile'=>$mobile])->scalar();
            $level = IntegralLevel::find()->select(['gradation','level_name'])->orderBy('gradation')->asArray()->all();
            if(count($level)>0)
            {
                for($i=0;$i<count($level);$i++)
                {
                    if($score>$level[$i]['gradation'])
                    {
                        continue;
                    }
                    else
                    {
                        $data[] = $level[$i]['level_name'];
                        break;
                    }
                }
            }else{
                
                $data['level_name'] = "0";
            
            }
            return $data;
        }
    }
}
