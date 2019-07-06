<?php

/**
 * 抓猫鼬
 *
 * PHP Version 8
 *
 * @category  Social
 * @package   Post
 * @author    wangleilei <wangleilei@i500m.com>
 * @time      2016/11/9
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wangleilei@i500m.com
 */

namespace frontend\modules\v8\controllers;

use common\helpers\CurlHelper;
use common\libs\Balance;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use frontend\models\i500_social\GameActivityPrice;
use frontend\models\i500_social\GameActivityRecord;
use frontend\models\i500_social\GameCommunity;
use frontend\models\i500_social\GameGift;
use frontend\models\i500_social\GameUserItem;
use frontend\models\i500_social\Logincommunity;
use frontend\models\i500_social\User;
class ProbabilityController extends BaseController
{
    public function actionGetReward()
    {
        $activity_id = RequestHelper::post('activity_id', '', ''); 
        if(empty($activity_id))
	{
            $this->returnJsonMsg('4010',[], '活动id不能为空');	
	}
        $community_id = RequestHelper::post('community_id', '', '');
        if(empty($community_id))
	{
            $this->returnJsonMsg('4006',[], '社区id不能为空');	
	}
        $mobile = RequestHelper::post('mobile', '', '');
        if(empty($mobile))
	{
            $this->returnJsonMsg('4005',[], '手机号不能为空');	
	}
        $result =  GameActivityPrice::find()->select(['sum(price_probability) as prob'])->where(['activity_id'=>$activity_id,'community_id'=>$community_id,'delete_flag'=>0])->scalar();
        $res =  GameActivityPrice::find()->select(['i500_game_activity_price.id','i500_game_activity_price.price_id','i500_game_activity_price.price_probability','i500_game_activity_price.price_class','i500_game_activity_price.price_number','i500_game_gift.name as name'])
                                         ->join('LEFT JOIN','i500_game_gift','i500_game_activity_price.price_id=i500_game_gift.id')
                                         ->where(['activity_id'=>$activity_id,'community_id'=>$community_id,'delete_flag'=>0])
                                         ->asArray()
                                         ->all();
        if(!empty($res))
        {
            $prob = array();
            foreach($res as $r)
            {
                $prob[] =$r['price_probability'];
            }

            $newarr = array();
            $numbers = 0;
            for($i=0;$i<count($prob);$i++)
            {
                $numbers = $prob[$i] + $numbers;
                $newarr[] = $numbers/$result;

            }
            $random_number = 0 + mt_rand() / mt_getrandmax() * (1 - 0);
            $newarr[] = $random_number;
            sort($newarr);
            $index = array_search($random_number,$newarr);//获取随机奖品等级

            //猫鼬数量减1
            $catnum = GameCommunity::find()->select(['num'])->where(['community_id'=>$community_id])->scalar();
            $cat_num = $catnum - 1;
            $update_num = GameCommunity::updateAll(['num'=>$cat_num],['community_id'=>$community_id]);

    //        var_dump((int)$res[$index]['price_class']);
    //        exit;
            //奖品数量减1
            $nm = 0;
            if((int)$res[$index]['price_class']>0)
            {
                $pid = $res[$index]['id'];
    //            $pnumber = $res[$index]['price_number'] - 1;
                $price_number = GameActivityPrice::find()->select(['price_number'])->where(['id'=>$pid])->scalar();
                if((int)$price_number>0)
                {
                    $pnumber = $price_number - 1;
                    $nm = GameActivityPrice::updateAll(['price_number'=>$pnumber],['id'=>$pid]);
                }
            }
            //保存用户中奖记录信息
            if($nm>0)
            {
             $game_record = new GameActivityRecord();
             $game_record->mobile = $mobile;
             $game_record->community_id = $community_id;
             $game_record->activity_id = $activity_id;
             $game_record->price_class = $res[$index]['price_class'];
             $game_record->price_id = $res[$index]['price_id'];

             $game_record->save(false);
            }

             $info[] = $res[$index]['price_class'];
             $info[] = $res[$index]['name'];
             if($nm==0)
             {
                 unset($info);
                 $info[] = "0";
                 $info[] = NULL;
             }
            // $this->returnJsonMsg('200', $info, Common::C('code','200','data','[]'));

             //根据游戏参与者推送消息
             $useritem= new GameUserItem();
             $field = array();
             $field[]="i500_game_user_item.id";
             $field[]="i500_user.channel_id as channel_id";

             $condition[Logincommunity::tableName().'.community_id'] = $community_id;
             $condition[Logincommunity::tableName().'.is_deleted'] = '0';
             $condition[GameUserItem::tableName().'.delete_flag'] = '0';
             $condition[GameUserItem::tableName().'.is_exit'] = '0';


             $user_channel = $useritem->find()->select($field)
                                      ->join('LEFT JOIN','i500_user','i500_user.mobile=i500_game_user_item.mobile')
                                      ->join('LEFT JOIN','i500_login_community','i500_game_user_item.mobile=i500_login_community.mobile')
                                      ->where($condition)
                                      ->asArray()
                                      ->all();
             $mychannel = User::find()->select(['channel_id'])->where(['mobile'=>$mobile])->scalar();

             //排除自己的channelid
             foreach ($user_channel as $key=>$value)
            {
                if ($value['channel_id'] == $mychannel)
                {
                    unset($user_channel[$key]);
                }
            }

             //获取要推送的channel_id
             $ios = array();
             $and = array();
             if(!empty($user_channel))
             {
                foreach($user_channel as $uc)
                {
                   $channel = array();
                   $channel = explode("-",$uc['channel_id']);
                   if($channel[0]=='1')
                   {
                       $ios[] = $channel[1];
                   }
                   if($channel[0]=='2')
                   {
                       $and[] = $channel[1];
                   }
                }


             }
        }
         $arr = array();
         $arr['code'] = 200;
         $arr['data'] = $info;
         $arr['message'] = "操作成功";
         echo json_encode($arr);
//         return $this->render('push',['and'=>$and,'ios'=>$ios]);
         $iosarr = implode(",", $ios);
         $andarr = implode(",", $and);
         
         $data['type'] = 20;//点赞  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论
         $data['title'] = '精灵被抓走了';
         $data['description'] = '精灵被抓走了';
         $data['device_type'] = 1;
         $data['channel_id'] = $iosarr;
//         $data['channel_id'] ="4663287031904154835";
         $url = \Yii::$app->params['channelHost'] . 'v1/push/many';
         if(!empty($iosarr))
         {
            CurlHelper::post($url, $data);
         }
//         var_dump($data);
//         exit();
         if(!empty($andarr))
         {
            $data['channel_id'] = $andarr;
            $data['device_type'] = 2;
            CurlHelper::post($url, $data);
         }

    }
}