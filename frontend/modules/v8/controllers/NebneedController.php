<?php

/**
 * 用户需求，发布需求
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

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\Seek;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\db\Query;
use common\helpers\FastDFSHelper;
class NebneedController extends BaseController
{
    //分类需求列表展示
    public function actionNeibneed()
    {
        $cate_id=RequestHelper::post('category_id', '', ''); //分类id

       $city_id=RequestHelper::post('city_id', '', ''); //所属城市id

        $comm_id=RequestHelper::post('community_id', '', '');  //所属社区id
    
        $mobile = RequestHelper::post('usermobile', '', '');
        
        $page=RequestHelper::post('page', '', ''); 
        if($page=="")
        {
            $page=0;
        }
        $page=$page*10;

            

            $seek = new Seek;
            $field=array();
            $field[]='i500_need.id';
            $field[]='i500_need.mobile';
            $field[]='i500_need.image';
            $field[]='i500_need.price';
            $field[]='i500_need.title';
            $field[]='i500_need.sendtime';
            $field[]='i500_need.description';
            $field[]='i500_need.create_time';
            $field[]='i500_user_basic_info.nickname';
            $field[]='i500_user_basic_info.avatar as icon';
            $field[]='i500_user_basic_info.personal_sign as sign';
            $field[]='i500_user_basic_info.backimg as backimg';
            $field[]='i500_user_basic_info.is_recruit as is_recruit';
            if(!empty($cate_id))
            {
                $condition[Seek::tableName().'.category_id'] = $cate_id;
            }
            if(!empty($comm_id))
            {
                $condition[Seek::tableName().'.community_id'] = $comm_id;
            }
            if(!empty($city_id))
            {
                $condition[Seek::tableName().'.community_city_id'] = $city_id;
            }
            if(!empty($mobile))
            {
                $condition[Seek::tableName().'.mobile'] = $mobile;  
            }
            
            $condition[Seek::tableName().'.status'] = '1';
            $condition[Seek::tableName().'.is_deleted'] = '2';
            $condition[Seek::tableName().'.is_receive'] = '0';
            
            

            $result = $seek->find()->select($field)
                               ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_need.mobile')
                               ->join('LEFT JOIN', 'i500_service_category','i500_need.son_category_id=i500_service_category.id')
                               ->andwhere($condition)
                               ->orderBy('i500_need.id DESC')
                               ->offset($page)
                               ->limit(100)
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
                        $tmp = json_decode($result[$i]['image']);
                        foreach ($tmp as $key => $value) {
                            if(trim($value) != ""){
                                $url[] = $value;
                            }
                        }
                    }
                    else
                    {
                        
                        $url[] = $result[$i]['image'];
                    }
                }
                $result[$i]['image'] = $url;
            }
            $this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));

    }
    //发布需求
    public function actionNeedpub()
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
            $this->returnJsonMsg('1005',[], '需求标题不能为空');    
    }
        $description=RequestHelper::post('content', '', '');
    if(empty($description))
    {
            $this->returnJsonMsg('1006',[], '需求描述不能为空');    
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
    $expire_time=RequestHelper::post('exp_date', '', ''); 
    if(empty($expire_time))
    {
            $this->returnJsonMsg('2003',[], '有效服务时间不能为空');  
    }
    $current_time=date('Y-m-d H:i:s',time());
    if($expire_time<$current_time)
    {
            $this->returnJsonMsg('1111',0, '截至时间不能早于当前时间'); 
    }
    else
    {
            if(!empty($_FILES))
            {
        $fastDfs = new FastDFSHelper();
        foreach ($_FILES as $k => $v) 
        {
                    $rs_data = $fastDfs->fdfs_upload($k);
    
                    if ($rs_data) 
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
            
            $seek = new Seek;
            $seek->image = $img;
            $seek->mobile = $mobile;
            $seek->category_id = $cate_id;
            $seek->price = $price;
            $seek->title=$title;
            $seek->description = $description;
            $seek->address = $address;
            $seek->community_city_id = $city_id;
            $seek->community_id = $comm_id;
            $seek->sendtime = $expire_time;
            $seek->save(false);
            $res = $seek->primaryKey;
         
            $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]')); 
        }
        
    }
    public function actionDelneed()
    {
        $nid=RequestHelper::post('nid', '', '');//服务id
        if(empty($nid))
        {
            $this->returnJsonMsg('7002',[], '需求id不能为空');    
        }
            $usermobile=RequestHelper::post('mobile', '', '');
        if(!empty($usermobile))
        {
            $res=Seek::updateAll(['is_deleted'=>1],['id'=>$nid]);
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
}