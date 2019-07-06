<?php
/**
 * 服务
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Service
 * @author    xuxiaoyu <xuxiaoyu@iyangpin.com>
 * @time      2016/10/27
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      xuxiaoyu@iyangpin.com
 */
namespace frontend\modules\v8\controllers;

use frontend\controllers\RestController;
use frontend\models\i500_social\Recruit;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500m\Shop;
use frontend\models\i500m\Product;
use frontend\models\shop\ShopProducts;
use frontend\models\i500m\Category;

class ServicedetailController extends BaseController
{
    /**
     * 服务详情
     * @return array
     */
    public function actionDetail()
    {
        $id = RequestHelper::post('id', '0', 'intval');
        if (empty($id)) {
            $this->returnJsonMsg('1010', [], Common::C('code', '1010'));
        }
        $user_mobile = RequestHelper::post('user_mobile','','');
        if (empty($user_mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $service = Service::find()->select(['id','mobile','image','title','description as describe','price','create_time'])
                                 ->where(['id'=>$id,'mobile'=>$user_mobile])
                                 ->asArray()->one();
        
		if(!empty($service)){
    		$retarray = array(
    			"id"=>'', "mobile"=>'', "image"=>'', "title"=>'', "describe"=>'', "price"=>'',"nickname"=>'', "avatar"=>'', "shop_id"=>'', "introduction"=>'', "logo"=>'', "sent_fee"=>'', "free_money"=>'', "freight"=>'', "url"=>''
    		);

    		$retarray = array_merge((array)$retarray,(array)$service);
    		$userbasicinfo = UserBasicInfo::find()->select(['nickname','avatar','is_recruit'])
    									->where(['mobile'=>$user_mobile])
    									->asArray()->one();
    		if($userbasicinfo){
    			$retarray = array_merge((array)$retarray,(array)$userbasicinfo);
    		}
    		$shop = Shop::find()->select(['id as shop_id','introduction','logo','sent_fee','free_money','freight'])
    							->where(['mobile'=>$user_mobile])
    							->asArray()->one();
    		if($shop){
    			$retarray = array_merge((array)$retarray,(array)$shop);
    		}



            $categoryList = Category::find()->select(['id','name'])->where(['status'=>2,'type'=>0,'level'=>1])->with(['sec'=>function($query) {
                $query->select(['id','name','parent_id'])->where(['status'=>2,'type'=>0,'level'=>2]);
            }])->asArray()->all();

            $retarray['category'] = $categoryList;                       
        } else {           
            $this->returnJsonMsg('2002', [], '无服务详情');
        }
        
        $url = array();
        if(!empty($retarray['image']))
        {
            $symbol = substr($retarray['image'],0,1);
            if($symbol=="[")
            {
                $retarray['image'] = json_decode($service['image']);
            }
            else
            {   
                $url[] = $service['image'];
                $retarray['image'] = $url;
            }
        }
        else 
        {
            $retarray['image'] = $url;
        }
        $this->returnJsonMsg('200', $retarray, Common::C('code', '200'));
    }
}
