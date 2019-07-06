<?php
/**
 * 服务订单
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Service
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/9/20
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v7\controllers;

use frontend\controllers\RestController;
use frontend\models\i500_social\ServiceOrderDetail;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\ServiceWeekTime;
use frontend\models\i500_social\ServiceOrder;
use frontend\models\i500_social\Order;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\ServiceUnit;
use frontend\models\i500_social\ServiceSetting;
use frontend\models\i500_social\User;
use yii\helpers\ArrayHelper;
use common\helpers\CurlHelper;
/**
 * Service order
 *
 * @category Social
 * @package  Serviceorder

 */
class ServiceorderController extends BaseController
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
	
    public function actionAdd()
    {
        //$data['service_way']=RequestHelper::post('service_way', '', '');//服务方式 2来我家 1去你家
		
        $data['mobile'] = RequestHelper::post('mobile', '', '');
        //if (empty($data['mobile'])) {
//            $this->returnJsonMsg('604', [], Common::C('code', '604'));
//        }
        $mob=$data['mobile'];
	$servicemob = RequestHelper::post('usermobile', '', '');
		
		$sid=array();	
        //$servieidjson = RequestHelper::post('service_id', '', '');
		$servieidjson = $_POST['service_id'];
        if (empty($servieidjson)) {
            $this->returnJsonMsg('1010', [], Common::C('code', '1010'));
        }
		$sid=json_decode($servieidjson,true);
		//var_dump($sid);
		//exit;
        $data['appointment_service_time'] = RequestHelper::post('appointment_service_time', '', '');
//        if (empty($data['appointment_service_time'])) {
//            $this->returnJsonMsg('1031', [], Common::C('code', '1031'));
//        }
        $data['appointment_service_address'] = RequestHelper::post('appointment_service_address', '', '');
        if (empty($data['appointment_service_address'])) {
            $this->returnJsonMsg('1032', [], Common::C('code', '1032'));
        }
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
        $data['total'] = RequestHelper::post('price', '0', '0');
        $data['description'] = RequestHelper::post('description', '', '');
        $order_model = new Order();
        //@todo 确定创建订单号为什么用省份？35=全国
        $data['order_sn'] = $order_model->createSn('35', $data['mobile']);
        if (empty($data['order_sn'])) {
            $this->returnJsonMsg('1053', [], Common::C('code', '1053'));
        }
         
      
        $rs_info['order_sn'] = $data['order_sn'];
        //@todo 应该用事务 判断这两个逻辑。
		if($data['order_sn']!==NULL)
		{
			$connection = \Yii::$app->db_social;
		    
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
				$inf['id']=$ise['id'];
				$inf['mobile']=$ise['mobile'];
				$inf['title']=$ise['title'];
				$inf['category_id']=$ise['category_id'];
				$inf['son_category_id']=$ise['son_category_id'];
				$inf['content']=$ise['description'];
				$eachprice=$ise['price'];
				$service_id=$ise['id'];
				$qtyindex=array_search($service_id,$servid);//根据id值找出该数据在数组中的索引
				$qty=$sid[$qtyindex]['amount'];			    //数量	
				$inf['price']=$eachprice;
				$inf['unit']=$ise['unit'];
				$inf['image']=$ise['image'];
				$inf['community_city_id']=$data['community_city_id'];
				$inf['community_id']=$data['community_id'];
				$inf['qty']=$qty;
				$infomationdata[]=$inf;
			  }
			//}
			 
			$orderif=json_encode($infomationdata);
			if(strlen($orderif)>8000)
			{
                            $this->returnJsonMsg('8000',[], '已超出预订上限');	
			}
			else
			{
                            if($mob!=$servicemob)
                            {
                                $res = $connection->createCommand()->insert('i500_user_order', ['mobile' => $mob,'shop_mobile' =>$servicemob,'order_sn'=>$sn,'total'=>$pricetot,'remark'=>$remark,'create_time'=>$createtime,'order_type'=>1,'community_city_id'=>$comm_city,'community_id'=>$comm_id,'source_type'=>$sourtype,'status'=>0,'order_info'=>$orderif])->execute();
                            }
			}
		}
		$rs_info['order_type']=1;
		$rs_info['total']=$pricetot;
        $this->returnJsonMsg('200', $rs_info, Common::C('code', '200'));
    }


}
