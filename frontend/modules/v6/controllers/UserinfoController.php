<?php
namespace frontend\modules\v6\controllers;

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
    public $modelClass = 'frontend\models\i500_social\Service';

    
 //用户的地址信息集合
	 public function actionShowaddress()
	 {
		$usermobile=RequestHelper::post('mobile', '', '');
		 if(empty($usermobile))
		 {
			$this->returnJsonMsg('4005',[], '手机号不能为空');	
		 }
		$connection = \Yii::$app->db_social;
		$command = $connection->createCommand("select id,consignee,sex,consignee_mobile,search_address,details_address,create_time from i500_user_address where mobile='$usermobile'");
		$res=$command->queryAll();
		$this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));	
	 }
	 
	 //添加联系人
	 public function actionAddcon()
	 {
		 $usermobile=RequestHelper::post('mobile', '', '');
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
		 $res=$connection->createCommand()->insert('i500_user_address', ['mobile' => $usermobile,'consignee'=>$contact,'sex'=>$sex,'consignee_mobile'=>$conmob,'search_address'=>$sh_address,'details_address'=>$dt_address,'create_time'=>$create_time])->execute();
          $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));	
	 }
}
?>