<?php
/**
 * 一行的文件介绍
 *
 * PHP Version 5
 * 可写多行的文件相关说明
 *
 * @category  I500M
 * @package   Member
 * @author    xuxiaoyu <xuxiaoyu@i500m.com>
 * @time      16/10/5
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      xuxiaoyu@i500m.com
 */
namespace frontend\modules\v7\controllers;

use common\helpers\RequestHelper;
use common\helpers\Common;
use frontend\models\i500_social\Logincommunity;

class CommunityController extends BaseController
{
	/**
     * Before
     * @param \yii\base\Action $action Action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
   

    /**
     * 获取加入社区人数
     * @return array
     */
    public function actionLogincommunity()
    {  
        $community_id = RequestHelper::post('community_id', 0, 'intval');
        if (empty($community_id)) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        $community_city_id = RequestHelper::post('community_city_id', 0, 'intval');
        if (empty($community_city_id)) {
            $this->returnJsonMsg('645', [], Common::C('code', '645'));
        }

        $res = Logincommunity::find()->select(['mobile'])
                                     ->where(['community_id'=>$community_id,'community_city_id'=>$community_city_id,'is_deleted'=>0])
                                     ->asArray()
                                     ->count();

        $this->returnJsonMsg('200', $res, Common::C('code', '200'));
    }
}
