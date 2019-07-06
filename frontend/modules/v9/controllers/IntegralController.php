<?php

/**
 * 积分相关
 *
 * PHP Version 8
 *
 * @category  Social
 * @package   Service
 * @author    wangleilei <wangleilei@i500m.com>
 * @time      2017/02/23
 * @copyright 2017 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wangleilei@i500m.com
 */
namespace frontend\modules\v9\controllers;

use frontend\controllers\RestController;
use frontend\models\i500_social\ServiceOrderDetail;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Integral;
use frontend\models\i500_social\IntegralLevel;
use frontend\models\i500_social\IntegralRules;
use frontend\models\i500_social\Message;
use yii\helpers\ArrayHelper;
use common\helpers\CurlHelper;
use yii\db\Query;

/**
 * Service order
 *
 * @category Social
 * @package  Serviceorder

 */
class IntegralController extends BaseController
{
    public function actionGetIntegral()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        
        
        //返回用户当前积分等级
        $score = Integral::find()->select('SUM(score)')->where(['mobile'=>$mobile])->scalar();
        $level = IntegralLevel::find()->select(['gradation','level_name'])->orderBy('gradation')->asArray()->all();
        $level_name = "";
        $gradation = "";
        if(count($level)>0)
        {
            for($i=0;$i<count($level);$i++)
            {
                if($score>$level[$i]['gradation'])
                {
                    continue;
                }
                else
                {
                    $level_name = $level[$i]['level_name'];
                    $gradation = $level[$i]['gradation'];
                    break;
                }
            }
        }
        $res['score'] = $score;
        $res['gradation'] = $gradation;
        $res['level'] = $level_name;    
        $this->returnJsonMsg('200', $res, Common::C('code', '200'));
                
    }
    //积分规则
    public function actionIntegralRules()
    {
        $group = RequestHelper::post('group', '', '');
        if(!empty($group))
        {
            $condition[IntegralRules::tableName().'.group'] = $group;
        }
        $condition[IntegralRules::tableName().'.status'] = 1;
        $score = IntegralRules::find()->select(['id','score','rule_name','group'])->where($condition)->asArray()->all();
        
        $this->returnJsonMsg('200', $score, Common::C('code', '200'));
                
    }
    //积分等级
    public function actionIntegralLevel()
    {
        $condition[IntegralLevel::tableName().'.status'] = 1;
        $res = IntegralLevel::find()->select(['id','level_name','gradation','image'])->where($condition)->asArray()->all();       
        $this->returnJsonMsg('200', $res, Common::C('code', '200'));
                
    }
    //积分明细
    public function actionIntegralDetail()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $page = RequestHelper::post('page', '1', 'intval');
        $page_size = RequestHelper::post('page_size','10','intval');
        $field[] = "i500_integral.id";
        $field[] = "i500_integral.score";
        $field[] = "i500_integral.create_time";
        $field[] = "i500_integral_rules.rule_name";
        $res = Integral::find()->select($field)
                        ->join('LEFT JOIN','i500_integral_rules','i500_integral.rule_id=i500_integral_rules.id')
                        ->where(['i500_integral.mobile'=>$mobile])
                        ->orderBy('i500_integral.id desc')
                        ->offset(($page-1)*$page_size)
                        ->limit($page_size)
                        ->asArray()
                        ->all();

        $this->returnJsonMsg('200', $res, Common::C('code', '200'));
    }
}