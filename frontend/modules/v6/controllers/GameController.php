<?php
/**
 * 钱包
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Wallet
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      2016/8/16
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */
namespace frontend\modules\v6\controllers;

use frontend\models\i500_social\GameCommunity;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\GameActivity;
use frontend\models\i500_social\GameGift;
use frontend\models\i500_social\GameActivityRecord;
use frontend\models\i500_social\GameUserDetail;
use frontend\models\i500_social\GameActivityPrice;
/**
 * GAME
 *
 * @category Social
 * @package  GAME
 * @author   liuyanwei <liuyanwei@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     liuyanwei@i500m.com
 */
class GameController extends BaseController
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
     * 活动参与次数
     * @return array
     */
    public function actionGetCount(){
    	$mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $activity_id = RequestHelper::post('activity_id', '', '');
        if (empty($activity_id)) {
            $this->returnJsonMsg('1111', [], '活动id不能为空');
        }
        
    	//查找活动
        $activity_info = GameActivity::find()->select(['begin_time','end_time','join_count'])->where(['id'=>$activity_id,'status'=>1,'delete_flag'=>0])->asArray()->one();
        if (empty($activity_info)) {
            $this->returnJsonMsg('1112', [], '未知活动');
        }
        //中奖记录
        $use_count = GameActivityRecord::find()->select(['id'])->where(['activity_id'=>$activity_id,'mobile'=>$mobile])->andWhere(['between','create_time',date("Y-m-d 00:00:00",time()),date("Y-m-d 23:59:59",time())])->asArray()->count();
        $data['join_count'] = $activity_info['join_count'];
        $data['use_count'] = $use_count;
        $this->returnJsonMsg('200', $data, Common::C('code', '200'));
    }

    /**
     * 抽奖
     * @return array
     */
    public function actionGet()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $activity_id = RequestHelper::post('activity_id', '', '');
        if (empty($activity_id)) {
            $this->returnJsonMsg('1111', [], '活动id不能为空');
        }

        $community_id = RequestHelper::post('community_id', '', '');
        if (empty($community_id)) {
            $this->returnJsonMsg('642', [], Common::C('code','642'));
        }
    	//查找活动
        $activity_info = GameActivity::find()->select(['begin_time','end_time','join_count'])->where(['id'=>$activity_id,'status'=>1,'delete_flag'=>0])->asArray()->one();
        if (empty($activity_info)) {
            $this->returnJsonMsg('1112', [], '未知活动');
        }

        $hour = (int)date('H',time());
        if(((int)$activity_info['begin_time'] > $hour) || ((int)$activity_info['end_time'] <= $hour)){
            $this->returnJsonMsg('1113', [], '活动未在开放时间');
        }

        $use_count = GameActivityRecord::find()->select(['id'])->where(['activity_id'=>$activity_id,'mobile'=>$mobile])->andWhere(['between','create_time',date("Y-m-d 00:00:00",time()),date("Y-m-d 23:59:59",time())])->asArray()->count();
        if($use_count >= $activity_info['join_count']){
            $this->returnJsonMsg('1114', [], '次数已经用完');
        }
        //查询小区人口
        $people_total = GameCommunity::find()->select(['community_population'])->where(['id'=>$community_id])->asArray()->one();//->andWhere(['>', 'level', 0])
		$prize_arr = [];
        $winCount = GameActivityRecord::find()->select(['id'])->where(['activity_id'=>$activity_id,'mobile'=>$mobile,'community_id'=>$community_id,'delete_flag'=>0])->andWhere(['>', 'price_class', 0])->andWhere(['between','create_time',date("Y-m-d 00:00:00",time()),date("Y-m-d 23:59:59",time())])->asArray()->one();
        $begin_num = 1;
        $relationList = GameActivityPrice::find()->select(['id','price_class','price_probability','price_number','price_id'])->where(['activity_id'=>$activity_id,'community_id'=>$community_id,'delete_flag'=>0])->asArray()->all();
        $end_num = $people_total['community_population']*$activity_info['join_count'];
        if(empty($winCount)){
            foreach ($relationList as $k => $v) {
                $arr = [];
                $arr['id'] = $v['id'];
                $arr['price_id'] = $v['price_id'];
                $arr['price_class'] = $v['price_class'];
                $arr['price_number'] = $v['price_number'];
                $arr['begin_num'] = $begin_num;
                $arr['end_num'] = $begin_num+$v['price_probability']*$end_num;
                $begin_num = $arr['end_num'];
                $giftinfo = GameGift::find()->select(['name'])->where(['id'=>$v['price_id']])->asArray()->one();
                $arr['name'] = $giftinfo['name'];
                $prize_arr[] = $arr;
            }
        }
        $arr = array(
        	'id'=>'0',
        	'price_class'=> '0',
            'price_id'=> '0',
        	'name'=>'再接再厉'
        );
        $arr['begin_num'] = $begin_num;
        $arr['end_num'] = $end_num;
        $prize_arr[] = $arr;
        var_dump($arr);exit;
		$ret = $this->_getRand($prize_arr,$end_num); //根据概率获取奖项
		if($ret['price_class'] != 0){
        	$giftCount = GameActivityRecord::find()->select(['id'])->where(['activity_id'=>$activity_id,'price_class'=>$ret['price_class']])->count();
            //判断奖励是否已经领完
        	if($giftCount >= $ret['price_number']){
        		$ret = $prize_arr[count($prize_arr)-1];
        	}
		}

		$gameRecord = new GameActivityRecord();
		$game_record['mobile']  = $mobile;
        $game_record['activity_id']  = $activity_id;
        $game_record['community_id']  = $community_id;
        $game_record['price_id'] = $ret['price_id'];
        $game_record['price_class'] = $ret['price_class'];
        $add_rs = $gameRecord->insertInfo($game_record);
        if(!$add_rs){
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $ret['record_id'] = $add_rs;
        $this->returnJsonMsg('200', $ret, Common::C('code', '200'));
    }

    /**
     * 抽中奖项
     * @param array
     * @param int
     * @return array
     */
    private function _getRand($proArr,$num)
    {  
    	$result = [];
	    //概率数组的总概率精度 
   	    $proSum = $num;  
	    //概率数组循环 
	    $rand_num = mt_rand(1, $proSum);
	    foreach ($proArr as $k => $v) {
			if($v['begin_num'] <= $rand_num && $v['end_num'] > $rand_num){
				$result = $proArr[$k];
				break;
			}
		}
   		return $result;
    }

    /**
     * 保存中奖者信息
     * @return array
     */
    public function actionCreateUserDetail()
	{
		$mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $contact_mobile = RequestHelper::post('contact_mobile', '', '');
        if (empty($contact_mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $record_id = RequestHelper::post('record_id', '', '');
        if (empty($record_id)) {
            $this->returnJsonMsg('1111', [], 'record_id不能为空');
        }

        $user_name = RequestHelper::post('user_name', '', '');
        if (empty($user_name)) {
            $this->returnJsonMsg('1112', [], '姓名不能为空');
        }

        $user_address = RequestHelper::post('user_address', '', '');
        if (empty($user_address)) {
            $this->returnJsonMsg('1113', [], '地址不能为空');
        }

        $userinfo1 = GameUserDetail::findOne($record_id);
        if($userinfo1){
            $this->returnJsonMsg('4001',[],'数据已经添加');
        }

		$userinfo = new GameUserDetail();
		$userinfo['record_id']  = $record_id;
        $userinfo['mobile']  = $mobile;
        $userinfo['user_name'] = $user_name;
        $userinfo['user_address'] = $user_address;
        $re= $userinfo->save();

        if (!$re) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
	}
}
