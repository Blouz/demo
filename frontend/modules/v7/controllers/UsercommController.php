<?php
namespace frontend\modules\v7\controllers;

use frontend\models\i500m\Community;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper; 
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\Logincommunity;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\db\Query;
use common\helpers\CurlHelper;
use frontend\models\i500_social\User;
class UsercommController extends BaseController
{
    
    //用户加入社区
     public function actionSelectcomm()
    {
        $mobile=RequestHelper::post('mobile', '', '');
        if(empty($mobile))
		{
            $this->returnJsonMsg('4005',[], '手机号不能为空');	
		}
        $community_id = RequestHelper::post('community_id', '', '');
        if(empty($community_id))
		{
            $this->returnJsonMsg('4006',[], '社区id不能为空');	
		}
        $community_city_id = RequestHelper::post('community_city_id', '', '');
        if(empty($community_city_id))
		{
            $this->returnJsonMsg('4007',[], '城市id不能为空');	
		}
        $community_name = RequestHelper::post('community_name', '', '');
        if(empty($community_name))
		{
            $this->returnJsonMsg('4008',[], '社区名称不能为空');	
		}
        $address = RequestHelper::post('address', '', '');
        $lng = RequestHelper::post('lng', '', '');
        $lat = RequestHelper::post('lat', '', '');
        $count = Logincommunity::find()->where(['mobile'=>$mobile,'is_deleted'=>0])->count();
        if($count<2)
        {
            $com = Logincommunity::find()->where(['mobile'=>$mobile,'is_deleted'=>0])->asArray()->one();

            if($com['community_city_id']==$community_city_id&&$com['community_id']==$community_id)
            {
                 $this->returnJsonMsg('9907',[], '不能重复加入同一个社区');
            }
            else
            {
                $comm = new Logincommunity();
                $comm->mobile = $mobile;
                $comm->community_id = $community_id;
                $comm->community_city_id = $community_city_id;
                $comm->community_name = $community_name;
                $comm->address = $address;
                $comm->lng = $lng;
                $comm->lat = $lat;
                $res = $comm->save();
                $commid = $comm->primaryKey;
                if($res)
                {
                    $logincomm = new Logincommunity();
        
                    $unjoin = $logincomm->updateAll(array('join_in'=>'0'),'mobile=:mobile AND id<>:id',array(':mobile'=>$mobile,':id'=>$commid));
                    if($unjoin>0)
                    {
                        $joinin = UserBasicInfo::updateAll(['last_community_id'=>$community_id,'last_community_city_id'=>$community_city_id,'community_name'=>$community_name],['mobile'=>$mobile]);
                        //新用户加入社区后做推送
                        if($joinin>0)
                        {
                            $login_comm = new Logincommunity();
                            $field = array();
                            $field[]="i500_login_community.id";
                            $field[]="i500_user.channel_id as channel_id";
                            
							$condition[Logincommunity::tableName().'.community_city_id'] = $community_city_id;
							$condition[Logincommunity::tableName().'.community_id'] = $community_id;
							$condition[Logincommunity::tableName().'.is_deleted'] = '0';

							$user_channel = $login_comm->find()->select($field)
										->join('LEFT JOIN','i500_user','i500_user.mobile=i500_login_community.mobile')
										->where($condition)
										->asArray()
										->all();
							//获取要推送的channel_id
							$ios = array();
							$and = array();
							foreach($user_channel as $uc)
							{
								$channel = array();
								$channel = explode("-",$uc['channel_id']);
								if($channel[0]=='1')
								{
									$ios[] = $channel[1];
								}
								if($channel[0]=='2')
								{
									$and[] = $channel[1];
								}
                            }     
                        }
                    }
                }
                $this->returnJsonMsg('200', [], Common::C('code','200','data','[]'));
            }
         }
         else
         {    
            $this->returnJsonMsg('9908',[], '不能同时加入两个以上社区');	
         }
//         return $this->render('push',['and'=>$and,'ios'=>$ios]);  
    }
	
	
	
	
    //用户退出社区
    public function actionExitcomm()
    {
        $mobile=RequestHelper::post('mobile', '', '');
        if(empty($mobile))
		{
            $this->returnJsonMsg('4005',[], '手机号不能为空');	
		}
        $commid = RequestHelper::post('commid', '', '');
        if(empty($commid))
		{
			//当前用户所在社区列表id
            $this->returnJsonMsg('4006',[], '手机号不能为空');	
		}
        
        $modify = Logincommunity::find()->select(['modify_time','id'])->where(['mobile'=>$mobile,'id'=>$commid,'is_deleted'=>0])->asArray()->one();
        $lid = $modify['id'];
		//$orgdate = $modify['modify_time']+30;
        $orgdate = $modify['modify_time'];
        if($modify['id']>0)
        {
			if($orgdate<date('Y-m-d H:i:s',time()))
			{
				$currenttime = date('Y-m-d H:i:s',time());
				$result = Logincommunity::updateAll(['modify_time'=>$currenttime,'is_deleted'=>1],['id'=>$lid]);
				if($result>0)
				{
					//如果res为0表示用户不在任何社区内，
					$res = Logincommunity::find()->where(['mobile'=>$mobile,'is_deleted'=>0])->count();
					$this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
				}
			}
			else 
			{
				$this->returnJsonMsg('9909',[], '30天内不可退出');	
			}
        }
        else
        {
             $this->returnJsonMsg('9910',[], '该用户不在此社区内');	
        }
    }
	
	
	
    //用户所在社区的地址信息集合
     public function actionShowcommlist()
    {
          $mobile=RequestHelper::post('mobile', '', '');
            if(empty($mobile))
            {
                $this->returnJsonMsg('4005',[], '手机号不能为空');	
            }
          
           $logcomm = new Logincommunity();
           $field=array();
           $field[]='i500_login_community.id';
           $field[]='i500_login_community.community_name';
           $field[]='i500_login_community.address';
           $field[]='i500_login_community.community_city_id';
           $field[]='i500_login_community.community_id';
           $field[]='i500_login_community.lng';
           $field[]='i500_login_community.lat';
           $field[]='i500_login_community.join_in';
                        
           $condition[Logincommunity::tableName().'.mobile'] = $mobile;
           $condition[Logincommunity::tableName().'.is_deleted'] = '0';
                        
           $result = $logcomm->find()->select($field)
                      ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_login_community.mobile')
                      ->andwhere($condition)
                      ->asArray()
                      ->all();
           $this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));
    }
	
	
	
	//用户切换小区
    public function actionChoseComm()
    {
		$mobile=RequestHelper::post('mobile', '', '');
		if(empty($mobile))
		{
		$this->returnJsonMsg('4005',[], '手机号不能为空');	
		}
        $commid = RequestHelper::post('commid', '', '');
        if(empty($commid))
		{
            $this->returnJsonMsg('4006',[], '手机号不能为空');	
		}
        
        $position = Logincommunity::find()->select(['community_id','community_city_id','community_name'])->where(['id'=>$commid,'is_deleted'=>0])->asArray()->one();
        $city = $position['community_city_id'];
        $community = $position['community_id'];
        $community_name = $position['community_name'];
        $join_in = Logincommunity::updateAll(['join_in'=>1],['id'=>$commid]);
//        $connection = \Yii::$app->db_social;
//        $command = $connection->createCommand("update i500_login_community set join_in='0' where mobile='$mobile' and id <>'$commid'");
//	$unjoin =  $command->execute();
        $logincomm = new Logincommunity();
        
        $unjoin = $logincomm->updateAll(array('join_in'=>'0'),'mobile=:mobile AND id<>:id',array(':mobile'=>$mobile,':id'=>$commid));     
         
        if($join_in==1)
        {
            $res = UserBasicInfo::updateAll(['last_community_id'=>$community,'last_community_city_id'=>$city,'community_name'=>$community_name],['mobile'=>$mobile]);
        }
        $this->returnJsonMsg('200', $unjoin, Common::C('code','200','data','[]'));
    }
}















