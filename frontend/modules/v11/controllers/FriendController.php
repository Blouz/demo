<?php
/**
 * 批量添加好友接口
 *
 * PHP Version 8
 *
 * @category  Social
 * @package   Service
 * @author    yaoxin <yaoxin@i500m.com>
 * @time      2017/03/01
 * @copyright 2016 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      yaoxin@i500m.com
 */
namespace frontend\modules\v11\controllers;

use Yii;
use yii\db\Query;
use yii\data\Pagination;
use common\helpers\Common;
use yii\helpers\ArrayHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserFriends;
use frontend\models\i500_social\Integral;
use frontend\models\i500_social\IntegralLevel;
use common\helpers\CurlHelper;

class FriendController extends BaseController
{
    /**
     * 批量添加好友
     * @return array()
    **/
    public function actionBatchaddFriends()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if(empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if(!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $friend_mobile = $_POST['friend_mobile'];
        if(empty($friend_mobile)) {
            $step = User::updateAll(['step'=>'8'], ['mobile'=>$mobile]);
            $this->returnJsonMsg('200', [], Common::C('code', '200'));
        }
        $time = date('Y-m-d H:i:s', time());
        $fid = json_decode($friend_mobile, true);
        foreach($fid as $k => $v) {
            //判断是否已申请好友
            $user_model = UserFriends::find()->select(['status'])->where(['uid'=>$mobile, 'fid'=>$v])->asArray()->one();
            if(empty($user_model)) {
                $user = new UserFriends();
                $user->uid = $mobile;
                $user->fid = $v;
                $user->status = '0';
                $user->create_time = $time;
                $res = $user->save();
                if($res){
                    $userinfo = new UserBasicInfo();
                    $username = $userinfo::find()->select(['nickname'])->where(['mobile'=>$mobile])->asArray()->one();
                    //获取要推送的channel_id
                    $channel_id = User::find()->select('channel_id')->where(['mobile'=>$v])->scalar();
                    if (!empty($channel_id))
                    {
                        $channel = explode('-', $channel_id);
                        $data['device_type'] = ArrayHelper::getValue($channel, 0);
                        $data['channel_id'] = ArrayHelper::getValue($channel, 1);
                        $data['type'] = 6;//添加好友标识   3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9加入社区
                        $data['title'] = $username['nickname'].'请求添加您为好友';
                        $data['description'] = $username['nickname'].'请求添加您为好友';
                        $channel_url = \Yii::$app->params['channelHost'] . 'v1/push';
                        $re = CurlHelper::post($channel_url, $data);
                    }

                    $channel_id1 = User::find()->select('xg_channel_id')->where(['mobile'=>$v])->scalar();
                    if(!empty($channel_id1))
                    {
                        $channel1 = explode('-', $channel_id1);
                        $data1['device_type'] = ArrayHelper::getValue($channel1, 0);
                        $data1['channel_id'] = ArrayHelper::getValue($channel1, 1);
                        $data1['type'] = 6;//添加好友标识   3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9加入社区
                        $data1['title'] = $username['nickname'].'请求添加您为好友';
                        $data1['description'] = $username['nickname'].'请求添加您为好友';
                        $channel_url1 = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
                        $re = CurlHelper::post($channel_url1, $data1);
                    }
                }  
            }elseif($user_model['status'] != 0 && $user_model['status'] != 1){
                $res = UserFriends::updateAll(['status'=>'0'], ['uid'=>$mobile, 'fid'=>$v]);
                if($res){
                    $userinfo = new UserBasicInfo();
                    $username = $userinfo::find()->select(['nickname'])->where(['mobile'=>$mobile])->asArray()->one();
                    //获取要推送的channel_id
                    $channel_id = User::find()->select('channel_id')->where(['mobile'=>$v])->scalar();
                    $channel_id1 = User::find()->select('xg_channel_id')->where(['mobile'=>$v])->scalar();
                    if (!empty($channel_id))
                    {
                        $channel1 = explode('-', $channel_id1);
                        $data1['device_type'] = ArrayHelper::getValue($channel1, 0);
                        $data1['channel_id'] = ArrayHelper::getValue($channel1, 1);
                        $data1['type'] = 6;//添加好友标识   3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9加入社区
                        $data1['title'] = $username['nickname'].'请求添加您为好友';
                        $data1['description'] = $username['nickname'].'请求添加您为好友';
                        $channel_url1 = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
                        $re = CurlHelper::post($channel_url1, $data1);


                        $channel = explode('-', $channel_id);
                        $data['device_type'] = ArrayHelper::getValue($channel, 0);
                        $data['channel_id'] = ArrayHelper::getValue($channel, 1);
                        $data['type'] = 6;//添加好友标识   3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9加入社区
                        $data['title'] = $username['nickname'].'请求添加您为好友';
                        $data['description'] = $username['nickname'].'请求添加您为好友';
                        $channel_url = \Yii::$app->params['channelHost'] . 'v1/push';
                        $re = CurlHelper::post($channel_url, $data);
                    }
                }
            }
        }
        $step = User::updateAll(['step'=>'8'], ['mobile'=>$mobile]);
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /*
    *  获取所有的 添加的好友列表()
    *  @author haungdekui  
    *  @return json;
    */
    public function actionFriendList(){
        $mobile = RequestHelper::post('mobile', '', '');
        if(empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if(!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $read = UserFriends::find()->select(['read'])->where(['fid'=>$mobile,'relation_status'=>1])->column();
        if (in_array(0, $read)) {
            $res = UserFriends::updateAll(['read'=>1],['fid'=>$mobile,'read'=>0,'relation_status'=>1]);
            if (!$res) {
                $this->returnJsonMsg('400', [], Common::C('code', '400'));
            }
        }
        
        $userlist = UserBasicInfo::find()->select([UserFriends::tableName().'.id',UserFriends::tableName().'.status',UserFriends::tableName().'.uid',UserFriends::tableName().'.message','nickname','avatar','sex','personal_sign','age'])
                    ->join('LEFT JOIN','i500_user_friends','i500_user_friends.uid=i500_user_basic_info.mobile')
                    ->where([UserFriends::tableName().'.fid'=>$mobile,UserFriends::tableName().'.relation_status'=>1])
                    ->orderBy('i500_user_friends.id desc')
                    ->asArray()
                    ->all();

        if($userlist){
            foreach ($userlist as $key => $value) {
                $userlist[$key]['level'] = $this->_getLevel($value['uid']);
            }
        }
        $this->returnJsonMsg('200', $userlist, Common::C('code', '200'));
    }


    /*
     *  获取等级
     *  @author haungdekui  
     *  @return string;
    */
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
                        $data = $level[$i]['level_name'];
                        break;
                    }
                }
            }else{
                
                $data = "0";
            
            }
            return $data;
        }
    }
}

?>