<?php
/**
 * 服务,需求
 *
 * PHP Version 6
 *

 */
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
use frontend\models\i500_social\ServiceOrder;
/**
 * Service
 *
 * @category Social
 * @package  Service

 */
class ServiceController extends BaseController
{
    public $modelClass = 'frontend\models\i500_social\Service';

    
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
  
	//分类服务列表展示··服务详情
    public function actionNeibserv()
    {
		$connection = \Yii::$app->db_social;
		
		
		$cate_id=RequestHelper::post('cate_id', '', ''); //分类id
		if(empty($cate_id))
		{
			$this->returnJsonMsg('1000',[], '分类id不能为空');	
		}
		$city_id=RequestHelper::post('city_id', '', ''); //所属城市id
		if(empty($city_id))
		{
			$this->returnJsonMsg('2001',[], '城市id不能为空');	
		}
	    $comm_id=RequestHelper::post('comm_id', '', '');  //所属社区id
		if(empty($comm_id))
		{
			$this->returnJsonMsg('2002',[], '社区id不能为空');	
		}
        $page=RequestHelper::post('page', '', '');        //列表起始位置
		$page=$page*10;
		if($page=="")
		{
			$page=0;
		}
		$user=NULL;
		
		$userinfo=RequestHelper::post('usermobile', '', '');
	
		if(!empty($userinfo))
		{
			$user=" and a.mobile='$userinfo'";
		}
		$son_cate="";
	    $soncate_id=RequestHelper::post('soncate_id', '', '');
		if(!empty($soncate_id))
		{
			$son_cate=" and a.son_category_id='$soncate_id'";
		}
		$category_name=ServiceCategory::find()->Where(['id'=>$cate_id])->one();
		$cate_name=$category_name['name'];
		
		$sql="SELECT 
		a.id,
		a.mobile,
		a.image,
		a.title,
		a.price,
		a.create_time,
		a.description as des,
		a.remark,
		b.backimg as backimg,
		b.nickname AS rname,
		b.personal_sign as sign,
		b.avatar AS icon
		FROM i500_service AS a 
		LEFT JOIN i500_user_basic_info AS b 
		ON a.mobile=b.mobile 	
		WHERE a.category_id='$cate_id' AND a.community_city_id='$city_id' AND a.community_id='$comm_id' AND a.is_deleted='2' AND a.audit_status='2' AND a.status='1' ".$son_cate.$user." order by a.id desc limit $page,10 ";
		
        $result= $connection->createCommand($sql)->queryAll();
		$service_info=array();
		foreach($result as $res)
		{
			$data=array();
			$data['id']=$res['id'];
			$data['mobile']=$res['mobile'];
			$data['price']=$res['price'];
			$data['image']=$res['image'];
			$data['title']=$res['title'];
			$data['create_time']=$res['create_time'];			
			$data['des']=$res['des'];
			$data['remark']=$res['remark'];
			$data['backimg']=$res['backimg'];
			$data['rname']=$res['rname'];
			$data['sign']=$res['sign'];
			$data['icon']=$res['icon'];
			$data['title']=$res['title'];
			$data['category_name']=$cate_name;
			$mobile=$res['mobile'];
			$sid=$res['id'];
			$data['goodat']=Service::find()->andWhere(['mobile' => $mobile, 'status' => '1','is_deleted'=>'2','audit_status'=>'2','category_id'=>$cate_id,'community_city_id'=>$city_id,'community_id'=>$comm_id])->count(); 
			$deal=$connection->createCommand("select count(id) from i500_service_order where service_mobile='$mobile' AND pay_status='1' AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)<=pay_time")->queryScalar();
			$data['deal']=$deal;
			$data['star']=$connection->createCommand("select AVG(e.star) from i500_service_order as d inner join i500_service_order_evaluation as e on e.order_sn=d.order_sn where d.service_id='$sid'")->queryScalar();
			$data['eva']=$connection->createCommand("select COUNT(e.id) from i500_service_order as d inner join i500_service_order_evaluation as e on e.order_sn=d.order_sn where d.service_id='$sid' and e.star>3")->queryScalar();
			$total=$connection->createCommand("select sum(total) from i500_service_order where pay_status='1' AND service_mobile='$mobile' AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)<=pay_time")->queryScalar();
			if($total==NULL||$total==0)
			{
				$avg="0";
			}
			else
			{
				$avg=$total/$deal;
			}
			$data['avrg']=$avg;
			$service_info[]=$data;
		}
		//var_dump($service_info);
		//$this->getResult($sql);
		$this->returnJsonMsg('200', $service_info, Common::C('code','200','data','[]'));	
	}
    //技能者中心
	 public function actionSkill()
    {
		$cate_id=RequestHelper::post('cate_id', '', ''); //分类id
		if(empty($cate_id))
		{
			$this->returnJsonMsg('1000',[], '分类id不能为空');	
		}
		$city_id=RequestHelper::post('city_id', '', ''); //所属城市id
		if(empty($city_id))
		{
			$this->returnJsonMsg('2001',[], '城市id不能为空');	
		}
	    $comm_id=RequestHelper::post('comm_id', '', '');  //所属社区id
		if(empty($comm_id))
		{
			$this->returnJsonMsg('2002',[], '社区id不能为空');	
		}
	    
		$user=NULL;
		
		$userinfo=RequestHelper::post('usermobile', '', '');//当前登录用户手机号
		
		if(!empty($userinfo))
		{
			$user=" and a.mobile='$userinfo'";
		}
		
		$sql="SELECT 
		a.id,
		a.mobile,
		a.image,
		a.title,
		a.price,
		a.remark,
		b.backimg,
		b.nickname AS rname,
		b.personal_sign as sign,
		b.avatar AS icon,
		b.backimg,
		(SELECT count(id) FROM i500_service WHERE mobile=a.mobile AND status=1 AND is_deleted='2' AND audit_status='2' AND category_id='$cate_id' AND community_city_id='$city_id' AND community_id='$comm_id') AS goodat,
		(SELECT count(id) FROM i500_service_order WHERE service_mobile=a.mobile AND pay_status='1' AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)<=pay_time) AS deal,
		(SELECT AVG(e.star) FROM i500_service_order as d inner join i500_service_order_evaluation as e on e.order_sn=d.order_sn where d.service_id=a.id) as star,
		(SELECT COUNT(e.id) FROM i500_service_order as d inner join i500_service_order_evaluation as e on e.order_sn=d.order_sn where d.service_id=a.id and e.star>3) as eva,
		(select sum(total)/count(id) from i500_service_order where pay_status='1' AND service_id=a.id AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)<=pay_time) as avrg 
		FROM i500_service AS a 
		LEFT JOIN i500_user_basic_info AS b 
		ON a.mobile=b.mobile 
		
		WHERE a.category_id='$cate_id' AND a.community_city_id='$city_id' AND a.community_id='$comm_id' AND a.is_deleted='2' AND a.audit_status='2' AND a.status='1' ".$user;
        
		$this->getResult($sql);
	}
	
	//分类需求列表展示
    public function actionNeibneed()
	{
		$cate_id=RequestHelper::post('cate_id', '', ''); //分类id
		if(empty($cate_id))
		{
			$this->returnJsonMsg('1000',[], '分类id不能为空');	
		}
		$city_id=RequestHelper::post('city_id', '', ''); //所属城市id
		if(empty($city_id))
		{
			$this->returnJsonMsg('2001',[], '城市id不能为空');	
		}
	    $comm_id=RequestHelper::post('comm_id', '', '');  //所属社区id
		if(empty($comm_id))
		{
			$this->returnJsonMsg('2002',[], '社区id不能为空');	
		}
		$page=RequestHelper::post('page', '', ''); 
		if($page=="")
		{
			$page=0;
		}
		$page=$page*10;
		
		$son_cate="";
	    $soncate_id=RequestHelper::post('soncate_id', '', '');
		if(!empty($soncate_id))
		{
			$son_cate=" and a.son_category_id='$soncate_id'";
		}
		
		$sql="SELECT 
		    a.id,
			a.mobile,
			a.image,
			a.price,
			a.title,
			a.create_time,
			b.nickname AS rname,
			b.avatar AS icon,
			c.name AS soncate 
			FROM i500_need AS a 
			LEFT JOIN i500_user_basic_info AS b 
			ON a.mobile=b.mobile 
			LEFT JOIN i500_service_category AS c 
			ON a.id=c.id 
			WHERE a.category_id='$cate_id' AND community_city_id='$city_id' AND community_id='$comm_id' AND a.status='1' AND a.is_deleted='2' AND is_receive='0'".$son_cate." order by a.id desc limit $page,10 ";
			
			$this->getResult($sql);
	}
	
	
	//显示用户评价
	public function actionEva()
	{
		
		$userinfo=RequestHelper::post('usermobile', '', '');		
		
		$sql="select a.create_time,
		a.content,
		b.nickname as rname,
		b.avatar as icon,
		a.star 
		from i500_service_order_evaluation as a 
		left join i500_user_basic_info as b 
		on a.mobile=b.mobile 
		left join i500_user_order as c 
		on c.order_sn=a.order_sn 
		where c.shop_mobile='$userinfo'";
		$connection = \Yii::$app->db_social;
		$command = $connection->createCommand($sql);
		$result = $command->queryAll();	
		$res=array();
		$res['eva']=$result;
		
		if(!connection_aborted())
		{
			$this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));		
		}
	}
	
    //发布服务
	 public function actionSrvpub()
	 {
		 
		 $userinfo=RequestHelper::post('mobile', '', '');
		 
		 $img=RequestHelper::post('img', '', '');
		 if(empty($img))
		 {
			$this->returnJsonMsg('3000',[], '图片url不能为空');	
		 }
		 
		 $cate_id=RequestHelper::post('category_id', '', '');
		 if(empty($cate_id))
		 {
			$this->returnJsonMsg('1000',[], '分类id不能为空');	
		 }
		 
		 $soncate_id=RequestHelper::post('soncate_id', '', '');
		 if(empty($soncate_id))
		 {
			$this->returnJsonMsg('1001',[], '子分类id不能为空');	
		 }
		 $price=RequestHelper::post('price', '', '');
		 if(empty($price))
		 {
			$this->returnJsonMsg('1004',[], '价格不能为空');	
		 }
		 $title=RequestHelper::post('title', '', '');
		 if(empty($title))
		 {
			$this->returnJsonMsg('1005',[], '服务标题不能为空');	
		 }
		 $unit=RequestHelper::post('unit', '', '');
		 
		 $content=RequestHelper::post('content', '', '');
		  
		 $remark=RequestHelper::post('remark', '', '');
		 
		 $address=RequestHelper::post('address', '', '');
		$city_id=RequestHelper::post('city_id', '', ''); //所属城市id
		if(empty($city_id))
		{
			$this->returnJsonMsg('2001',[], '城市id不能为空');	
		}
	    $comm_id=RequestHelper::post('comm_id', '', '');  //所属社区id
		if(empty($comm_id))
		{
			$this->returnJsonMsg('2002',[], '社区id不能为空');	
		}
		 $create_time=date('Y-m-d H:i:s',time());
		 $connection = \Yii::$app->db_social;
		 $res=$connection->createCommand()->insert('i500_service', ['image' => $img,'mobile' => $userinfo,'category_id'=>$cate_id,'son_category_id'=>$soncate_id,'price'=>$price,'unit'=>$unit,'description'=>$content,'title'=>$title,'remark'=>$remark,'address'=>$address,'create_time'=>$create_time,'community_city_id'=>$city_id,'community_id'=>$comm_id])->execute();
		  $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));

	 }
	//发布需求
	 public function actionNeedpub()
	 {
		 $userinfo=RequestHelper::post('mobile', '', '');
		 
		 $img=RequestHelper::post('img', '', '');
		 if(empty($img))
		 {
			$this->returnJsonMsg('3000',[], '图片url不能为空');	
		 }
		 
		 $cate_id=RequestHelper::post('category_id', '', '');
		 if(empty($cate_id))
		 {
			$this->returnJsonMsg('1000',[], '分类id不能为空');	
		 }
		 
		 $soncate_id=RequestHelper::post('soncate_id', '', '');
		 if(empty($soncate_id))
		 {
			$this->returnJsonMsg('1001',[], '子分类id不能为空');	
		 }
		 $price=RequestHelper::post('price', '', '');
		 if(empty($price))
		 {
			$this->returnJsonMsg('1004',[], '价格不能为空');	
		 }
		 $title=RequestHelper::post('title', '', '');
		 if(empty($title))
		 {
			$this->returnJsonMsg('1005',[], '需求标题不能为空');	
		 }
		 $unit=RequestHelper::post('unit', '', '');
		 $address=RequestHelper::post('address', '', '');
		 
		 
		 $city_id=RequestHelper::post('city_id', '', ''); //所属城市id
		 $city_id=RequestHelper::post('city_id', '', ''); //所属城市id
		 if(empty($city_id))
		 {
			$this->returnJsonMsg('2001',[], '城市id不能为空');	
		 }
	     $comm_id=RequestHelper::post('comm_id', '', '');  //所属社区id
		 if(empty($comm_id))
		 {
			$this->returnJsonMsg('2002',[], '社区id不能为空');	
		 }
		 //$create_time=date('Y-m-d H:i:s',time());
		 $connection = \Yii::$app->db_social;
		 $res=$connection->createCommand()->insert('i500_need', ['image' => $img,'mobile' => $userinfo,'category_id'=>$cate_id,'son_category_id'=>$soncate_id,'price'=>$price,'unit'=>$unit,'description'=>$title,'title'=>$title,'address'=>$address,'community_city_id'=>$city_id,'community_id'=>$comm_id])->execute();
          $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));	
	 }
	 
	 //是否允许发布者删除需求
	 public function actionIsdelneed()
	 {
		$nid=RequestHelper::post('nid', '', '');//需求id
		 if(empty($nid))
		 {
			$this->returnJsonMsg('7001',[], '需求id不能为空');	
		 }
		$usermobile=RequestHelper::post('usermobile', '', '');
		
		$connection = \Yii::$app->db_social;
		$command = $connection->createCommand("select id,mobile from i500_need  where id='$nid' and mobile='$usermobile' and is_receive='0'");
		$res=$command->execute();
		//返回空值则不允许删除
		$this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
		
	 }
	 
	 //删除已发布服务信息
	 public function actionDelserv()
	 {
		$sid=RequestHelper::post('sid', '', '');//服务id
		 if(empty($sid))
		 {
			$this->returnJsonMsg('7002',[], '服务id不能为空');	
		 }
		$usermobile=RequestHelper::post('usermobile', '', '');
		
		$connection = \Yii::$app->db_social;
		//执行删除操作
		$command = $connection->createCommand("UPDATE i500_service SET is_deleted='1' WHERE id='$sid' and mobile='$usermobile'");
		$res=$command->execute();
		if($res==1)
		{
			$this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
		}
		
	 }
	 
	 //修改服务描述
	 public function actionEditservice()
	 {
		$sid=RequestHelper::post('sid', '', '');
		if(empty($sid))
		{
			$this->returnJsonMsg('7002',[], '服务id不能为空');	
		}
		$usermobile=RequestHelper::post('usermobile', '', '');
		 
		$content=RequestHelper::post('content', '', '');
		//
		$connection = \Yii::$app->db_social;
		$command = $connection->createCommand("UPDATE i500_service SET description='$content' WHERE id='$sid' and mobile='$usermobile'");
		$res=$command->execute();
		if($res==1)
		{
			$this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
		}
		
	 }
	 
	 //我的需求
	public function actionNeibneedbyuser()
	{
		$usermobile=RequestHelper::post('usermobile', '', '');
		 
		
		$sql="SELECT 
		    a.id,
			a.mobile,
			a.image,
			a.price,
			a.description,
			a.status,
			a.create_time,
			b.realname AS rname,
			b.avatar AS icon,
			c.name AS soncate 
			FROM i500_need AS a 
			LEFT JOIN i500_user_basic_info AS b 
			ON a.mobile=b.mobile 
			LEFT JOIN i500_service_category AS c 
			ON a.id=c.id 
			WHERE a.mobile='$mobile' AND a.status='1' AND a.is_deleted='2' AND is_receive='0' order by a.id desc ";
			
			$this->getResult($sql);
	}
	//修改个人签名
	public function actionEditdesc()
	{
		$usermobile=RequestHelper::post('usermobile', '', '');
		 
		$content=RequestHelper::post('content', '', '');
		//
		$connection = \Yii::$app->db_social;
		$command = $connection->createCommand("UPDATE i500_user_basic_info SET personal_sign='$content' WHERE  mobile='$usermobile'");
		$res=$command->execute();
		if($res==1)
		{
			$this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
		}
	}
	//对订单进行评价
	public function actionAddeva()
	{
		$mobile=RequestHelper::post('mobile', '', '');
		
		$content=RequestHelper::post('content', '', '');
		$star=RequestHelper::post('star', '', '');
		$type=RequestHelper::post('type', '', '');
		$order_sn=RequestHelper::post('order_sn', '', '');
		 if(empty($order_sn))
		 {
			$this->returnJsonMsg('6000',[], '订单号不能为空');	
		 }
		$create_time=date('Y-m-d H:i:s',time());
		$connection = \Yii::$app->db_social;
		$res=$connection->createCommand()->insert('i500_service_order_evaluation', ['mobile'=>$mobile,'star' => $star,'content'=>$content,'type'=>$type,'create_time'=>$create_time,'order_sn'=>$order_sn])->execute();
		if($res==1)
		{
			$update_serviceorder = $connection->createCommand("UPDATE i500_service_order SET user_evaluation_status='1' WHERE order_sn='$order_sn'")->execute();
			$update_userorder = $connection->createCommand("UPDATE i500_user_order SET user_comment_status='1' WHERE order_sn='$order_sn'")->execute();;
		}
        $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
	}
	//判断用户是否已评论
	public function actionIseva()
	{
		$usermobile=RequestHelper::post('usermobile', '', '');
		 
		
		$order_sn=RequestHelper::post('order_sn', '', '');
		 if(empty($order_sn))
		 {
			$this->returnJsonMsg('6000',[], '订单号不能为空');	
		 }
		$sql="select user_comment_status from i500_user_order where order_sn='$order_sn' and mobile='$usermobile' ";
		$this->getResult($sql);
	}
	
	
    //修改背景图片
	public function actionUpbackimg()
	{
		$usermobile=RequestHelper::post('usermobile', '', '');
		
		$backurl=RequestHelper::post('backurl', '', '');
		$connection = \Yii::$app->db_social;
		$update_backimg = $connection->createCommand("UPDATE i500_user_basic_info SET backimg='$backurl' WHERE mobile='$usermobile'")->execute();
		if($update_backimg==1)
		{
			$this->returnJsonMsg('200', $update_backimg, Common::C('code','200','data','[]'));
		}
	}
	

	
}
