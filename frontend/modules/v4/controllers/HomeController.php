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
namespace frontend\modules\v4\controllers;

use common\helpers\CurlHelper;
use common\helpers\HuanXinHelper;
use frontend\models\i500_social\Seek;
use frontend\models\i500_social\UserVerifyCode;
use linslin\yii2\curl\Curl;
use yii\web\Controller;

class HomeController extends Controller
{
    public function actionIndex()
    {

        $curl = new Curl();
        $put['price'] = 55;
        $put['description'] = '1是描222述';
        $response = $curl->reset()
            ->setOption(CURLOPT_POSTFIELDS, http_build_query($put))->put("http://social.500mi.com/v4/seeks/10");
        var_dump($response);
        $response = json_decode($response, true);
        var_dump($response);exit();
        $re = HuanXinHelper::userStatus('13391998802');
        var_dump($re);
//        $re = HuanXinHelper::userStatus('13391998819');
//        var_dump($re);

    }
    public function actionAbc()
    {
        $orders = Seek::find()->joinWith('category')->where(['i500_seek_help.id' => '12'])->asArray()->all();
        var_dump($orders);exit();
        $model = new UserVerifyCode();
        $data = ['mobile'=>'18618359358', 'code'=>1111];
        $re = $model->insertInfo($data);
        //var_dump($re);
        exit();
    }
}