<?php
/**
 * 交易明细
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-25 上午9:47
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v4\controllers;

use common\libs\Account;
use common\libs\Balance;
use frontend\controllers\RestController;
use frontend\models\i500_social\AccountDetail;
use frontend\models\i500_social\UserWithdrawal;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use yii\base\DynamicModel;
use yii\helpers\ArrayHelper;

class AccountController extends RestController
{
    public $modelClass = 'frontend\models\i500_social\AccountDetail';

    public function actions()
    {
        $actions = parent::actions();
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        unset($actions['delete']);
        return $actions;
    }

    /**
     * 交易明细
     * @return array
     */
    public function prepareDataProvider()
    {
        $mobile = RequestHelper::get('mobile', '', '');
        $type = RequestHelper::get('type', '', 'intval');
        $id = RequestHelper::get('id', '', 'intval');
        if (empty($mobile)) {
            $this->result['code'] = 604;
            $this->result['message'] = Common::C('code', '604');
        }
        if (!Common::validateMobile($mobile)) {
            $this->result['code'] = 605;
            $this->result['message'] = Common::C('code', '605');
        }
        $map = ['mobile'=>$mobile];
        if (!empty($type)) {
            $map['type'] = $type;
        }
        $map['status'] = 1;
        //var_dump($map);
        $model = new $this->modelClass;
        $data = $model->getPageItem($map, []);
        //$data = $this->findAll($map);
            // var_dump($data);exit();

        $this->result['data'] = $data;
        return $this->response();
    }

    /**
     * 查看描述
     * @return array
     */
    public function actionView()
    {
        $mobile = RequestHelper::get('mobile', '', '');
        $id = RequestHelper::get('id', '', 'intval');
        if (empty($mobile)) {
            $this->result['code'] = 604;
            $this->result['message'] = Common::C('code', '604');
        }
        if (!Common::validateMobile($mobile)) {
            $this->result['code'] = 605;
            $this->result['message'] = Common::C('code', '605');
        }
        $map = ['mobile'=>$mobile, 'id'=>$id];
        $this->result['data'] = $this->findModel($map);
        ///$this->result['data'] = $data;
        return $this->response();
    }
    /**
     * 提现申请
     * mobile total brank_card
     */
    public function actionWithdrawals()
    {
        $data = Yii::$app->request->post();
        $model = new AccountDetail();
        $data['order_sn'] = Common::createSn(35, $data['mobile']);
        if (empty($data['order_sn'])) {
            $this->result['code'] = 500;
            $this->result['message'] = 'channel网络繁忙';
            return $this->result;
        }
        if (empty($data['bank_card'])) {
            $this->result['code'] = 422;
            $this->result['message'] = '银行卡必须';
            return $this->result;
        }
        if (empty($data['real_name'])) {
            $this->result['code'] = 422;
            $this->result['message'] = '开户姓名必须';
            return $this->result;
        }
        if (empty($data['price'])) {
            $this->result['code'] = 422;
            $this->result['message'] = '请输入提现金额';
            return $this->result;
        }
        $extra_info = [
            'bank_card'=>$data['bank_card'],
            'real_name'=>$data['real_name'],
        ];
        $data = [
            'mobile'=>$data['mobile'],
            'order_sn'=>$data['order_sn'],
            'price'=>$data['price'],
            'extra_info'=>json_encode($extra_info),
        ];
        $account = new Account($data);
        $re = $account->withdraw();
        if ($re == false) {
            $this->result['code'] = 422;
            $this->result['message'] = $account->error;
        }
        return $this->result;

    }
}