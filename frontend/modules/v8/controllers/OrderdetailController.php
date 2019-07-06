<?php
/**
 * 服务,需求订单详情及列表
 *
 * PHP Version 8
 *
 * @category  Social
 * @package   Service
 * @author    wangleilei <wangleilei@i500m.com>
 * @time      2016/12/01
 * @copyright 2016 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wangleilei@i500m.com
 */
namespace frontend\modules\v8\controllers;

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
use yii\db\Query;

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
            $mobile = RequestHelper::post('mobile', '', '');
            $type =  RequestHelper::post('type', 0,'intval');
            $status = RequestHelper::post('status', '', '');
            $pay_status = RequestHelper::post('pay_status', '', '');
            $order = RequestHelper::post('order', 0, 'intval');// 0:按创建时间排序 1：按修改时间排序
            $page = RequestHelper::post('page', '', '');        //列表起始位置
            $page = $page*10;
            if($page=="")
            {
		$page=0;
            }
            if($order==0)
            {
                $order_condition = "i500_user_order.id DESC";
            }
            else 
            {
                $order_condition = "i500_user_order.operation_time DESC";
            }
                 $field = array();
                 $field[] = "i500_user_order.id";
                 $field[] = "i500_user_order.order_sn";
                 $field[] = "i500_user_order.mobile";
                 $field[] = "i500_user_order.shop_mobile";
                 $field[] = "i500_user_order.total";
                 $field[] = "i500_user_order.pay_status";
                 $field[] = "i500_user_order.status";
                 $field[] = "i500_user_order.remark";
                 $field[] = "i500_user_order.create_time";
                 $field[] = "i500_user_order.community_city_id";
                 $field[] = "i500_user_order.community_id";
                 $field[] = "i500_user_order.send_time";
//                 $field[] = "i500_user_order.order_info";
                 $field[] = "i500_user_order.order_type";
                 $field[] = "i500_user_order.pay_method";
                 $field[] = "i500_user_order.service_comment_status";
                 $field[] = "i500_user_order.user_comment_status";
                 $field[] = "i500_user_basic_info.nickname as nickname";
                 $field[] = "i500_user_basic_info.avatar as icon";
                 $field['address'] = (new Query())->select('details_address')->from("i500_user_address")->where("id=i500_user_order.address_id");
                 $field['consignee'] = (new Query())->select('consignee')->from("i500_user_address")->where("id=i500_user_order.address_id");
                 $field['consignee_mobile'] = (new Query())->select('consignee_mobile')->from("i500_user_address")->where("id=i500_user_order.address_id");
                if($type==0)
                {
                    $condition[UserOrder::tableName().'.shop_mobile'] = $mobile;
                }
                if($type==1)
                {
                    $condition[UserOrder::tableName().'.mobile'] = $mobile;
                }  
                $current_time = date("Y-m-d H:i:s",time());
                $time = date("Y-m-d H:i:s",strtotime($current_time ."-1 day"));
//                var_dump($time);
//                exit();
                $condition[UserOrder::tableName().'.status'] = $status;
                $condition[UserOrder::tableName().'.pay_status'] = $pay_status;
                $orderdata = UserOrder::find()->select($field)
                                  ->where($condition)
                                  ->andWhere(['<>','i500_user_order.status',3])
//                                  ->andWhere('i500_user_order.create_time >"'.$time.'"')
                                  ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_user_order.mobile')
                                  ->with(['orderdetail'=>function ($query){$query->select(['id','sid','order_sn','title','category_id','son_category_id','content','price','qty','image','unit']);}])
                                  ->orderBy($order_condition)
                                  ->offset($page)
                                  ->limit(10)
                                  ->asArray()
                                  ->all();
                 $empty = array();
                 for($k=0;$k<count($orderdata);$k++)
                 {
                    $detail = $orderdata[$k]['orderdetail'];
                    if(!empty($detail))
                    {
                        for($i=0;$i<count($detail);$i++)
                        {
                            if(empty($detail[$i]['image']))
                            {
                                $orderdata[$k]['orderdetail'][$i]['image'] = $empty;
                            }
                            else 
                            {
                                $orderdata[$k]['orderdetail'][$i]['image'] =json_decode($detail[$i]['image']);
                            }

                        }  
                    }
                    else 
                    {
                        $orderdata[$k]['orderdetail'] = $empty;
                    }
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
		 
		 $field = array();
                 $field[] = "i500_user_order.id";
                 $field[] = "i500_user_order.order_sn";
                 $field[] = "i500_user_order.mobile";
                 $field[] = "i500_user_order.shop_mobile";
                 $field[] = "i500_user_order.total";
                 $field[] = "i500_user_order.pay_status";
                 $field[] = "i500_user_order.status";
                 $field[] = "i500_user_order.remark";
                 $field[] = "i500_user_order.create_time";
                 $field[] = "i500_user_order.community_city_id";
                 $field[] = "i500_user_order.community_id";
//                 $field[] = "i500_user_order.order_info";
                 $field[] = "i500_user_order.order_type";
                 $field[] = "i500_user_order.pay_method";
                 $field[] = "i500_user_order.service_comment_status";
                 $field[] = "i500_user_order.user_comment_status";
                 $field[] = "i500_user_basic_info.nickname as nickname";
                 $field[] = "i500_user_basic_info.avatar as icon";
                 
                 
                 $orderdata = UserOrder::find()->select($field)
                                  ->where(['i500_user_order.mobile'=>$mobile])
                                  ->andWhere(['<>','i500_user_order.status',3])
                                  ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_user_order.shop_mobile')
                                  ->with(['orderdetail'=>function ($query){$query->select(['*']);}])
                                  ->orderBy('i500_user_order.id DESC')
                                  ->offset($page)
                                  ->limit(10)
                                  ->asArray()
                                  ->all();
		 $this->returnJsonMsg('200', $orderdata, Common::C('code','200','data','[]'));
	 }
	
}

?>