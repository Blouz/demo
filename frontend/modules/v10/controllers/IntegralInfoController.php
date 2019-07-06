<?php
/**
 * 标签相关接口
 *
 * PHP Version 10
 *
 * @category  Social
 * @package   Service
 * @author    yaoxin <yaoxin@i500m.com>
 * @time      2017/03/21
 * @license   http://www.i500m.com license
 * @link      yaoxin@i500m.com
 */
namespace frontend\modules\v10\controllers;

use Yii;
use yii\db\Query;
use yii\data\Pagination;
use common\helpers\Common;
use yii\helpers\ArrayHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Integral;
use frontend\models\i500_social\IntegralLevel;
use frontend\models\i500_social\IntegralRules;


class IntegralInfoController extends BaseController
{
    /**
     * 用户积分详细
     * @return Array()
    **/
    public function actionIndex()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $page = RequestHelper::post('page', '1', 'intval');
        $size = RequestHelper::post('size', '10', 'intval');
        $info = Integral::find()->select(['create_time', 'score', 'rule_id'])->where(['mobile'=>$mobile])
                                ->with(['integralrules'=>function($query) {
                                    $query->select(['id', 'rule_name']);
                                }]);
        $model = $info->offset(($page-1) * $size)->limit($size)->asArray()->all();
        $this->returnJsonMsg('200', $model, Common::C('code', '200'));
    }
}

?>