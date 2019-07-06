<?php

namespace frontend\modules\v9\controllers;

use common\helpers\RequestHelper;
use common\helpers\Common;
use frontend\models\i500_social\EditionUpgrade;

class EditionController extends BaseController
{
    public function actionPatch()
    {
       $code = RequestHelper::post('code', '', ''); //版本号
       
       $res = EditionUpgrade::find()->select(['patch'])
                              ->where(['code'=>$code])
                              ->orderBy('id desc')
                              ->offset(0)
                              ->limit(1)
                              ->asArray()
                              ->one();
       $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));	
    }
}