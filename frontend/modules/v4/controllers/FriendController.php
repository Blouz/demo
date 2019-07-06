<?php
namespace frontend\modules\v4\controllers;

use frontend\controllers\RestController;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserFriends;
use frontend\models\i500_social\User;
use Yii;
use yii\helpers\ArrayHelper;
use common\helpers\CurlHelper;

class FriendController extends RestController
{
    public $modelClass = 'frontend\models\i500_social\UserFriends';
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['delete'],$actions['create'],$actions['index']);
        return $actions;
    }
    /**
     * 加好友
     */
    public function actionCreate()
    {
        $model = new $this->modelClass;
        $data = Yii::$app->request->post();
        if(!empty($data['friend_id'])) {
            $fmobile = User::find()->select(['mobile'])->where(['id'=>$data['friend_id']])->asArray()->one();
            $data['fid'] = $fmobile['mobile'];
        }
        $mobile = $this->mobile;
        $fid = $data['fid'];
        $data['uid'] = $this->mobile;
        $model->load($data, '');
        $re = $model->validate();
        if ($re == true) {
            $friend_model = $this->findModel(['uid'=>$this->mobile, 'fid'=>$data['fid']]);
			$friend_models = $this->findModel(['uid'=>$data['fid'], 'fid'=>$this->mobile]);
            if (!empty($friend_model)) {
                if ($friend_model->status == 1) {
                    $this->result['code'] = 422;
                    $this->result['message'] = '已经是你的好友,请勿重复申请';
                } else if ($friend_model->status == 0) {
                    $this->result['code'] = 422;
                    $this->result['message'] = '你已经申请过,请勿重复申请';
                    $uf = new UserFriends();
                    $uf->updateAll(array('relation_status'=>1),'uid=:mobile AND fid=:freind_id',array(':mobile'=>$mobile,':freind_id'=>$fid));    
                } else if ($friend_model->status == 4) {
                    $this->result['code'] = 423;
                    $this->result['message'] = '对方在你的黑名单中,请解除黑名单,再添加好友';
                } else if(!empty($friend_models->status) && $friend_models->status == 4){
                    $this->result['code'] = 424;
                    $this->result['message'] = '你在对方的黑名单中,不能添加好友';
                } else {
                    $friend_model->status = 0;
                    $friend_model->read = 0;
                    $friend_model->message = $data['message'];
                    $res = $friend_model->save(false);
                    
                    if($res)
                    {
//                        $username = UserBasicInfo::find()->select(['nickname'])->where(['mobile'=>$data['fid']])->asArray()->one();
//                        //获取要推送的channel_id
//                        $channel_id = User::find()->select('channel_id')->where(['mobile'=>$data['fid']])->scalar();
//                        if (!empty($channel_id))
//                        {
//                            $channel = explode('-', $channel_id);
//                            $data['device_type'] = ArrayHelper::getValue($channel, 0);
//                            $data['channel_id'] = ArrayHelper::getValue($channel, 1);
//                            $data['type'] = 6;//添加好友标识   3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9消息 10 等级提升 11 点亮社区
//                            $data['title'] = $username.'请求添加您为好友';
//                            $data['description'] = $username.'请求添加您为好友';
//                            $channel_url = \Yii::$app->params['channelHost'] . 'v1/push';
//                            $re = CurlHelper::post($channel_url, $data);
//
//                        }

                        $channel_id1 = User::find()->select('xg_channel_id')->where(['mobile'=>$data['fid']])->scalar();
                        if (!empty($channel_id1))
                        {
                            $channel1 = explode('-', $channel_id1);
                            $data1['device_type'] = ArrayHelper::getValue($channel1, 0);
                            $data1['channel_id'] = ArrayHelper::getValue($channel1, 1);
                            $data1['type'] = 6;
                            $data1['title'] = '有一条好友请求';
                            $data1['description'] = '有一条好友请求';
                            $channel_url1 = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
                            $re = CurlHelper::post($channel_url1, $data1);
                            if ($re['code'] == 200) {
                            } else {
                                //file_put_contents('/tmp/push.log', "执行时间：" . date('Y-m-d H:i:s') . " 加好友推送失败 " . "\n", FILE_APPEND);

                            }
                        }
                    }   
//                    $this->returnJsonMsg('200', $res, Common::C('code', '200'));
                }
            } else {
                if (!$model->save()) {
                    if ($model->hasErrors()) {
                        return $model;
                    } else {
                        $this->result['code'] = 500;
                        $this->result['message'] = '网络繁忙';
                    }
                } else {
                    try {
                        $channel_id1 = User::find()->select('xg_channel_id')->where(['mobile'=>$data['fid']])->scalar();
                        if(!empty($channel_id1))
                        {
                            $channel1 = explode('-', $channel_id1);
                            $data1['device_type'] = ArrayHelper::getValue($channel1, 0);
                            $data1['channel_id'] = ArrayHelper::getValue($channel1, 1);
                            $data1['type'] = 6;
                            $data1['title'] = '有一条好友请求';
                            $data1['description'] = '有一条好友请求';
                            $channel_url1 = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
                            $re = CurlHelper::post($channel_url1, $data1);
                            if ($re['code'] == 200) {
                            } else {
                                //file_put_contents('/tmp/push.log', "执行时间：" . date('Y-m-d H:i:s') . " 加好友推送失败 " . "\n", FILE_APPEND);

                            }
                        }
                    } catch( \Exception $e) {
                        file_put_contents('/tmp/push.log', "执行时间：" . date('Y-m-d H:i:s') . " 加好友推送失败：\n", FILE_APPEND);
                    }
                }
            }
        } else {
            return $model;
        }

        if($data['type'] == 2) {
            $user_info = User::find()->select(['open_contact','xg_channel_id'])->where(['mobile'=>$data['fid']])->asArray()->one();
            if ($user_info['open_contact'] == 0) {
                try {
                    if(!empty($user_info['xg_channel_id']))
                    {
                        $channel = explode('-', $user_info['xg_channel_id']);
                        $date['device_type'] = ArrayHelper::getValue($channel, 0);
                        $date['channel_id'] = ArrayHelper::getValue($channel, 1);
                        $date['type'] = 13;//点赞  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9系统消息 10 11点亮社区  12 家庭组推送 13通讯录推送
                        $date['title'] = '是否同步通讯录';
                        $date['description'] ='是否同步通讯录？通讯录仅用于查找好友,不会泄露隐私。';
                        $channel_url = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
                        $re = CurlHelper::post($channel_url, $date);
                    }
                } catch( \Exception $e) {}

            }
        }
        

        return $this->result;
    }

    /**
     * 我的好友
     * @return array
     */
    public function actionIndex()
    {
        if (empty($this->mobile)) {
            $this->result['code'] = 422;
            $this->result['message'] = '手机号不能为空';
            return $this->result;
        }
        $map = ['uid'=>$this->mobile, 'status'=>1];
        $list = $this->findAll($map);
        $data = [];
        if(!empty($list)) {
            $user_ids = [$this->mobile];
            foreach ($list as $k => $v) {
                $user_ids[] = $v['fid'];
            }
//            var_dump($user_ids);
            $user_list = UserBasicInfo::find()
                ->select(['mobile','nickname','last_community_id'])
                ->where(['mobile'=>$user_ids])
                ->asArray()->all();
            $user_list = ArrayHelper::index($user_list, 'mobile');
//            var_dump($user_list);
            foreach ($list as $k => $v) {
                $status = (ArrayHelper::getValue($user_list, $v['fid'].'.last_community_id', -1) == $user_list[$this->mobile]['last_community_id'])? 1 : 0;
                $data[] = [
                    'mobile'=>$v['fid'],
                    'nickname'=>ArrayHelper::getValue($user_list, $v['fid'].'.nickname', $v['fid']),
                    'status'=>$status,
                ];
            }
        }
        $this->result['data'] = $data;
        return $this->result;
    }

    /**
     * 取消关注某人
     */
    public function actionDelete($id)
    {

        $modelClass = $this->modelClass;
        if (empty($this->mobile)) {
            $this->result['code'] = 422;
            $this->result['message'] = '手机号不能为空';
            return $this->result;
        }
        $model = $modelClass::findOne(['uid'=>$this->mobile, 'fid'=>$id]);
        if (empty($model)) {
            $this->result['code'] = 422;
            $this->result['message'] = '他不是你的朋友';
            return $this->result;
        }
        $re = $model->delete();
        if ($re) {
            $modelClass::deleteAll(['fid'=>$this->mobile, 'uid'=>$id]);
        } else {
            $this->result['code'] = 500;
            $this->result['message'] = '网络繁忙';
        }
        //UserCount::deleteAll(['']);
        return $this->result;
    }

    /**
     * 附近的人
     */
    public function actionNear(){
        if (empty($this->mobile)) {
            $this->result['code'] = 422;
            $this->result['message'] = '手机号不能为空';
            return $this->result;
        }
        $community_id = Yii::$app->request->get('community_id', 0);
        if (empty($community_id)) {
            $this->result['code'] = 422;
            $this->result['message'] = '无效的小区id';
            return $this->result;
        }
        $user = UserBasicInfo::find()->select(['mobile', 'last_community_id', 'nickname', 'avatar', 'personal_sign'])
            ->where(['last_community_id'=>$community_id])->asArray()->all();
        $data = [];
        if (!empty($user)) {
            foreach ($user as $k=>$v) {
                if ($v['mobile'] != $this->mobile) {
//                    unset($user[$k]);
                    $data[] = $v;
                }
            }
        }
        unset($user);
        $this->result['data'] = $data;
        return $this->result;
    }

    /**
     * 新的朋友 等待验证的
     * @return array
     */
    public function actionNewFriend()
    {
        if (empty($this->mobile)) {
            $this->result['code'] = 422;
            $this->result['message'] = '手机号不能为空';
            return $this->result;
        }
//        $map = ['fid'=>$this->mobile, 'status'=>0];
        $map = ['fid'=>$this->mobile];
        $list = $this->findAll($map);
        $data = [];
        if(!empty($list)) {
            $user_ids = [$this->mobile];
            foreach ($list as $k => $v) {
                $user_ids[] = $v['uid'];
            }
            $user_list = UserBasicInfo::find()
                ->select(['mobile','nickname','last_community_id','avatar'])
                ->where(['mobile'=>$user_ids])
                ->asArray()->all();
            $user_list = ArrayHelper::index($user_list, 'mobile');
//            var_dump($user_list);
            foreach ($list as $k => $v) {
                $is_same = (ArrayHelper::getValue($user_list, $v['uid'].'.last_community_id', -1) == $user_list[$this->mobile]['last_community_id'])? 1 : 0;
                $data[] = [
                    'mobile'=>$v['uid'],
                    'nickname'=>ArrayHelper::getValue($user_list, $v['uid'].'.nickname', $v['uid']),
                    'avatar'=>ArrayHelper::getValue($user_list, $v['uid'].'.avatar', ''),
                    'is_same'=>$is_same,
                    'status'=>$v['status'],
                ];
            }
        }
        $this->result['data'] = $data;
        return $this->result;
    }
    public function actionDoFriend()
    {
//        $model = new $this->modelClass;
        $data = Yii::$app->request->post();
        if (empty($data['from_uid'])) {
            $this->result['code'] = 422;
            $this->result['message'] = '对方手机号不能为空';
            return $this->result;
        }

        if(!empty($data['type']) && in_array($data['type'], ['agree','delete'])) {
            $time = date("Y-m-d H:i:s");
            $user_model = $this->findModel(['fid'=>$this->mobile, 'uid'=>$data['from_uid']]);

            if (!empty($user_model)) {
                if ($data['type'] == 'delete') {//拒绝
//                    $user_model->status = 2;
//                    $user_model->agree_time = $time;
//                    $user_model->save();
                    $user_model->delete();
                } else {//同意
                    $user_model->status = 1;
                    if ($user_model->save()) {
                        $new = $this->findModel(['uid'=>$this->mobile, 'fid'=>$data['from_uid']]);
                        if (!empty($new)) {
                            $new->status = 1;
                            $new->save();
                        } else {
                            $new_model = new UserFriends();
                            $new_model->uid = $this->mobile;
                            $new_model->fid = $data['from_uid'];
                            $new_model->status = 1;
                            $new_model->create_time = $time;
                            $new_model->agree_time = $time;
                            $new_model->save();
                        }

                    }
                }
            } else {
                $this->result['code'] = 404;
                $this->result['message'] = '此数据不存在';
            }
        } else {
            $this->result['code'] = 422;
            $this->result['message'] = '类型必须为同意或者拒绝';
        }
//        $data['uid'] = $this->mobile;

        return $this->result;
    }

}
