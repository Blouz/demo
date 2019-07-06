<?php
/**
 * 测试用页面、接口
 *
 * PHP Version 5
 *
 * @category  SOCIAL
 * @package   CONTROLLER
 * @author    zhengyu <zhengyu@iyangpin.com>
 * @time      15/9/23 14:40
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      zhengyu@iyangpin.com
 */

namespace frontend\modules\v3\controllers;

use frontend\models\i500_social\UserVerifyCode;
use Yii;
use yii\web\Controller;
use common\helpers\RequestHelper;
use common\helpers\ZcommonHelper;


/**
 * 测试用页面、接口
 *
 * @category SOCIAL
 * @package  CONTROLLER
 * @author   zhengyu <zhengyu@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     zhengyu@iyangpin.com
 */
class TestController extends BaseController
{
    private $_icomet_url_admin = '';
    private $_icomet_url_user = '';

    /**
     * Action之前的处理
     *
     * Author zhengyu@iyangpin.com
     *
     * @param \yii\base\Action $action action
     *
     * @return bool
     *
     * @throws \yii\web\BadRequestHttpException
     */
//    public function beforeAction($action)
//    {
//        $this->_icomet_url_admin = Yii::$app->params['icomet_url_admin'];
//        $this->_icomet_url_user = Yii::$app->params['icomet_url_user'];
//
//        $this->enableCsrfValidation = false;
//        return parent::beforeAction($action);
//    }


    /**
     * 页面-发布信息
     *
     * Author zhengyu@iyangpin.com
     *
     * @return void
     */
    public function actionPublish()
    {
        $arr_view = array();
        echo $this->renderPartial('publish', $arr_view);
        return;
    }


    /**
     * Ajax
     *
     * Author zhengyu@iyangpin.com
     *
     * @return void
     */
    public function actionAjax()
    {
        $act = RequestHelper::get('act', '', 'trim');
        if ($act == 'pub') {
            $this->_zPub();
            return;
        } elseif ($act == 'sign') {
            $this->_zSign();
            return;
        } elseif ($act == 'xxxx') {
        } else {
        }
        return;
    }


    /**
     * Ajax-sign
     *
     * Author zhengyu@iyangpin.com
     *
     * @return void
     */
    private function _zSign()
    {
        $zhelper = new ZcommonHelper();

        $cname = RequestHelper::post('cname', 'ztestchdefault', 'trim');
        $expire = RequestHelper::post('expire', 60, 'intval');

        //sign
        $url_sign = $this->_icomet_url_admin . "sign?cname=" . $cname . "&expires=" . $expire;
        if ($str_sign_result = $zhelper->zcurl('get', $url_sign)) {
            if ($zhelper->zcheckJson($str_sign_result) === false) {
                //sign 失败
                $arr_return = array('result' => 0, 'msg' => 'sign失败', 'data' => array($str_sign_result));
                echo json_encode($arr_return);
                return;
            }
            //sign 成功
            $arr_sign_result = json_decode($str_sign_result, true);
            $token = $arr_sign_result['token'];
            $sub_timeout = $arr_sign_result['sub_timeout'];

            //sub 0
            $url_sub = $this->_icomet_url_user . "sub?cname=" . $cname . "&seq=0&token=" . $token;
            if ($str_sub_result = $zhelper->zcurl('get', $url_sub)) {
                //sub 正常
                if ($zhelper->zcheckJson($str_sub_result) === false) {
                    //sub 返回非json
                    $arr_return = array('result' => 0, 'msg' => 'sub失败', 'data' => array($str_sub_result));
                    echo json_encode($arr_return);
                    return;
                } else {
                    //sub 返回json
                    $arr_sub_result = json_decode($str_sub_result, true);
                    if ($arr_sub_result['type'] == 'next_seq') {
                        //目标达到
                        $next_seq = intval($arr_sub_result['seq']);
                        $arr_return = array(
                            'result' => 1,
                            'msg' => '发布成功',
                            'data' => array(
                                'cname' => $cname,
                                'token' => $token,
                                'seq' => $next_seq,
                                'sub_timeout' => $sub_timeout,
                                'origin' => $str_sub_result,
                            )
                        );
                        echo json_encode($arr_return);
                        return;
                    } else {
                        //返回非所需
                        $arr_return = array('result' => 0, 'msg' => 'sub失败', 'data' => array($str_sub_result));
                        echo json_encode($arr_return);
                        return;
                    }
                }
            } else {
                //sub 异常
                $arr_return = array('result' => 0, 'msg' => 'sub失败', 'data' => array($str_sub_result));
                echo json_encode($arr_return);
                return;
            }
        } else {
            //异常
            $arr_return = array('result' => 0, 'msg' => 'sign异常');
            echo json_encode($arr_return);
            return;
        }
    }


    /**
     * Ajax-pub
     *
     * Author zhengyu@iyangpin.com
     *
     * @return void
     */
    private function _zPub()
    {
        $zhelper = new ZcommonHelper();

        $cname = RequestHelper::post('cname', 'ztestchdefault', 'trim');
        $content = RequestHelper::post('content', 'ztestcontentdefault', 'trim');

        $url_pub = $this->_icomet_url_admin . "pub?cname=" . $cname . "&content=" . rawurlencode($content);

        if ($str_pub_result = $zhelper->zcurl('get', $url_pub)) {
            if ($zhelper->zcheckJson($str_pub_result) === false) {
                //pub 返回非json
                $arr_return = array('result' => 0, 'msg' => 'pub失败', 'data' => array($str_pub_result));
                echo json_encode($arr_return);
                return;
            }
            //pub 返回json
            $arr_pub_result = json_decode($str_pub_result, true);
            if (isset($arr_pub_result['type']) && $arr_pub_result['type'] == 'ok') {
                //pub 成功
                $arr_return = array('result' => 1, 'msg' => 'pub成功', 'data' => array($str_pub_result));
                echo json_encode($arr_return);
                return;
            } else {
                //pub 失败
                $arr_return = array('result' => 0, 'msg' => 'pub失败', 'data' => array($str_pub_result));
                echo json_encode($arr_return);
                return;
            }
        } else {
            //异常
            $arr_return = array('result' => 0, 'msg' => 'pub异常');
            echo json_encode($arr_return);
            return;
        }
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

