<?php
/**
 * 钱包
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Wallet
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/9/22
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v1\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\UserWallet;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserCoupons;
use frontend\models\i500_social\UserWithdrawal;
use frontend\models\i500_social\UserBankCard;

/**
 * Wallet
 *
 * @category Social
 * @package  Wallet
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class WalletController extends BaseController
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
     * 获取钱包
     * @return array
     */
    public function actionGet()
    {
        $where['uid'] = RequestHelper::get('uid', '', '');
        if (empty($where['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::get('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $wallet_model = new UserWallet();
        $wallet_info = $wallet_model->getInfo($where, true, 'money,integral');
        if (empty($wallet_info)) {
            /**新增**/
            $wallet_add_data['uid']    = $where['uid'];
            $wallet_add_data['mobile'] = $where['mobile'];
            $add_rs = $wallet_model->insertInfo($wallet_add_data);
            if (!$add_rs) {
                $this->returnJsonMsg('400', [], Common::C('code', '400'));
            }
            $wallet_info['money']    = '0.00';
            $wallet_info['integral'] = '0';
        }
        $coupon_model = new UserCoupons();
        $coupon_where['mobile'] = $where['mobile'];
        $coupon_where['status'] = '0';
        $wallet_info['coupon_count'] = $coupon_model->getCount($coupon_where);
        $bank_card_model = new UserBankCard();
        $bank_card_where['mobile'] = $where['mobile'];
        $wallet_info['bankcard_count'] = $bank_card_model->getCount($bank_card_where);
        $this->returnJsonMsg('200', $wallet_info, Common::C('code', '200'));
    }

    /**
     * 获取账户余额
     * @return array
     */
    public function actionGetMoney()
    {
        $where['uid'] = RequestHelper::get('uid', '', '');
        if (empty($where['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::get('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $arr['scroll_news'] = '十一期间提现时间和往常不一样，详情点击查看规则';
        $arr['withdrawals_amount'] = '9000';
        $arr['all_amount'] = '9000.58';
        $arr['today_order_amount'] = '3000.00';
        $arr['unfinished_order_amount'] = '2500.00';
        $this->returnJsonMsg('200', $arr, Common::C('code', '200'));
    }

    /**
     * 提现
     * @return array
     */
    public function actionWithdrawal()
    {
        $data['uid'] = RequestHelper::post('uid', '', '');
        if (empty($data['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $data['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($data['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($data['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $data['bank_card'] = RequestHelper::post('bank_card', '', '');
        if (empty($data['bank_card'])) {
            $this->returnJsonMsg('1101', [], Common::C('code', '1101'));
        }
        //@todo 验证是否是合法的银行卡号
        $data['money'] = RequestHelper::post('money', '', '');
        if (empty($data['money'])) {
            $this->returnJsonMsg('1105', [], Common::C('code', '1105'));
        }
        //@todo 验证提现金额 是否超过总金额
        $data['real_name'] = $this->_getUserAuthName($data['mobile']);
        $data['expect_arrival_time'] = date('Y-m-d H:i:s', (time()+ Common::C('money_arrival_time')));
        $withdrawal_model = new UserWithdrawal();
        $rs = $withdrawal_model->insertInfo($data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        //@todo 执行成功 减少余额
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 提现列表
     * @return array
     */
    public function actionWithdrawalList()
    {
        $where['uid'] = RequestHelper::get('uid', '', '');
        if (empty($where['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::get('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $page      = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '6', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        $fields = 'id,money,create_time';
        $withdrawal_model = new UserWithdrawal();
        $list = $withdrawal_model->getPageList($where, $fields, 'id desc', $page, $page_size);
        if (empty($list)) {
            $this->returnJsonMsg('1108', [], Common::C('code', '1108'));
        }
        foreach ($list as $k => $v) {
            $list[$k]['status'] = '2';  //1="+" 2="-"
        }
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }

    /**
     * 提现详情
     * @return array
     */
    public function actionWithdrawalDetail()
    {
        $where['uid'] = RequestHelper::get('uid', '', '');
        if (empty($where['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::get('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['id'] = RequestHelper::get('withdrawal_id', '', '');
        if (empty($where['id'])) {
            $this->returnJsonMsg('1106', [], Common::C('code', '1106'));
        }
        //@todo 验证是否是合法的银行卡号
        $withdrawal_model = new UserWithdrawal();
        $fields = 'bank_card,money,status,create_time,expect_arrival_time,arrival_time';
        $info = $withdrawal_model->getInfo($where, true, $fields);
        if (empty($info)) {
            $this->returnJsonMsg('1107', [], Common::C('code', '1107'));
        }
        $info['tail_number'] = substr($info['bank_card'], -4);
        $info['bank_name']   = '中国银行';
        $this->returnJsonMsg('200', $info, Common::C('code', '200'));
    }

    /**
     * 获取用户认证名称
     * @param string $mobile 手机号
     * @return string
     */
    private function _getUserAuthName($mobile = '')
    {
        $real_name = '';
        if (!empty($mobile)) {
            $user_base_info_model = new UserBasicInfo();
            $user_base_info_where['mobile'] = $mobile;
            $user_base_info_fields = 'realname';
            $info = $user_base_info_model->getInfo($user_base_info_where, true, $user_base_info_fields);
            if (!empty($info)) {
                $real_name = $info['realname'];
            }
        }
        return $real_name;
    }
}
