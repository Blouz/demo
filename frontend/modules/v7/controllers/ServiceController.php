<?php
/**
 * 服务,需求
 *
 * PHP Version 7
 *

 */
namespace frontend\modules\v7\controllers;

///use frontend\controllers\RestController;
use frontend\models\i500_social\Recruit;
use frontend\models\i500m\Community;
use Yii;
use common\helpers\Common;
use common\helpers\SsdbHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\Seek;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\ServiceSetting;
use frontend\models\i500_social\UserBasicInfo;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use frontend\models\i500_social\ServiceOrder;
use yii\db\Query;
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
	
                $soncate_id=RequestHelper::post('soncate_id', '', '');
		
		$category_name=ServiceCategory::find()->Where(['id'=>$cate_id])->one();
		$cate_name=$category_name['name'];
		
                
                $serv = new Service();
                $field=array();
                $field[]='i500_service.id';
                $field[]='i500_service.mobile';
                $field[]='i500_service.image';
                $field[]='i500_service.price';
                $field[]='i500_service.title';
                $field[]='i500_service.create_time';
                $field[]='i500_service.remark';
                $field[]='i500_service.description as des';
                $field[]='i500_user_basic_info.nickname as rname';
                $field[]='i500_user_basic_info.avatar as icon';
                $field[]='i500_user_basic_info.personal_sign as sign';
                $field[]='i500_user_basic_info.backimg as backimg';
                
                
                $goodat = (new Query())->select('count(id)')->from('i500_service');
                $field['goodat'] = $goodat->where('mobile=i500_service.mobile and status=1 and is_deleted=2 and audit_status=2 and category_id="$cate_id" and community_city_id="$city_id" and community_id="$comm_id"');
                
                $de = (new Query())->select('count(id)')->from('i500_user_order'); 
                $field['deal'] = $de->where('shop_mobile=i500_service.mobile AND pay_status=1 AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)<=pay_time');
                
                $star = (new Query())->select('AVG(i500_service_order_evaluation.star)')->from('i500_user_order')->join('INNER JOIN','i500_service_order_evaluation','i500_service_order_evaluation.order_sn=i500_user_order.order_sn');
                $field['star'] = $star->where('i500_user_order.shop_mobile=i500_service.mobile');
                
                $eva = (new Query())->select('count(i500_service_order_evaluation.id)')->from('i500_user_order')->join('INNER JOIN','i500_service_order_evaluation','i500_service_order_evaluation.order_sn=i500_user_order.order_sn');
                $field['eva'] = $eva->where('i500_user_order.shop_mobile=i500_service.mobile');
                
                $total=(new Query())->select('sum(total)/count(id)')->from('i500_user_order');
                $field['avrg'] = $total->where('shop_mobile=i500_service.mobile AND pay_status=1 AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)<=pay_time');
                
                $condition[Service::tableName().'.category_id'] = $cate_id;
                $condition[Service::tableName().'.community_city_id'] = $city_id;
                $condition[Service::tableName().'.community_id'] = $comm_id;
                $condition[Service::tableName().'.status'] = '1';
                $condition[Service::tableName().'.is_deleted'] = '2';
                $condition[Service::tableName().'.audit_status'] = '2';
                if(!empty($userinfo))
                {
                    $condition[Service::tableName().'.mobile'] = $userinfo;
                }
                if(!empty($soncate_id))
		{
                    $condition[Service::tableName().'.son_category_id'] = $soncate_id;
		}
                
                $result = $serv->find()->select($field)
                                ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_service.mobile')
                                ->andwhere($condition)
                                ->orderBy('i500_service.id DESC')
                                ->offset($page)
                                ->limit(10)
                                ->asArray()
                                ->all();
               
		
		$this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));	
	}
    //技能者中心
    public function actionSkill()
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
	    
		
		$userinfo=RequestHelper::post('usermobile', '', '');//当前登录用户手机号
		

                $category_name=ServiceCategory::find()->Where(['id'=>$cate_id])->one();
		$cate_name=$category_name['name'];
		
                $serv = new Service();
                $field=array();
                $field[]='i500_service.id';
                $field[]='i500_service.mobile';
                $field[]='i500_service.image';
                $field[]='i500_service.price';
                $field[]='i500_service.title';
                $field[]='i500_service.remark';                
                $field[]='i500_service.description as des';
                $field[]='i500_user_basic_info.backimg as backimg';
                $field[]='i500_user_basic_info.nickname as rname';
                $field[]='i500_user_basic_info.avatar as icon';
                $field[]='i500_user_basic_info.personal_sign as sign';
                $field[]='i500_user_basic_info.backimg as backimg';
                
                $goodat = (new Query())->select('count(id)')->from('i500_service');
                $field['goodat'] = $goodat->where('mobile=i500_service.mobile and status=1 and is_deleted=2 and audit_status=2 and category_id="$cate_id" and community_city_id="$city_id" and community_id="$comm_id"');
                
                $de = (new Query())->select('count(id)')->from('i500_user_order'); 
                $field['deal'] = $de->where('shop_mobile=i500_service.mobile AND pay_status=1 AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)<=pay_time');
                
                $star = (new Query())->select('AVG(i500_service_order_evaluation.star)')->from('i500_user_order')->join('INNER JOIN','i500_service_order_evaluation','i500_service_order_evaluation.order_sn=i500_user_order.order_sn');
                $field['star'] = $star->where('i500_user_order.shop_mobile=i500_service.mobile');
                
                $eva = (new Query())->select('count(i500_service_order_evaluation.id)')->from('i500_user_order')->join('INNER JOIN','i500_service_order_evaluation','i500_service_order_evaluation.order_sn=i500_user_order.order_sn');
                $field['eva'] = $eva->where('i500_user_order.shop_mobile=i500_service.mobile');
                
                $total=(new Query())->select('sum(total)/count(id)')->from('i500_user_order');
                $field['avrg'] = $total->where('shop_mobile=i500_service.mobile AND pay_status=1 AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)<=pay_time');
                
                $condition[Service::tableName().'.category_id'] = $cate_id;
                $condition[Service::tableName().'.community_city_id'] = $city_id;
                $condition[Service::tableName().'.community_id'] = $comm_id;
                $condition[Service::tableName().'.status'] = '1';
                $condition[Service::tableName().'.is_deleted'] = '2';
                $condition[Service::tableName().'.audit_status'] = '2';
                if(!empty($userinfo))
                {
                    $condition[Service::tableName().'.mobile'] = $userinfo;
                }
                
                
                $result = $serv->find()->select($field)
                                ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_service.mobile')
                                ->andwhere($condition)
                                ->orderBy('i500_service.id DESC')
                                ->asArray()
                                ->all();
                
		
		
		$this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));
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
		
		$son_cate=NULL;
                $soncate_id=RequestHelper::post('soncate_id', '', '');
		
                $seek = new Seek;
                $field=array();
                $field[]='i500_need.id';
                $field[]='i500_need.mobile';
                $field[]='i500_need.image';
                $field[]='i500_need.price';
                $field[]='i500_need.title';
                $field[]='i500_need.sendtime';
                $field[]='i500_need.create_time';
                $field[]='i500_user_basic_info.nickname as rname';
                $field[]='i500_user_basic_info.avatar as icon';
                $field[]='i500_service_category.name as soncate';
                        
                $condition[Seek::tableName().'.category_id'] = $cate_id;
                $condition[Seek::tableName().'.community_city_id'] = $city_id;
                $condition[Seek::tableName().'.community_id'] = $comm_id;
                $condition[Seek::tableName().'.status'] = '1';
                $condition[Seek::tableName().'.is_deleted'] = '2';
                $condition[Seek::tableName().'.is_receive'] = '0';
                
                if(!empty($soncate_id))
		{
                    $condition[Seek::tableName().'.son_category_id'] = $soncate_id;
		}
                        
                        $res= $seek->find()->select($field)
                                ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_need.mobile')
                                ->join('LEFT JOIN', 'i500_service_category','i500_need.son_category_id=i500_service_category.id')
                                ->andwhere($condition)
                                ->orderBy('i500_need.id DESC')
                                ->offset($page)
                                ->limit(10)
                                ->asArray()
                                ->all();
                         $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
			
	}
	
	
	//显示用户评价
	public function actionEva()
	{
		
		$userinfo=RequestHelper::post('usermobile', '', '');		
		$page=RequestHelper::post('page', '', '');
		if($page=="")
		{
                    $page=0;
		}
		$page=$page*10;
		
		
		$sql="select a.create_time,
		a.content,
		b.nickname as rname,
		b.avatar as icon,
		a.star,
		a.create_time 
		from i500_service_order_evaluation as a 
		left join i500_user_basic_info as b 
		on a.mobile=b.mobile 
		left join i500_user_order as c 
		on c.order_sn=a.order_sn 
		where c.shop_mobile='$userinfo' order by a.id desc limit $page,10";
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
		 //$create_time=date('Y-m-d H:i:s',time());
		 
                 
                 $sev = new Service;
                 $sev->image = $img;
                 $sev->mobile = $userinfo;
                 $sev->category_id = $cate_id;
                 $sev->son_category_id = $soncate_id;
                 $sev->price = $price;
                 $sev->unit = $unit;
                 $sev->description = $content;
                 $sev->title = $title;
                 $sev->remark = $remark;
                 $sev->address = $address;
                 $sev->community_city_id = $city_id;
                 $sev->community_id = $comm_id;
                 
                 $isrec = UserBasicInfo::find()->where(['is_recruit'=>1,'mobile'=>$userinfo])->count();
                 if($isrec>0)
                 {
                    $sev->save(false);
                    $res = $sev->primaryKey;
                 
                    $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
                 }
                 else
                 {
                     $this->returnJsonMsg('9899',[], '您还不是服务者，无法发布服务');
                 }

	 }
	//发布需求
	 public function actionNeedpub()
	 {
		 //$connection = \Yii::$app->db_social;
		 
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
		 if(empty($city_id))
		 {
			$this->returnJsonMsg('2001',[], '城市id不能为空');	
		 }
                 $comm_id=RequestHelper::post('comm_id', '', '');  //所属社区id
		 if(empty($comm_id))
		 {
			$this->returnJsonMsg('2002',[], '社区id不能为空');	
		 }
		 $expire_time=RequestHelper::post('exp_date', '', ''); 
		 if(empty($expire_time))
		 {
			$this->returnJsonMsg('2003',[], '有效服务时间不能为空');	
		 }
		 $current_time=date('Y-m-d H:i:s',time());
		 if($expire_time<$current_time)
		 {
			$this->returnJsonMsg('1111',[], '截至时间不能早于当前时间');	
		 }
		 else
		 {
                    $seek = new Seek;
                    $seek->image = $img;
                    $seek->mobile = $userinfo;
                    $seek->category_id = $cate_id;
                    $seek->son_category_id = $soncate_id;
                    $seek->price = $price;
                    $seek->unit = $unit;
                    $seek->title=$title;
                    $seek->description = $title;
                    $seek->address = $address;
                    $seek->community_city_id = $city_id;
                    $seek->community_id = $comm_id;
                    $seek->sendtime = $expire_time;
                    $seek->save(false);
                    $res = $seek->primaryKey;
		 
                    $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));	
		 }
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
		
		
                $result=Seek::find()->select(['id','mobile'])->andwhere(['id'=>$nid,'mobile'=>$usermobile,'is_receive'=>'0'])->asArray()->one();
                $res = $result['id'];
               
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
		

                if(!empty($usermobile))
                {
                    $res=Service::updateAll(['is_deleted'=>1],['id'=>$sid]);
                    if($res==1)
                    {
                        $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
                    }
                }
                else
                {
                    $this->returnJsonMsg('7003',[], '手机号不能为空');
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
		
		if(!empty($usermobile))
                {
                    $res=Service::updateAll(['description'=>$content],['id'=>$sid]);
                    if($res==1)
                    {
                        $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
                    }
                }
                else
                {
                    $this->returnJsonMsg('7003',[], '手机号不能为空');
                }
		
	 }
	 
	 //我的需求
	public function actionNeibneedbyuser()
	{
		$mobile=RequestHelper::post('usermobile', '', '');
		 
			$seek = new Seek;
                        $field=array();
                        $field[]='i500_need.id';
                        $field[]='i500_need.mobile';
                        $field[]='i500_need.image';
                        $field[]='i500_need.price';
                        $field[]='i500_need.description';
                        $field[]='i500_need.status';
                        $field[]='i500_need.create_time';
                        $field[]='i500_user_basic_info.realname as rname';
                        $field[]='i500_user_basic_info.avatar as icon';
                        $field[]='i500_service_category.name as soncate';
                        $res= $seek->find()->select($field)
                                ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_need.mobile')
                                ->join('LEFT JOIN', 'i500_service_category','i500_need.son_category_id=i500_service_category.id')
                                ->andwhere(['i500_need.mobile'=>$mobile,'i500_need.status'=>'1','i500_need.is_deleted'=>'2','i500_need.is_receive'=>'0'])
                                ->orderBy('i500_need.id DESC')
                                ->asArray()
                                ->all();
                         $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));	
                       
	}
	//修改个人签名
	public function actionEditdesc()
	{
		$usermobile=RequestHelper::post('usermobile', '', '');
		 
		$content=RequestHelper::post('content', '', '');
		
                if(!empty($usermobile))
                {
                    $res=UserBasicInfo::updateAll(['personal_sign'=>$content],['mobile'=>$usermobile]);
                   
                    if($res==1)
                    {
                        $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
                    }
                }
                else
                {
                    $this->returnJsonMsg('7003',[], '手机号不能为空');
                }
		
	}
	//对订单进行评价
	public function actionAddeva()
	{
		$mobile=RequestHelper::post('mobile', '', '');
		if(empty($mobile))
		{
                    $this->returnJsonMsg('10006',[], '手机号不能为空');	
		}
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
                    $update_userorder = $connection->createCommand("UPDATE i500_user_order SET user_comment_status='1' WHERE order_sn='$order_sn'")->execute();
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
