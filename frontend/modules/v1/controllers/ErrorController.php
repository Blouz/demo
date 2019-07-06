<?php
/**
 * 错误提示
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   ERROR
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/06
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */

namespace frontend\modules\v1\controllers;

use yii\web\Controller;

/**
 * ERROR
 *
 * @category Social
 * @package  ERROR
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class ErrorController extends Controller
{
    /**
     * 错误页面提示
     * @return string
     */
    public function actionIndex()
    {
        $arr = array(
            'code' => '404',
            'data' => [],
            'message' => '抱歉，请求方法不存在',
        );
        echo json_encode($arr);
    }
}
