<?php

/**
 * 服务下单
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
namespace frontend\modules\v9\controllers;

use frontend\controllers\RestController;
use frontend\models\i500_social\ServiceOrderDetail;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\Seek;
use frontend\models\i500_social\ServiceWeekTime;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\ServiceUnit;
use frontend\models\i500_social\ServiceSetting;
use frontend\models\i500_social\User;
use yii\helpers\ArrayHelper;
use common\helpers\CurlHelper;
use yii\db\Query;
use frontend\models\i500_social\UserOrderDetail;
use frontend\models\i500_social\UserOrder;
use frontend\models\i500_social\Order;
/**
 * Service order
 *
 * @category Social
 * @package  Serviceorder

 */
class OrderController extends BaseController
{
    /**
     * 预约 
     * @return array
     */
    public function randsn()//生成随机24位订单号
    {
	$orderSn = date('i').substr(time(), -5).substr(microtime(), 2, 5).sprintf('%02d', rand(0, 99));

	$num=0;
	for($i=0;$i<9;$i++)
	{
            $ran=rand(1, 9);
            $num=(int)$ran.$num;
	}
            return $orderSn.$num;
    }
	
    public function actionAddServiceOrder()
    {
        $data['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($data['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $mob=$data['mobile'];
	$servicemob = RequestHelper::post('usermobile', '', '');
		
	$sid=array();	
	$servieidjson = $_POST['service_id'];
        if (empty($servieidjson)) {
            $this->returnJsonMsg('1010', [], Common::C('code', '1010'));
        }
	$sid=json_decode($servieidjson,true);

        $data['appointment_service_time'] = RequestHelper::post('appointment_service_time', '', '');
        if (empty($data['appointment_service_time'])) {
            $this->returnJsonMsg('1031', [], Common::C('code', '1031'));
        }
        $send_time = $data['appointment_service_time'];
//        $data['appointment_service_address'] = RequestHelper::post('appointment_service_address', '', '');
//        if (empty($data['appointment_service_address'])) {
//            $this->returnJsonMsg('1032', [], Common::C('code', '1032'));
//        }
        $address_id = RequestHelper::post('address_id', '', '');
        $data['source_type'] = RequestHelper::post('source_type', '', '');
        if (empty($data['source_type'])) {
            $this->returnJsonMsg('1033', [], Common::C('code', '1033'));
        }
        $data['remark'] = RequestHelper::post('remark', '', '');
        $data['community_id'] = RequestHelper::post('community_id', '0', 'intval');
        if (empty($data['community_id'])) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        $data['community_city_id'] = RequestHelper::post('community_city_id', '0', 'intval');
        if (empty($data['community_city_id'])) {
            $this->returnJsonMsg('645', [], Common::C('code', '645'));
        }
        $data['total'] = RequestHelper::post('price', '', '');
        $data['description'] = RequestHelper::post('description', '', '');
        $order_model = new Order();
        //@todo 确定创建订单号为什么用省份？35=全国
        $data['order_sn'] = $order_model->createSn('35', $data['mobile']);
        if (empty($data['order_sn'])) {
            $this->returnJsonMsg('1053', [], Common::C('code', '1053'));
        }
         
//       $rs_info['order_sn'] = $this->randsn();
        $rs_info['order_sn'] = $data['order_sn'];
        //@todo 应该用事务 判断这两个逻辑。
	if($data['order_sn']!==NULL)
	{	    
            $sn=$data['order_sn'];
            $pricetot=$data['total'];
            $remark=$data['description'];
            $createtime=date('Y-m-d H:i:s',time());
            $comm_city=$data['community_city_id'];
            $comm_id=$data['community_id'];
            $sourtype=$data['source_type'];
       
            $infomationdata=array();
            $servid=array();//服务id集合
			
            for($i=0;$i<count($sid);$i++)
            {
		$servid[]=$sid[$i]['id'];
            }
			
            $iservice=Service::find()->select(['id','title', 'mobile', 'category_id','son_category_id','image','description','price','unit','community_city_id','community_id'])->andwhere(['AND',['id'=>$servid],['and','audit_status' => 2],['and','status' => 1]])->asArray()->all();
			  
            foreach($iservice as $ise)
            {				
		$inf=array();
		$inf['sid']=$ise['id'];
		$inf['mobile']=$ise['mobile'];
		$inf['title']=$ise['title'];
		$inf['category_id']=$ise['category_id'];
		$inf['son_category_id']=$ise['son_category_id'];
		$inf['content']=$ise['description'];
		$service_id=$ise['id'];
		$qtyindex=array_search($service_id,$servid);//根据id值找出该数据在数组中的索引
		$qty=$sid[$qtyindex]['amount'];			    //数量	
		$inf['price']=$ise['price'];
		$inf['unit']=$ise['unit'];
                $symbol = substr($ise['image'],0,1);
                $imgurl = array();
                if($symbol!=="[")
                {
                    $imgurl[] = $ise['image'];
                    $inf['image'] = json_encode($imgurl);
                }
                else 
                {
                    $inf['image'] = $ise['image'];
                }
		
		$inf['community_city_id']=$data['community_city_id'];
		$inf['community_id']=$data['community_id'];
		$inf['qty']=$qty;
                $inf['order_sn']=$sn;
		$infomationdata[]=$inf;
            }
//            var_dump($servid);
//            exit;
            $orderif=json_encode($infomationdata);
            if(strlen($orderif)>8000)
            {
                $this->returnJsonMsg('8000',[], '已超出预订上限');	
            }
            else
            {
                if($mob!=$servicemob)
                {
                    $connection = \Yii::$app->db_social;
                    $transaction = $connection->beginTransaction();
                    $res = $connection->createCommand()->insert('i500_user_order', ['mobile' => $mob,'shop_mobile' =>$servicemob,'order_sn'=>$sn,'total'=>$pricetot,'remark'=>$remark,'create_time'=>$createtime,'order_type'=>1,'community_city_id'=>$comm_city,'community_id'=>$comm_id,'source_type'=>$sourtype,'status'=>0,'order_info'=>"[]",'address_id'=>$address_id,'send_time'=>$send_time])->execute();
                    
                    if($res==1)
                    {
                        foreach($infomationdata as $infodata)
                        {
                            $order_model = new UserOrderDetail();
                            $order_model->sid = $infodata['sid'];
                            $order_model->mobile = $infodata['mobile'];
                            $order_model->category_id = $infodata['category_id'];
                            $order_model->son_category_id = $infodata['son_category_id'];
                            $order_model->title = $infodata['title'];
                            $order_model->content = $infodata['content'];
                            $order_model->price = $infodata['price'];
                            $order_model->unit = $infodata['unit'];

                            $order_model->image = $infodata['image'];
                            $order_model->community_city_id = $infodata['community_city_id'];
                            $order_model->community_id = $infodata['community_id'];
                            $order_model->qty = $infodata['qty'];
                            $order_model->order_sn = $infodata['order_sn'];
                            $succeed = $order_model->save(false);
                             
                        }
                        $transaction->commit();
                    }
                    else
                    {
                        $transaction->rollBack();
                    }
                }
            }
        }
        $rs_info['order_type']=1;
        $rs_info['total']=$pricetot;
        $this->returnJsonMsg('200', $rs_info, Common::C('code', '200'));
    }
    //服务方发货
    public function actionShipped()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $order_sn = RequestHelper::post('order_sn', '', '');
        $time = date("Y-m-d H:i:s",time());
        $res = UserOrder::updateAll(array('status'=>'5','operation_time'=>$time),'shop_mobile=:mobile AND order_sn=:sn',array(':mobile'=>$mobile,':sn'=>$order_sn));
        $this->returnJsonMsg('200', $res, Common::C('code', '200'));
    }
    //买家收货
    public function actionRecieved()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $order_sn = RequestHelper::post('order_sn', '', '');
        $time = date("Y-m-d H:i:s",time());
        $res = UserOrder::updateAll(array('status'=>'2','operation_time'=>$time),'mobile=:mobile AND order_sn=:sn',array(':mobile'=>$mobile,':sn'=>$order_sn));
        $this->returnJsonMsg('200', $res, Common::C('code', '200'));
    }
    //
    public function actionCancled()
    {
        $current_time = date("Y-m-d H:i:s",time());
        $time = date("Y-m-d H:i:s",strtotime($current_time ."-15 minute"));
        $res = UserOrder::updateAll(array('status'=>'3'),'create_time<:ct AND pay_status=:ps AND status=:st',array(':ct'=>$time,':ps'=>0,'st'=>0));
        $this->returnJsonMsg('200', $res, Common::C('code', '200'));
    }
     public function actionGetRecieved()
    {
        $current_time = date("Y-m-d H:i:s",time());
        $time = date("Y-m-d H:i:s",strtotime($current_time ."-7 day"));//7天后到期
        $res = UserOrder::updateAll(array('status'=>'2'),'operation_time<:ct AND pay_status=:ps AND status=:st',array(':ct'=>$time,':ps'=>1,'st'=>5));
        $this->returnJsonMsg('200', $res, Common::C('code', '200'));
    }
}
