<?php
namespace frontend\modules\v7\controllers;

///use frontend\controllers\RestController;
use frontend\models\i500_social\Recruit;
use frontend\models\i500m\Community;
use Yii;
use common\helpers\Common;
use common\helpers\SsdbHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\ServiceSetting;
use frontend\models\i500_social\UserBasicInfo;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;



class UserinfoController extends BaseController
{
    
 //用户的地址信息集合
	 public function actionShowaddress()
	 {
		$usermobile=RequestHelper::post('mobile', '', '');
		 if(empty($usermobile))
		 {
			$this->returnJsonMsg('4005',[], '手机号不能为空');	
		 }
		$connection = \Yii::$app->db_social;
		$command = $connection->createCommand("select id,consignee,sex,consignee_mobile,search_address,details_address,create_time from i500_user_address where mobile='$usermobile' and is_deleted='2' order by id desc");
		$res=$command->queryAll();
		$this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));	
	 }
	 
	 //添加联系人
	 public function actionAddcon()
	 {
		 $usermobile=RequestHelper::post('mobile', '', '');
		 $aid=RequestHelper::post('aid', '', '');
		 if(empty($usermobile))
		 {
			$this->returnJsonMsg('4005',[], '手机号不能为空');	
		 }
		 $contact=RequestHelper::post('contact', '', '');
		 $sex=RequestHelper::post('sex', '', '');
		 $conmob=RequestHelper::post('conmob', '', '');
		 $sh_address=RequestHelper::post('sh_address', '', '');
		 $dt_address=RequestHelper::post('dt_address', '', '');
		 $create_time=RequestHelper::post('create_time', '', '');
		 $connection = \Yii::$app->db_social;
		 if(empty($aid))
		 {
		 	$res=$connection->createCommand()->insert('i500_user_address', ['mobile' => $usermobile,'consignee'=>$contact,'sex'=>$sex,'consignee_mobile'=>$conmob,'search_address'=>$sh_address,'details_address'=>$dt_address])->execute();
                        $lastinsertid = \Yii::$app->db_social->getLastInsertID();
          $this->returnJsonMsg('200', $lastinsertid, Common::C('code','200','data','[]'));	
		 }
		 else
		 {
			 $res=$connection->createCommand("update i500_user_address set mobile='$usermobile',consignee='$contact',sex='$sex',consignee_mobile='$conmob',search_address='$sh_address',details_address='$dt_address',update_time='$create_time' where id='$aid'")->execute();
          $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));	
		 }
	 }
	 //删除联系人
	 public function actionDeleteaddress()
	 {
		$usermobile=RequestHelper::post('mobile', '', '');
		$aid=RequestHelper::post('aid', '', '');
		 if(empty($usermobile))
		 {
			$this->returnJsonMsg('4005',[], '手机号不能为空');	
		 }
		
		$connection = \Yii::$app->db_social;
		$command = $connection->createCommand("UPDATE i500_user_address SET is_deleted=1 WHERE id='$aid' and mobile='$usermobile'");
		$res=$command->execute();
		if($res==1)
		{
			$this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
		}
	 }
}
?>