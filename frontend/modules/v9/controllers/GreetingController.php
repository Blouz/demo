<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\modules\v9\controllers;

use frontend\models\i500_social\Greeting;
use common\helpers\RequestHelper;
use common\helpers\Common;
use yii;


class GreetingController extends BaseController
{
    public function actionIndex()
    {   
        $res = Greeting::find()->select(['image','greetings','title','content','description'])->where(['status'=>1])->asArray()->one();
        $this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
       
    }
}