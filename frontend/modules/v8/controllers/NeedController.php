<?php
/**
 * 需求
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
use frontend\models\i500_social\Need;
use frontend\models\i500_social\UserBasicInfo;

class NeedController extends BaseController
{
    /**
     * 需求详情
     * @return array
     */
    public function actionDetail()
    {
        $id = RequestHelper::post('id', '0', 'intval');
        if (empty($id)) {
            $this->returnJsonMsg('1010', [], Common::C('code', '1010'));
        }
        $result = Need::find()->select(['i500_need.id','i500_need.mobile','i500_need.image','i500_need.title','i500_need.description as describe','i500_need.price','i500_need.create_time','i500_need.sendtime','i500_user_basic_info.nickname','i500_user_basic_info.avatar','i500_user_basic_info.is_recruit'])
                              ->join('LEFT JOIN','i500_user_basic_info','i500_need.mobile = i500_user_basic_info.mobile')
                              ->where(['i500_need.id'=>$id])
                              ->asArray()->one();
        $url = array();
        if(!empty($result['image']))
        {
            $symbol = substr($result['image'],0,1);
            if($symbol=="[")
            {
                $result['image'] = json_decode($result['image']);
            }
            else
            {            
                $url[] = $result['image'];
                $result['image'] = $url;
            }
        }
        else 
        {
            $resul['image'] = $url;
        }
    
        if (!empty($result)) {
            $this->returnJsonMsg('200', $result, Common::C('code', '200'));
        }else{
            $this->returnJsonMsg('2002', [], '无需求详情');
        }
    }
} 
