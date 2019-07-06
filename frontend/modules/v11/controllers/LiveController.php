<?php

/* 
 * 
 * @category  Social
 * @package   Post
 * @author    wangleilei <wangleilei@i500m.com>
 * @time      2017
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wangleilei@i500m.com
 */


namespace frontend\modules\v11\controllers;


use common\helpers\Common;
use common\helpers\CurlHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500m\Community;
use frontend\models\i500m\OpenUserCity;
use frontend\models\i500m\City;
use frontend\models\i500_social\Event;
use frontend\models\i500_social\Stream;
use yii\db\Query;
use frontend\models\Live\LiveData;
use frontend\models\i500_social\Live;

class LiveController extends BaseController
{
    /**
      * 获取推流地址
      * 如果不传key和过期时间，将返回不含防盗链的url
      * @param bizId 您在腾讯云分配到的bizid
      *        streamId 您用来区别不通推流地址的唯一id
      *        key 安全密钥
      *        time 过期时间 sample 2016-11-12 12:00:00
      * @return String url */
    public function actionPushUrl()
    {
        $bizId = Common::C('bizid');
        $key = Common::C('safe_key');
        $streamId = "18740090392q";
        $time = date("Y-m-d H:i:s", strtotime("+1 day"));
        

        if($key && $time)
        {
            $txTime = strtoupper(base_convert(strtotime($time),10,16));
            //txSecret = MD5( KEY + livecode + txTime )
            //livecode = bizid+"_"+stream_id  如 8888_test123456
            $livecode = $bizId."_".$streamId; //直播码
            $txSecret = md5($key.$livecode.$txTime);
            $ext_str = "?".http_build_query(array(
			            "bizid"=> $bizId,
			            "txSecret"=> $txSecret,
			            "txTime"=> $txTime
			        ));
        }
        $res[] = date(strtotime("+1 day"));
        $res[] = "rtmp://".$bizId.".livepush.myqcloud.com/live/".$livecode.(isset($ext_str) ? $ext_str : "");
        return json_encode($res);
    }
    /**
      * 获取播放地址
      * @param bizId 您在腾讯云分配到的bizid
      *        streamId 您用来区别不通推流地址的唯一id
      * @return String url */
    public function actionPlayUrl()
    {
        $bizId = "9133";
//        $streamId = "18740090392p";
        $streamId = "18341724286q";
	$livecode = $bizId."_".$streamId; //直播码
        $res = array("rtmp://".$bizId.".liveplay.myqcloud.com/live/".$livecode,
                     "http://".$bizId.".liveplay.myqcloud.com/live/".$livecode.".flv",
                     "http://".$bizId.".liveplay.myqcloud.com/live/".$livecode.".m3u8");
        return json_encode($res);
    }
    public function actionApiAuth()
    {
        $key = "22dcc2c43f54df98fd06f1e8c9169e39";
        $time = 1496718858;
        return md5($key.$time);
    }
    public function actionLiveCallback()
    {
        $data = file_get_contents('php://input');
        $res = json_decode($data,true);
        $event = new Event();
        $event->t = $res['t'];
        $event->sign = $res['sign'];
        $event->event_type = $res['event_type'];
        $event->stream_id = $res['stream_id'];
        $event->channel_id = $res['channel_id'];
        $event->save(false);
        $event_id = $event->primaryKey;
        if((int)$event_id>0)
        {
            $stream = new Stream();
            $stream->event_id = $event_id;
            $stream->appname = $res['appname'];
            $stream->app = $res['app'];
            $stream->sequence = $res['sequence'];
            $stream->node = $res['node'];
            $stream->user_ip = $res['user_ip'];
            $stream->errcode = $res['errcode'];
            $stream->errmsg = $res['errmsg'];
            $stream->stream_param = $res['stream_param'];
            $stream->save(false);
        }
        else
        {
            $stream_id = $res['stream_id'];
            LiveData::updateAll(['status'=>0],['stream_id'=>$stream_id]);
            
        }
    }
    public function actionSaveLiveUser()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $groupid = RequestHelper::post('groupid', '', '');
        
        $uid = User::find()->select(['id'])->where(['mobile'=>$mobile])->scalar();
        $community_id = UserBasicInfo::find()->select(['last_community_id'])->where(['mobile'=>$mobile])->scalar();
        $live_count = Live::find()->where(['uid'=>$uid])->count();
        if($live_count==0)
        {
            $live = new Live();
            $live->uid = $uid;
            $live->mobile = $mobile;
            $live->groupid = $groupid;
            $live->community_id = $community_id;
            $res = $live->save(false);
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
    public function actionLiveUserList()
    {
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }

        $res['flag'] = RequestHelper::post('flag','1', '');
        $res['community_id'] = RequestHelper::post('community_id', '', '');
        if (empty($res['community_id'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $recommend = RequestHelper::post('recommend', '0', '');
        $res['order'] = RequestHelper::post('order', '1', 'intval');

        // $data = file_get_contents('php://input');
        // $res = json_decode($data,true);
        $flag = $res['flag'];
        $community_id = $res['community_id'];
        $order = "viewer_count";
        if($flag==NULL||$flag=="")
        {
            $flag = 1;
        }
        if(isset($res['order'])&&$res['order']=="1")
        {
            $order = "viewer_count";
        }
        else
        {
            $order = "like_count";
        }
        $field[] = "userid";
        $field[] = "groupid";
        $field[] = "create_time";
        $field[] = "viewer_count";
        $field[] = "like_count";
        $field[] = "title";
        $field[] = "play_url";
        $field[] = "hls_play_url";
        $field[] = "desc";
        
        $field[] = "status";
        $field['fileid'] = (new Query())->select('file_id')->from('tape_data')->where('userid=live_data.userid');
        $field[] = "nickname";
        $field[] = "headpic";
        $field[] = "frontcover";
        $field[] = "location";
        $result = LiveData::find()->select($field)
                                  ->where(['status'=>$flag])
                                  ->orderBy($order.' desc')
                                  ->asArray()
                                  ->all();

        $rec_result = array();
        if($recommend=='1'&&!empty($result))
        {
            foreach($result as $r)
            {
                $rec_result[] = $r;
                
            }
            $result = $rec_result;
        }

        $uid = User::find()->select(['i500_user.id'])->join('left join','i500_user_basic_info','i500_user_basic_info.mobile=i500_user.mobile')
                                    ->where(['i500_user_basic_info.last_community_id'=>$community_id])
                                    ->asArray()
                                    ->all();
        $userid = array();
        if(isset($uid))
        {
            foreach($uid as $id)
            {
                $userid[] = $id['id'];
            }
        }

        $list = array();
        if(isset($result))
        {
            foreach($result as $res)
            {
                if(in_array($res['userid'], $userid))
                {
                    $res['forbid_status'] = 0;
                    $res['type'] = 0;
                    if($res['fileid']==NULL)
                    {
                        $res['fileid'] = "";
                    }
                    $res['viewercount'] = (int)$res['viewer_count'];
                    $res['likecount'] = (int)$res['like_count'];
                    $res['timestamp'] = strtotime($res['create_time']);
                    $res['playurl'] = $res['play_url'];
                    $res['userinfo'] = array('nickname'=>$res['nickname'],'headpic'=>$res['headpic'],'frontcover'=>$res['frontcover'],'location'=>$res['location']);
                    unset($res['nickname']);
                    unset($res['headpic']);
                    unset($res['frontcover']);
                    unset($res['location']);
                    unset($res['create_time']);
                    unset($res['viewer_count']);
                    unset($res['like_count']);
                    unset($res['play_url']);
                    $list[] = $res;
                    
                }
                else
                {
                    continue;
                }
            }
        }
        
        $total = LiveData::find()->select(['id'])
                                 ->where(['status'=>$flag])
                                 ->andWhere(['userid'=>$userid])
                                 ->count();
        
        $return_data = array();
        
        $return_data['returnValue'] = 0;
        $return_data['returnMsg'] = "return successfully!";
        $return_data['returnData'] = array('totalcount'=>$total,'pusherlist'=>$list);
        echo json_encode($return_data);
//        var_dump($res);
    }

    /**
     * 直播给小区其他人推送
     *
     * @return array
     */
    public function actionLiveSendPush(){
        if (empty($this->mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($this->mobile)) {
            return $this->returnJsonMsg('605',[],Common::C('code','605'));
        }
        //查询小区
        $community_id = UserBasicInfo::find()->select(['last_community_id','realname'])->where(['mobile'=>$this->mobile])->asArray()->one();

        //查询当前小区的人
        $user_mobile = UserBasicInfo::find()->select(['mobile'])
                    ->where(['last_community_id'=>$community_id['last_community_id']])
                    ->andWhere(['<>','mobile',$this->mobile])
                    ->asArray()
                    ->column();

        try{
            //查询channel_id
            $channel_id = User::find()->select('xg_channel_id')->where(['mobile'=>$user_mobile])->column();
            $ios = [];
            $and = [];
            if (!empty($channel_id)) {
                for ($i = 0; $i < count($channel_id); $i++) {
                    $channel = [];
                    $channel = explode("-", $channel_id[$i]);
                    if ($channel[0] == '1') {
                        $ios[] = $channel[1];
                    }
                    if ($channel[0] == '2') {
                        $and[] = $channel[1];
                    }
                }
            }
            $iosarr = implode(",", $ios);
            $andarr = implode(",", $and);

            $data['type'] = 32;//点赞  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9系统消息 11 点亮社区 20直播推送
            $data['title'] = $community_id['realname'].'正在直播';
            $data['description'] ='你的邻居正在直播，快来围观吧';
            $data['device_type'] = 1;
            $data['channel_id'] = $iosarr;
            $url = \Yii::$app->params['channelHost'] . 'v1/xg-push/many';
            if (!empty($iosarr)) {
                $ios_result = CurlHelper::post($url, $data);
            }
            if (!empty($andarr)) {
                $data['channel_id'] = $andarr;
                $data['device_type'] = 2;
                $and_result = CurlHelper::post($url, $data);
            }
        } catch (\Exception $e) {}
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
}



			


      
			
