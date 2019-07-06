<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-25 下午4:17
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\controllers;

use common\vendor\wxpay\PayApi;
use yii\web\Controller;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\ApiErrorLog;

class NotifyController extends Controller
{
    /**
     * 微信支付回调
     */
    public function actionWxRecharge()
    {
        file_put_contents('/tmp/new_txt.log',  "执行时间：".date('Y-m-d H:i:s')." 微信回调来了\n", FILE_APPEND);

        if (isset(file_get_contents('php://input'))) {
            file_put_contents('/tmp/new_wx_txt.log',  "执行时间：".date('Y-m-d H:i:s').var_export(file_get_contents('php://input'))." \n", FILE_APPEND);
        } else {
            file_put_contents('/tmp/new_txt.log',  "执行时间：".date('Y-m-d H:i:s')." 貌似没有数据\n", FILE_APPEND);
        }
        if (isset($_POST)) {
            file_put_contents('/tmp/new_wx_txt.log',  "执行时间post：".date('Y-m-d H:i:s').var_export($_POST)." \n", FILE_APPEND);
        }

        PayApi::handle();
        file_put_contents('/tmp/new_wx_txt.log',  "执行时间：".date('Y-m-d H:i:s')." 微信处理完毕\n", FILE_APPEND);
    }

    /**
     * 支付宝回调方法
     */
    public function actionAlipayRecharge()
    {
        if (isset($_POST)) {
            file_put_contents('/tmp/new_alipay_txt.log',  "执行时间post：".date('Y-m-d H:i:s').var_export($_POST)." \n", FILE_APPEND);
        }
        file_put_contents('/tmp/new_alipay_txt.log',  "执行时间：".date('Y-m-d H:i:s')." 支付宝处理完毕\n", FILE_APPEND);
    }
}
