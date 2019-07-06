<?php

namespace frontend\models\i500_social;
use common\helpers\CurlHelper;
use common\helpers\TxyunHelper;
use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%user_code}}".
 *
 * @property integer $id
 * @property string $code
 * @property integer $type
 * @property string $create_time
 * @property string $expires_in
 */
class UserFriends extends SocialBase
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%i500_user_friends}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'fid'], 'required'],
            ['uid',function($attribute, $params){
                if ($this->$attribute == $this->fid) {
                    $this->addError($attribute, '不能加自己为好友');
                }
            }],
            ['fid',function($attribute, $params){
                if (User::find()->where(['mobile'=>$this->fid, 'status'=>2])->count() <= 0) {
                    $this->addError($attribute, '用户不存在');
                }
            }],
            ['status','default', 'value'=>0],
            ['create_time', 'filter', 'filter'=>function(){
                return date("Y-m-d H:i:s", time());
            }],
//            [['uid','fid'], 'unique', 'targetAttribute' => ['uid', 'fid'],'message'=>'他已经是你的好友了']//同一个账号 只能绑
            [['message'], 'string', 'max' => 50],
        ];
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => '手机号',
            'fid' => '好友手机号',
            'remark' => '备注',
            'create_time' => '关注时间',
            'message' => '验证消息',
        ];
    }
    public function afterSave($insert,$changedAttributes)
    {
        if ($insert) {
//            try {
//                //获取要推送的channel_id
//                $channel_id = User::find()->select('channel_id')->where(['mobile'=>$this->fid])->scalar();
//                if (!empty($channel_id)) {
//                    $channel = explode('-', $channel_id);
//                    $data['device_type'] = ArrayHelper::getValue($channel, 0);
//                    $data['channel_id'] = ArrayHelper::getValue($channel, 1);
//                    $data['type'] = 10;
//                    $data['title'] = '有一条好友请求';
//                    $data['description'] = '有一条好友请求';
//                    //$data['title'] = '您有一个新订单';
//                    $channel_url = \Yii::$app->params['channelHost'] . 'v1/push';
//                    $re = CurlHelper::post($channel_url, $data);
//                    if ($re['code'] == 200) {
//                    } else {
//                        file_put_contents('/tmp/push.log', "执行时间：" . date('Y-m-d H:i:s') . " 加好友推送失败 " . "\n", FILE_APPEND);
//
//                    }
//                }
//            } catch( Exception $e) {
//                file_put_contents('/tmp/push.log', "执行时间：" . date('Y-m-d H:i:s') . " 加好友推送失败：\n", FILE_APPEND);
//            }
        }
    }

    /**
     * 根据对方的uid 数组 一次判断对方是否关注自己 (是否是自己的粉丝)
     *
     * $uids array   用户id数组
     * $fid  integer 当前用户id
     * @param array $data
     */
    public static function isFans($uids = [], $fid)
    {
        $data = [];
        if (!empty($uids) && !empty($fid)) {
            $fans = self::find()->select(['uid'])->where(['uid'=>$uids, 'fid'=>$fid])->asArray()->column();

            if (!empty($fans)) {
                foreach ($uids as $k => $v) {
                    if (in_array($v, $fans)) {
                        $data[$v] = '1';
                    } else {
                        $data[$v] = '0';
                    }
                }
            } else {
                foreach ($uids as $k => $v) {
                    $data[$v] = '0';
                }
            }
        }
        return $data;
    }
    /**
     * 根绝对方用户id 判断 我是否关注对方
     */
    public static function isFollow($fids = [], $uid)
    {
        $data = [];
        if (!empty($uid) && !empty($fids)) {
            $follow = self::find()->select(['fid'])->where(['uid'=>$uid, 'fid'=>$fids])->asArray()->column();

            if (!empty($follow)) {
                foreach ($fids as  $v) {
                    if (in_array($v, $follow)) {
                        $data[$v] = '1';
                    } else {
                        $data[$v] = '0';
                    }
                }
            } else {
                foreach ($fids as $k => $v) {
                    $data[$v] = '0';
                }
            }
        }
        return $data;
    }

    //黑名单
    public static function getBlackList($mobile){
        return $res = self::find()->select(['fid','black_time'])
               ->with(['user'=>function($query){
                    $query->select(['mobile','realname','avatar']);
               }])
               ->where(['uid'=>$mobile,'status'=>4])
               ->asArray()
               ->all();
    }

    public function setUp($uid, $fid, $status){
        $date = date('Y-m-d H:i:s');
//        $res = false;
//        $res1 = false;
        //加入黑名单操作
        if ($status == 4) {
            //不是好友关系从未添加过好友
            $friend =  UserFriends::find()->select(['status'])->where(['uid'=>$uid,'fid'=>$fid])->asArray()->one();
            if (!isset($friend)) {
                $newFriend = new UserFriends();
                $newFriend -> uid = $uid;
                $newFriend -> fid = $fid;
                $newFriend -> status = 4;
                $newFriend -> create_time = date('Y-m-d H:i:s');
                $newFriend -> black_time = date('Y-m-d H:i:s');
                $newFriend ->save(false);

            } else {//之前是好友关系，但是已经被删除 的i情况下，加黑名单
                //查询是否被对方添加黑名单了
                $data_status = UserFriends::find()->select(['status'])->where(['uid'=>$fid,'fid'=>$uid])->scalar();
                if ($data_status == 4) {
                    $res1 = $this -> updateInfo(['status'=>4,'black_time'=>$date],['uid'=>$uid,'fid'=>$fid]);
                } else {
                    //解除好友关系并加入黑名单
                    $res  = $this -> updateInfo(['status'=>4,'black_time'=>$date],['uid'=>$uid,'fid'=>$fid]);
                    $res1 = $this -> updateInfo(['status'=>3,'black_time'=>$date],['uid'=>$fid,'fid'=>$uid]);
                }
            }
        } else {
            //解除黑名单操作
            $res  = $this -> updateInfo(['status'=>5,'black_time'=>$date],['uid'=>$uid,'fid'=>$fid]);
        }

//        if($res && $res1){
//            return true;
//        } else {
//            return false;
//        }
    }

    public function getUser(){
        return $this->hasOne(UserBasicInfo::className(),['mobile'=>'fid']);
    }
}
