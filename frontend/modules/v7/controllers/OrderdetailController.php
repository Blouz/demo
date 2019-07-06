<?php
/**
 * 服务,需求订单
 *
 * PHP Version 6
 *

 */
namespace frontend\modules\v7\controllers;

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
use frontend\models\i500_social\ServiceOrder;
use frontend\models\i500_social\UserOrder;


class OrderdetailController extends BaseController
{
	
	public function getResult($sql)
	{
		$connection = \Yii::$app->db_social;
		
		$command = $connection->createCommand($sql);
		$result = $command->queryAll();
		if(!connection_aborted())
		{
			$this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));		
		}
		else
		{
			$this->returnJsonMsg('10054',[], '连接中断');	
		}
	}

 
 //服务订单
	 public function actionServorderlist()
	 {
		 $mobile=RequestHelper::post('usermobile', '', '');
		 if(empty($mobile))
		 {
			$this->returnJsonMsg('4010',[], '手机号不能为空');	
		 }
		 $page=RequestHelper::post('page', '', '');        //列表起始位置
			$page=$page*10;
			if($page=="")
			{
				$page=0;
			}
		 //$order_type=RequestHelper::post('order_type', '', '');
		 $sql="select a.id, 
		 a.order_sn, 
		 a.mobile,
		 a.shop_mobile, 
		 a.total, 
		 a.pay_status, 
		 a.status, 
		 a.remark, 
		 a.create_time, 
		 a.community_city_id, 
		 a.community_id,
		 a.order_info,
		 a.order_type,
		 a.pay_method,
		 a.service_comment_status,
		 a.user_comment_status,
		 b.nickname as nickname,
		 b.avatar as icon 
		 from i500_user_order as a
		 left join i500_user_basic_info as b
		 on a.mobile=b.mobile
		 where a.shop_mobile='$mobile' and a.status<>3 order by a.id desc limit $page,10";
		 $connection = \Yii::$app->db_social;
		
		 $command = $connection->createCommand($sql);
		 $result = $command->queryAll();
		 $orderdata=array();
		 foreach($result as $res)
		 {
			$obj=array();
			$obj['id']=$res['id'];
			$obj['order_sn']=$res['order_sn'];
			$obj['mobile']=$res['mobile'];
			$obj['shop_mobile']=$res['shop_mobile'];
			$obj['total']=$res['total'];
			$obj['pay_status']=$res['pay_status'];
			$obj['status']=$res['status'];
			$obj['remark']=$res['remark'];
			$obj['create_time']=$res['create_time'];
			$obj['community_city_id']=$res['community_city_id'];
			$obj['community_id']=$res['community_id'];
			$obj['order_type']=$res['order_type'];
			$obj['pay_method']=$res['pay_method'];
			$obj['service_comment_status']=$res['service_comment_status'];
			$obj['user_comment_status']=$res['user_comment_status'];
			$obj['nickname']=$res['nickname'];
			$obj['icon']=$res['icon'];	
			$obj['order_info']=json_decode($res['order_info']);
			$orderdata[]=$obj;
			
		 }
		 $this->returnJsonMsg('200', $orderdata, Common::C('code','200','data','[]'));
	 }
	//需求订单列表
	 public function actionSeekorderlist()
	 {
		 $mobile=RequestHelper::post('usermobile', '', '');
		 if(empty($mobile))
		 {
			$this->returnJsonMsg('4010',[], '手机号不能为空');	
		 }
		 $page=RequestHelper::post('page', '', '');        //列表起始位置
		 $page=$page*10;
		 if($page=="")
		 {
			$page=0;
		 }
		 
		 $sql="select a.id, 
		 a.order_sn, 
		 a.mobile,
		 a.shop_mobile, 
		 a.total, 
		 a.pay_status, 
		 a.status, 
		 a.remark, 
		 a.create_time, 
		 a.community_city_id, 
		 a.community_id,
		 a.order_info,
		 a.order_type,
		 a.pay_method,
		 a.service_comment_status,
		 a.user_comment_status,
		 b.nickname as nickname,
		 b.avatar as icon 
		 from i500_user_order as a
		 left join i500_user_basic_info as b
		 on a.shop_mobile=b.mobile
		 where a.mobile='$mobile' and a.status<>3 order by a.id desc limit $page,10";
		 $connection = \Yii::$app->db_social;
		
		 $command = $connection->createCommand($sql);
		 $result = $command->queryAll();
		 $orderdata=array();
		 foreach($result as $res)
		 {
			$obj=array();
			$obj['id']=$res['id'];
			$obj['order_sn']=$res['order_sn'];
			$obj['mobile']=$res['mobile'];
			$obj['shop_mobile']=$res['shop_mobile'];
			$obj['total']=$res['total'];
			$obj['pay_status']=$res['pay_status'];
			$obj['status']=$res['status'];
			$obj['remark']=$res['remark'];
			$obj['create_time']=$res['create_time'];
			$obj['community_city_id']=$res['community_city_id'];
			$obj['community_id']=$res['community_id'];
			$obj['order_type']=$res['order_type'];
			$obj['pay_method']=$res['pay_method'];
			$obj['service_comment_status']=$res['service_comment_status'];
			$obj['user_comment_status']=$res['user_comment_status'];
			$obj['nickname']=$res['nickname'];
			$obj['icon']=$res['icon'];	
			$obj['order_info']=json_decode($res['order_info']);
			$orderdata[]=$obj;
			
		 }
		 $this->returnJsonMsg('200', $orderdata, Common::C('code','200','data','[]'));
	 }
	
}

?>