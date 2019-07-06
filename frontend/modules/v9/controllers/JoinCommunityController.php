<?php
/**
 * 用户加入社区相关接口
 *
 * PHP Version 8
 *
 * @category  Social
 * @package   Service
 * @author    yaoxin <yaoxin@i500m.com>
 * @time      2017/02/15
 * @copyright 2016 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      yaoxin@i500m.com
 */
namespace frontend\modules\v9\controllers;

use Yii;
use yii\db\Query;
use yii\data\Pagination;
use common\helpers\Common;
use yii\helpers\ArrayHelper;
use common\helpers\RequestHelper;
use frontend\models\i500m\Community;
use frontend\models\i500_social\Logincommunity;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\Identify;

class JoinCommunityController extends BaseController
{
    /**
     * 查询该用户加入社区时人数数量
     * return array()
    **/
    public function actionIndex()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if(empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if(!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $community_id = RequestHelper::post('community_id', '', '');
        if(empty($community_id)) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }

        $comm = new Logincommunity();
        $uid = $comm::find()->select(['id'])
							->where(['mobile'=> $mobile, 'community_id'=> $community_id])
							->asArray()
							->one();
        $user_id = $uid['id'];
        $commun = $comm::find()->select(['id'])
							   ->where(['<=', 'id', $user_id])
							   ->andWhere(['community_id'=> $community_id])
							   ->asArray()
							   ->count();
        $rest['amount'] = $commun;
        $amount[] = $rest;
        $this->returnJsonMsg('200', $amount, Common::C('code', '200'));
    }
	
	
    /**
     *  查询认证码状态
     *  return array()
    **/
    public function actionProgress()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if(empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if(!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $ident = new Identify();
        $identify = $ident::find()->select(['progress'])->where(['mobile'=>$mobile])->asArray()->one();
        if(empty($identify['progress'])) {
            $identify['progress'] = '0';
            $progress[] = $identify;
            $this->returnJsonMsg('200', $progress, Common::C('code', '200'));
        }else{
            $progress[] = $identify;
            $this->returnJsonMsg('200', $progress, Common::C('code', '200'));
        }
    }
}

?>