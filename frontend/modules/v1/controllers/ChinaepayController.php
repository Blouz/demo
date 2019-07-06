<?php
/**
 * 【易付宝】相关充值业务订单表
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Chinaepay
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/9/09
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v1\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Chinaepay;
use frontend\models\i500_social\Order;

/**
 * 我的订单
 *
 * @category Social
 * @package  Chinaepay
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class ChinaepayController extends BaseController
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
     * 下单
     * @return array
     */
    public function actionAdd()
    {
        $uid = RequestHelper::post('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $order_model = new Order();
        $data['mobile']   = $mobile;
        //@todo 确定创建订单号为什么用省份？35=全国
        $data['order_sn'] = $order_model->createSn('35', $mobile);
        if (empty($data['order_sn'])) {
            $this->returnJsonMsg('1053', [], Common::C('code', '1053'));
        }
        $data['business_code'] = RequestHelper::post('business_code', '0', '');
        if (empty($data['business_code'])) {
            $this->returnJsonMsg('900', [], Common::C('code', '900'));
        }
        $data['total']         = RequestHelper::post('total', '0.00', '');
        $data['total_number']  = RequestHelper::post('total_number', '1', '');
        $data['business_type'] = RequestHelper::post('business_type', '0', '');
        if (empty($data['business_type']) || !$this->_checkBusinessType($data['business_type'])) {
            $this->returnJsonMsg('901', [], Common::C('code', '901'));
        }
        $data['source_type']   = RequestHelper::post('source_type', '0', '');
        if (empty($data['source_type']) || !in_array($data['source_type'], ['3','4'])) {
            $this->returnJsonMsg('902', [], Common::C('code', '902'));
        }
        $data['remark']   = RequestHelper::post('remark', '', '');
        $data['community_id'] = RequestHelper::post('community_id', '0', 'intval');
        if (empty($data['community_id'])) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        $data['community_city_id'] = RequestHelper::post('community_city_id', '0', 'intval');
        if (empty($data['community_city_id'])) {
            $this->returnJsonMsg('645', [], Common::C('code', '645'));
        }
        $order_model = new Chinaepay();
        $rs = $order_model->insertInfo($data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', ['order_sn'=>$data['order_sn']], Common::C('code', '200'));
    }

    /**
     * 检验业务类型
     * @param int $type_id 类型ID
     * @return bool
     */
    private function _checkBusinessType($type_id = 0)
    {
        if (in_array($type_id, ['1', '2', '3'])) {
            return true;
        }
        return false;
    }
}
