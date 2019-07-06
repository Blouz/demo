<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-20 上午10:44
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v4\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\controllers\RestController;
use frontend\models\i500_social\Recruit;
use frontend\models\i500_social\UserBasicInfo;
use Yii;
use yii\data\ActiveDataProvider;

class RecruitController extends RestController
{
    public $modelClass = 'frontend\models\i500_social\Recruit';
    public function actions(){
        $actions = parent::actions();
        unset($actions['view'],$actions['create']);
        return $actions;
    }

    public function actionView()
    {
        $mobile = RequestHelper::get('mobile', 0);
        $model = $this->findModel(['mobile'=>$mobile]);
        if (empty($model)) {
            $this->result['code'] = 404;
            $this->result['message'] = '数据不存在';
            return $this->result;
        } else {
            if ($model->status ==1 ) {
                $model->identity_card = substr_replace($model->identity_card,"****",5,9);
            }

            //$model->identity_card = str_replace();
            return $model;
        }

    }

    public function actionCreate()
    {
        $mobile = RequestHelper::post('mobile', 0);
        $model = $this->findModel(['mobile'=>$mobile]);
        if (empty($model)) {
            $model = new $this->modelClass;

        } else {
            if ($model->status == 1) {
                $this->result['code'] = 512;
                $this->result['message'] = '您已经审核通过不可再次修改!';
            } else if ($model->status == 0) {
                $this->result['code'] = 513;
                $this->result['message'] = '请等待审核';
                return $this->result;
            }
            $model->status=0;
        }
        $model->attributes = Yii::$app->request->post();
        //var_dump($model->attributes);exit();
        if (!$model->save()) 
        {

            $errors = $model->getFirstErrors();
            //var_dump($errors);exit();
            $error = array_values($errors);
            $this->result['code'] = 511;
            $this->result['message'] = ArrayHelper::getValue($error, 0, 'Error');
        }
        else 
        {
            
        }
        return $this->result;

    }
}