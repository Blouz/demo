<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-9 下午3:03
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v3\controllers;

use frontend\models\i500_social\UserVerifyCode;
use yii\web\Controller;

class HomeController extends Controller
{
    public function actionIndex()
    {
        echo 32;
    }
    public function actionAbc()
    {
        $model = new UserVerifyCode();
        $data = ['mobile'=>'18618359358', 'code'=>1111];
        $re = $model->insertInfo($data);
        //var_dump($re);
        exit();
    }
}