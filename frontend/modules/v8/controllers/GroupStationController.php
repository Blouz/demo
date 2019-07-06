<?php
/**
 * 好友相关
 *
 * PHP Version 5
 * 可写多行的文件相关说明
 *
 * @category  I500M
 * @package   Member
 * @author    xuxiaoyu <xuxiaoyu@i500m.com>
 * @time      16/10/14
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      xuxiaoyu@i500m.com
 */
namespace frontend\modules\v8\controllers;

use common\helpers\RequestHelper;
use common\helpers\Common;
use frontend\models\i500_social\GroupStation;
use frontend\models\i500_social\GroupRoute;
use frontend\models\i500_social\GroupStationRoom;
 
class GroupStationController extends BaseController
{
    public function actionRoute()
    {   
        $route = GroupRoute::find()->select(['id','route_name'])
		                           ->asArray()
		                           ->all();
        $this->returnJsonMsg('200', $route, Common::C('code', '200'));
    }
    public function actionStation()
    {   
        $stationList = GroupRoute::find()->select(['id','route_name'])
                                         ->with(['station'=>function($query) {
                                           $query->select(['id','route_id','station_name','sort']);
                                           }])
                                         ->asArray()->all();
        $this->returnJsonMsg('200', $stationList, Common::C('code', '200'));
    }
}
 