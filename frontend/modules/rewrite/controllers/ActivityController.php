<?php
/**
 * 活动相关接口
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   BASE
 * @author    huangdekui <huangdekui@i500m.com>
 * @time      2017/4/18
 * @copyright 辽宁i500科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      huangdekui@i500m.com
 */
namespace frontend\modules\rewrite\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social_rewrite\Activity;
use frontend\models\i500_social_rewrite\UserCommunity;
use frontend\models\i500_social_rewrite\ActivityCommunity;
use yii\helpers\ArrayHelper;
use yii\db\Query;

/**
 * BASE
 *
 * @category Social
 * @package  BASE
 * @author   huangdekui <huangdekui@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     huangdekui@i500m.com
 */

class ActivityController extends ActivityBaseController
{
    public $actions = array(
    		'0'=>['index'],
    		'1'=>['set-top'],
    		'2'=>['set-top']
    	);

    public function beforeAction($action){  
        $user_actions = $this->actions[$this->community_info['is_pioneer']];
        if ( !in_array($action->id, $user_actions)) {
            $this->returnJsonMsg(403, [], Common::C('coderewrite', '403'));
        }
        return parent::beforeAction($action);
    }

    /**
     * 活动列表接口
     * @param string $mobile 手机号
     * @param int    $page   页数
     * @param int    $page_size   数量
     * @return json
     * 
     */
    public function actionIndex()
    {   
        if (empty($this->mobile)){
        	return $this->returnJsonMsg(604,[],Common::C('coderewrite','604'));
        }
        if (!Common::validateMobile($this->mobile)) {
            return $this->returnJsonMsg(605, [], Common::C('coderewrite', '605'));
        }

        //小区
        $userCommunity = new UserCommunity();
        $community_id  = $userCommunity->getOneRecord(['mobile'=>$this->mobile],'',['community_id']);


       	//先查询出当前小区所有活动ID
        
        $activity_community = new ActivityCommunity();
        $activity_id = $activity_community->getCloumn(['activity_id'],['community_id'=>$community_id]);

       	//查询数据带分页
       	$activity = new Activity();
        $result = $activity->getList($this->mobile,$activity_id,$this->page,$this->page_size);

        $this->returnJsonMsg(200,$result, Common::C('coderewrite','200'));
    }

    /**
     *  活动置顶
     *  @param string $mobile 手机号
     *  @param int $activity_id 活动ID
     *  @return array
     */
    public function actionSetTop()
    {
        if (empty($this->mobile)){
            return $this->returnJsonMsg(604,[],Common::C('coderewrite','604'));
        }
        if (!Common::validateMobile($this->mobile)) {
            return $this->returnJsonMsg(605, [], Common::C('coderewrite', '605'));
        }

        if (!empty($this->activity_id)) {
            return $this->returnJsonMsg(3001,[],Common::C('coderewrite','3001'));
        }

        $reset = Activity::updateAll(['sort'=>0]);

        $res = Activity::updateAll(['sort'=>1],['id'=>$activity_id]);

        return $this->returnJsonMsg(200,[], Common::C('coderewrite','200'));
    }



}