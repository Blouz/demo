<?php

/**
 * 用户服务，发布服务
 *
 * PHP Version 8
 *
 * @category  Social
 * @package   Post
 * @author    wangleilei <wangleilei@i500m.com>
 * @time      2016/11/9
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wangleilei@i500m.com
 */

namespace frontend\modules\v8\controllers;

use common\helpers\FastDFSHelper;
use frontend\models\i500_social\Recruit;
use frontend\models\i500m\Community;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\UserBasicInfo;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use common\helpers\CurlHelper;
use frontend\models\i500m\Shop;
use frontend\models\i500m\Product;
use frontend\models\shop\ShopProducts;
use yii\db\Query;

/**
 * Service
 *
 * @category Social
 * @package  Service

 */
class NebservController extends BaseController
{
    public function actionNeibserv()
    {
        $cate_id=RequestHelper::post('category_id', '', ''); //分类id

	$city_id=RequestHelper::post('city_id', '', ''); //所属城市id

        $comm_id=RequestHelper::post('community_id', '', '');  //所属社区id
        
//        $son_cate_id = RequestHelper::post('son_category_id', '', ''); //二级分类id
        
        $page_start=RequestHelper::post('page', '', '');        //列表起始位置
//        $page=$page*10;
        $page = (int)$page_start;
	if($page==""||$page==0)
	{
            $page=0;
	}
        else
        {
            $page = ($page - 1)*10;
        }
        
		
        $mobile=RequestHelper::post('usermobile', '', '');
	$dns = Shop::getDB()->dsn;
        $db = strstr($dns,"dbname=");
        $name = str_replace("dbname=","",$db);
                
            $serv = new Service();
            $field=array();
            $field[]='i500_service.id';
            $field[]='i500_service.mobile';
            $field[]='i500_service.image';
            $field[]='i500_service.price';
            $field[]='i500_service.title';
            $field[]='i500_service.create_time';
            $field[]='i500_service.description as des';
            $field[]='i500_user_basic_info.nickname';
            $field[]='i500_user_basic_info.avatar as icon';
            $field[]='i500_user_basic_info.personal_sign as sign';
            $field[]='i500_user_basic_info.backimg as backimg';
            $field[]='i500_user_basic_info.is_recruit as is_recruit';    
//            $authorized = (new Query())->select('count(id)')->from($name.".shop")->where("mobile=i500_service.mobile");
//            $field['authorized'] = $authorized;
            
           
            if(!empty($cate_id))
            {
                $condition[Service::tableName().'.category_id'] = $cate_id;
            }    
//            if(!empty($son_cate_id))
//            {
//                $condition[Service::tableName().'.son_category_id'] = $son_cate_id;
//            } 
            if(!empty($comm_id))
            {
                $condition[Service::tableName().'.community_id'] = $comm_id;
            }
            if(!empty($city_id))
            {
            	$condition[Service::tableName().'.community_city_id'] = $city_id;
            }
            if(!empty($mobile))
            {
                $condition[Service::tableName().'.mobile'] = $mobile;	
            }
            $condition[Service::tableName().'.status'] = '1';
            $condition[Service::tableName().'.is_deleted'] = '2';
            $condition[Service::tableName().'.audit_status'] = '2';

                
            $result = $serv->find()->select($field)
                           ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_service.mobile')
                           ->andwhere($condition)
                           ->orderBy('i500_service.id DESC')
                           ->offset($page)
                           ->limit(10)
                           ->asArray()
                           ->all();
            
            for($i=0;$i<count($result);$i++)
            {
                $url = array();
                if(!empty($result[$i]['image']))
                {
                    $symbol = substr($result[$i]['image'],0,1);
                    if($symbol=="[")
                    {
                        $result[$i]['image'] = json_decode($result[$i]['image']);
                    }
                    else
                    {
                        
                        $url[] = $result[$i]['image'];
                        $result[$i]['image'] = $url;
                    }
                }
                else 
                {
                    $result[$i]['image'] = $url;
                }
//                $result[$i]['origin_num']=0;
                $result[$i]['authorized']="0";
            }
             
            $this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));	
    }
    
    //发布服务
    public function actionSrvpub()
    {
		 
        $mobile=RequestHelper::post('mobile', '', '');
		 
	$cate_id=RequestHelper::post('category_id', '', '');
	if(empty($cate_id))
        {
            $this->returnJsonMsg('1000',[], '分类id不能为空');	
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
		 
	$content=RequestHelper::post('content', '', '');
	if(empty($content))
	{
            $this->returnJsonMsg('1006',[], '服务内容不能为空');	
	}	  
		 
	$address=RequestHelper::post('address', '', '');
	$city_id=RequestHelper::post('city_id', '', ''); //所属城市id
	if(empty($city_id))
	{
            $this->returnJsonMsg('2001',[], '城市id不能为空');	
	}
	$comm_id=RequestHelper::post('community_id', '', '');  //所属社区id
        if(empty($comm_id))
	{
            $this->returnJsonMsg('2002',[], '社区id不能为空');	
	}
        $url = array();
	if(!empty($_FILES))
	{
		$fastDfs = new FastDFSHelper();
		foreach ($_FILES as $k => $v) 
		{
			$rs_data = $fastDfs->fdfs_upload($k);
	
			if($rs_data) 
			{
                            $url[] = Common::C('imgHost').$rs_data['group_name'].'/'.$rs_data['filename'];                          
			}
		}
		$img = json_encode($url);	 
	}
        else 
        {
            $img = "[]";
        }
        $sev = new Service;
        $sev->image = $img;
        $sev->mobile = $mobile;
        $sev->category_id = $cate_id;
        $sev->price = $price;
        $sev->description = $content;
        $sev->title = $title;
        $sev->address = $address;
        $sev->community_city_id = $city_id;
        $sev->community_id = $comm_id;
                 
        $isrec = UserBasicInfo::find()->where(['is_recruit'=>1,'mobile'=>$mobile])->count();
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
    

}