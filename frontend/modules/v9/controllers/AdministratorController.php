<?php
/**
 * 管理员
 *
 * PHP Version 9
 *
 * @category  Social
 * @package   Service
 * @author    duzongyan <duzongyan@i500m.com>
 * @time      2017/03/07
 * @copyright 2017 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      duzongyan@i500m.com
 */
namespace frontend\modules\v9\controllers;

use frontend\models\i500_social\Message;
use Yii;

use common\helpers\Common;
use common\helpers\CurlHelper;
use common\helpers\RequestHelper;
use yii\helpers\ArrayHelper;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\User;
use yii\db\Query;

class AdministratorController extends BaseController
{   
     /**
     * 管理员列表
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */
    public function actionIndex()
    {   
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $res = UserBasicInfo::find()->select(['id','last_community_id'])->where(['mobile'=>$mobile])->asArray()->one();
        if ($res['last_community_id'] != "") {
            $community_id = $res['last_community_id'];
        } else {
            $this->returnJsonMsg('732', [], '用户未加入小区');
        }

        $result = UserBasicInfo::find()->select(['id','nickname','avatar','mobile'])->where(['is_pioneer'=>2,'last_community_id'=>$community_id])->asArray()->all();
        $this->returnJsonMsg('200',$result, Common::C('code','200','data','[]'));
    }

     /**
     * 添加管理员
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */
    public function actionAdd()
    {   
        $user_mobile = RequestHelper::post('user_mobile', '', '');
        if (empty($user_mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($user_mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $result = UserBasicInfo::find()->select(['id','last_community_id','is_pioneer'])->where(['mobile'=>$mobile])->asArray()->one();
        if ($result['last_community_id'] != "") {
            $community_id = $result['last_community_id'];
        } else {
            $this->returnJsonMsg('732', [], '用户未加入小区');
        }

        $count = UserBasicInfo::find()->select(['id'])->where(['is_pioneer'=>2,'last_community_id'=>$community_id])->count();
        if ($count >= 3) {
            $this->returnJsonMsg('739', [], '管理员人数已达上限');
        }

        if ($result['is_pioneer'] == 1) {
            $data = UserBasicInfo::updateAll(['is_pioneer'=>2],['mobile'=>$user_mobile]);
            if (!$data) {
                return $this->returnJsonMsg('740',[],'已是管理员');
            }
             try {
                 $username = UserBasicInfo::find()->select(['nickname'])->where(['mobile'=>$user_mobile])->scalar();
                 //获取要推送的channel_id
                 // $channel_id1 = User::find()->select(['channel_id'])->where(['mobile'=>$user_mobile])->scalar();
                 $channel_id2 = User::find()->select(['xg_channel_id'])->where(['mobile'=>$user_mobile])->scalar();
                 // echo $channel_id;exit;


                 $message = new Message();
                 $message -> mobile = $user_mobile;
                 $message -> title = '管理员权限';
                 $message -> content = $username.'被委任为管理员';
                 $message -> type = '1';
                 $message -> status = '1';
                 $message -> message_type = '0';
                 $message -> create_time = date('Y-m-d H:i:s');
                 $re = $message -> save(false);
                 if (!$re) {
                     return $this->returnJsonMsg('733',[],'添加数据库失败');
                 }

                 if (!empty($channel_id2))
                 {
                     // list($device_type1,$channel_id1) = explode('-', $channel_id1);
                     // $data1['device_type'] = $device_type1;
                     // $data1['channel_id'] = $channel_id1;
                     // $data['device_type'] = ArrayHelper::getValue($channel, 0);
                     // $data['channel_id'] = ArrayHelper::getValue($channel, 1);
                     // $data1['type'] = 14;//点赞  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 14委任管理员
                     // $data1['title'] = $username.'被委任为管理员';
                     // $data1['description'] = $username.'被委任为管理员';
                     // $channel_url1 = \Yii::$app->params['channelHost'] . 'v1/push';
                     // $re1 = CurlHelper::post($channel_url1, $data1);

                     list($device_type2,$channel_id2) = explode('-', $channel_id2);
                     $data2['device_type'] = $device_type2;
                     $data2['channel_id'] = $channel_id2;
                     // $data['device_type'] = ArrayHelper::getValue($channel, 0);
                     // $data['channel_id'] = ArrayHelper::getValue($channel, 1);
                     $data2['type'] = 14;//点赞  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 14委任管理员
                     $data2['title'] = $username.'被委任为管理员';
                     $data2['description'] = $username.'被委任为管理员';
                     $channel_url2 = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
                     $re2 = CurlHelper::post($channel_url2, $data2);
                     if ($re2['code'] == 200)
                     {
                         $this->returnJsonMsg('200', [], Common::C('code','200'));
                     }
                 }
             } catch( \Exception $e) {}
        } else {
           return $this->returnJsonMsg('741',[],Common::C('code','741'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    } 

     /**
     * 解除管理员
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */

    public function actionDelete()
    {   
        $user_mobile = RequestHelper::post('user_mobile', '', '');
        if (empty($user_mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($user_mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $result = UserBasicInfo::find()->select(['realname','is_pioneer'])->where(['mobile'=>$mobile])->asArray()->one();
        if ($result['is_pioneer'] == 1) {
            $data = UserBasicInfo::updateAll(['is_pioneer'=>0],['mobile'=>$user_mobile]);
            if (!$data) {
                return $this->returnJsonMsg('500',[],'网络繁忙');
            }else{
                $message = new Message();
                $message -> mobile = $user_mobile;
                $message -> title = '管理员权限';
                $message -> content = '您已被'.$result['realname'].'取消了管理员权限';
                $message -> type = '1';
                $message -> status = '1';
                $message -> message_type = '0';
                $message -> create_time = date('Y-m-d H:i:s');
                $re = $message -> save(false);
                if (!$re) {
                    return $this->returnJsonMsg('733',[],'添加数据库失败');
                }
            }
        } else {
           return $this->returnJsonMsg('741',[],Common::C('code','741'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
}