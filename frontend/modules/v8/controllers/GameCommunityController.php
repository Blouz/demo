<?php
/**
 *
 * @see 游戏道具类
 * @time 2016年11月10日
 * @author yaoxin
 *
 */

namespace frontend\modules\v8\controllers;

use common\helpers\BaseRequestHelps;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\GameCommunity;
use frontend\models\i500_social\GameUserItem;
use frontend\models\i500_social\GameActivity;
use frontend\models\i500_social\CommunityActivity;
use common\helpers\Common;
class GameCommunityController extends BaseController
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
    *
    * 游戏道具初始化
    * @param int $itemId 道具ID
    * @param string $mobile 用户手机号
    * @param string $communityId 小区ID
    * @return array
    * 
    */
	public function actionUserCommunity()
	{
        $item_id = BaseRequestHelps::post('itemId','','intval');
        if(empty($item_id)){
            $this->returnJsonMsg('511', [],Common::C('code','511'));
        }

		$community_id = BaseRequestHelps::post('communityId', '', '');
        if(empty($community_id)){
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }

        $mobile = BaseRequestHelps::post('mobile', '', '');
        if(empty($mobile)){
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }else{
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }
        
        $community = GameCommunity::find()->select(['community_id','community_name','community_vertexes','num','update_time'])->where(['status'=>1,'community_id'=>$community_id,'delete_flag'=>0])->asArray()->one();
        $community['community_vertexes'] = json_decode($community['community_vertexes'],true);
        if ($community['num'] < 1) {
            $this->returnJsonMsg('655', [],'游戏暂未开始');
        }
		
		$time = date('Y-m-d H:i:s');
		$update_time = date('d', strtotime($community['update_time']));
		$newtime = date('d',time());
		$balance = $newtime - $update_time;
		if($balance > 0){
			$sql = "UPDATE i500_game_community SET num = 20  WHERE status=1 AND community_id='$community_id' AND delete_flag=0";
			$res = \Yii::$app->db_social->createCommand($sql)->execute();
			$sql1 = "UPDATE i500_game_community SET update_time = '$time'  WHERE status=1 AND community_id='$community_id' AND delete_flag=0";
			$res1 = \Yii::$app->db_social->createCommand($sql1)->execute();
		}
        $activity_id = BaseRequestHelps::post('activity_id', '', '');
        if(empty($activity_id)){
            $this->returnJsonMsg('511', [],Common::C('code','511'));
        }
        //查询活动是否开启
        $gameactivity = GameActivity::find()->select(['begin_time', 'end_time'])->where(['id' => $activity_id,'status'=>1,'delete_flag'=>0])->asArray()->one();
        $nowtime = date('H', time());
        if($nowtime < $gameactivity['begin_time'] or $nowtime > $gameactivity['end_time'])
        {
            $this->returnJsonMsg('655', [], '游戏暂未开始');
        }
        //查询是否有道具
        $model = GameUserItem::find()->select(['item_number'])->where(['status'=>1,'delete_flag'=>0,'item_id'=>$item_id,'mobile'=>$mobile])->asArray()->one();
        if(empty($model)){
            $gameuseritem = new GameUserItem();
            $gameuseritem -> item_id = $item_id;
            $gameuseritem -> item_number = 3;
            $gameuseritem -> mobile = $mobile;
            $gameuseritem -> is_exit = 0;
            $res1 = $gameuseritem -> save();
            $model = GameUserItem::find()->select(['item_number'])->where(['status'=>1,'delete_flag'=>0,'item_id'=>$item_id,'mobile'=>$mobile])->asArray()->one();
        }else{
            $sql =  "UPDATE i500_game_user_item SET item_number=3,is_exit=0,update_time='$time'  WHERE mobile='$mobile' AND item_id='$item_id' AND delete_flag=0 AND datediff(curdate(), date(update_time))>0";
            $res = \Yii::$app->db_social->createCommand($sql)->execute();
			$sql1 = "UPDATE i500_game_user_item SET is_exit = 0  WHERE mobile='$mobile' AND item_id='$item_id' AND delete_flag=0";
			$res1 = \Yii::$app->db_social->createCommand($sql1)->execute();
        }

        $userbasicinfo = UserBasicInfo::find()->select(['mobile','avatar','nickname'])->where(['mobile'=>$mobile])->asArray()->all();
        $list = array();
        $list['community_id'] = $community['community_id'];
        $list['community_name'] = $community['community_name'];
        $list['community_vertexes'] = $community['community_vertexes'];
        $list['num'] = $community['num'];
        $list['item_number'] = $model['item_number'];
        $list['mobile'] = $userbasicinfo['0']['mobile'];
        $list['avatar'] = $userbasicinfo['0']['avatar'];
        $list['nickname'] = $userbasicinfo['0']['nickname'];
        
        return $this->returnJsonMsg('200', $list, Common::C('code','200'));
	}

    /**
    * 用户道具使用数量
    * @param int $item_id 道具id
    * @return 
    */
    public function actionUserItem()
    {
        $item_id = BaseRequestHelps::post('item_id','','intval');
        if(empty($item_id)){
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }

        $mobile = BaseRequestHelps::post('mobile', '', '');
        if(empty($mobile)){
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }else{
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }

        $model = GameUserItem::find()->select(['item_number'])->where(['status'=>1,'delete_flag'=>0,'item_id'=>$item_id,'mobile'=>$mobile])->asArray()->one();
        if($model['item_number'] > 0)
        {
            $list = $model['item_number'] - 1;
            $sql =  "UPDATE i500_game_user_item SET item_number= '$list'  WHERE mobile='$mobile' AND item_id='$item_id' AND delete_flag=0";
            $res = \Yii::$app->db_social->createCommand($sql)->execute(); 
			$query = array();
            $query = GameUserItem::find()->select(['item_number'])->where(['status'=>1,'delete_flag'=>0,'item_id'=>$item_id,'mobile'=>$mobile])->asArray()->one();       
        }else{
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        return $this->returnJsonMsg('200', $query, Common::C('code', '200'));
    }
	
	/**
	*用户游戏退出接口
	*@param int mobile 用户手机号 
	*@return
	**/
	public function actionGameExit()
	{
		$mobile = BaseRequestHelps::post('mobile', '', '');
		if(empty($mobile)){
			$this->returnJsonMsg('604', [], Commmon::C('code', '604'));
		}else{
			if(!Common::validateMobile($mobile)){
				$this->returnJsonMsg('605', [], Common::C('code', '605'));
			}
		}
	
		$sql =  "UPDATE i500_game_user_item SET is_exit = 1 WHERE mobile='$mobile' AND delete_flag=0";
		$res = \Yii::$app->db_social->createCommand($sql)->execute(); 
		
		return $this->returnJsonMsg('200', [], Common::C('code', '200'));
	}
}