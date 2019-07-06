<?php
/**
 * 意见反馈
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Post
 * @author    yaoxin <yaoxin@i500m.com>
 * @time      2017-03-21
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      yaoxin@i500m.com
 */
namespace frontend\modules\v10\controllers;

use Yii;
use common\helpers\Common;
use yii\helpers\ArrayHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\Suggest;

/**
 * Post
 *
 * @category Social
 * @package  Post
 * @author   yaoxin <yaoxin@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     yaoxin@i500m.com
 */
class SuggestController extends BaseController
{
    /**
     * Before
     * @param \yii\base\Action $action Action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }
    /**
     * 邻居说话题
     * @return array
     */
    public function actionIndex()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $step = User::find()->select(['step'])->where(['mobile'=>$mobile,'step'=>8])->asArray()->one();
        if (!$step) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }

        $contract_mobile = RequestHelper::post('contract_mobile', '', '');
        if(!empty($contract_mobile)) {
            if(!Common::validateMobile($contract_mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }

        $content = RequestHelper::post('content', '', '');
        if(empty($content)) {
            $this->returnJsonMsg('6015', [], '用户意见反馈不能为空');
        }

        $suggest = new Suggest();
        $suggest->mobile = $mobile;
        $suggest->contract_mobile = $contract_mobile;
        $suggest->content = Common::sens_filter_word($content);
        $suggest->create_time = date("Y-m-d H:i:s", time());
        $res = $suggest->save();
        if($res) {
            $this->returnJsonMsg('200', [], Common::C('code', '200'));
        }else{
            $this->returnJsonMsg('500', [], Common::C('code', '500'));
        }
    }
}
