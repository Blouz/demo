<?php
/**
 * 用户银行卡相关
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   UserBankCard
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/9/21
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v1\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\UserBankCard;
use frontend\models\i500_social\UserVerifyCode;

/**
 * 用户银行卡相关
 *
 * @category Social
 * @package  UserBankCard
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class BankcardController extends BaseController
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
     * 新增
     * @return array
     */
    public function actionAdd()
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
        $data['real_name'] = RequestHelper::post('real_name', '', '');
        if (empty($data['real_name'])) {
            $this->returnJsonMsg('1100', [], Common::C('code', '1100'));
        }
        $data['bank_card'] = RequestHelper::post('bank_card', '', '');
        if (empty($data['bank_card'])) {
            $this->returnJsonMsg('1101', [], Common::C('code', '1101'));
        }
        //@todo 验证是否是合法的银行卡号
        $code = RequestHelper::post('code', '', '');
        if (empty($code)) {
            $this->returnJsonMsg('608', [], Common::C('code', '608'));
        }
        $bank_card_model = new UserBankCard();
        $where['bank_card'] = $data['bank_card'];
        $info = $bank_card_model->getInfo($where, true, 'id');
        if (!empty($info)) {
            $this->returnJsonMsg('1102', [], Common::C('code', '1102'));
        }
        $user_verify_code_model = new UserVerifyCode();
        $user_verify_code_where['mobile'] = $data['mobile'];
        $user_verify_code_where['code']   = $code;
        $user_verify_code_where['type']   = '5';
        $user_verify_code_fields = 'id,expires_in';
        $user_verify_code_info = $user_verify_code_model->getInfo($user_verify_code_where, true, $user_verify_code_fields, '', 'id desc');
        if ($user_verify_code_info) {
            if (strtotime($user_verify_code_info['expires_in']) < time()) {
                $this->returnJsonMsg('609', [], Common::C('code', '609'));
            }
        } else {
            $this->returnJsonMsg('610', [], Common::C('code', '610'));
        }
        $rs = $bank_card_model->insertInfo($data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 描述判断是否添加过
     * @return array
     */
    public function actionCheckAdd()
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
        $where['bank_card'] = RequestHelper::post('bank_card', '', '');
        if (empty($where['bank_card'])) {
            $this->returnJsonMsg('1101', [], Common::C('code', '1101'));
        }
        //@todo 验证是否是合法的银行卡号
        $bank_card_model = new UserBankCard();
        $info = $bank_card_model->getInfo($where, true, 'id');
        if (!empty($info)) {
            $this->returnJsonMsg('1102', [], Common::C('code', '1102'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
    /**
     * 卡信息
     * @return array
     */
    public function actionCardInfo()
    {
        $data['uid'] = RequestHelper::get('uid', '', '');
        if (empty($data['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $data['mobile'] = RequestHelper::get('mobile', '', '');
        if (empty($data['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($data['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['bank_card'] = RequestHelper::get('bank_card', '', '');
        if (empty($where['bank_card'])) {
            $this->returnJsonMsg('1101', [], Common::C('code', '1101'));
        }
        //@todo 验证是否是合法的银行卡号
        //@todo 通过银行卡号获取卡信息
        $arr['card_type'] = '借记卡';
        $arr['card_holder'] = '陈奕迅';
        $arr['card_validity'] = '2019-10-09';
        $arr['card_id'] = '998787666567654456';
        $arr['card_cvv2'] = '989';
        $arr['card_mobile'] = $data['mobile'];
        $this->returnJsonMsg('200', $arr, Common::C('code', '200'));
    }

    /**
     * 列表
     * @return array
     */
    public function actionList()
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
        $bank_card_model = new UserBankCard();
        $bank_card_fields = 'real_name,bank_card,status';
        $list = $bank_card_model->getList($where, $bank_card_fields, 'id desc');
        if (empty($list)) {
            $this->returnJsonMsg('1103', [], Common::C('code', '1103'));
        }
        $rs_list = [];
        foreach ($list as $k => $v) {
            $rs_list[$k]['real_name'] = $v['real_name'];
            $rs_list[$k]['bank_card'] = $v['bank_card'];
            $rs_list[$k]['tail_number'] = substr($v['bank_card'], -4);
            $rs_list[$k]['status'] = $v['status'];
            $rs_list[$k]['card_type'] = '借记卡';
            $rs_list[$k]['bank_name'] = '招商银行';
            $rs_list[$k]['bank_ico'] = 'http://www.cmbchina.com/images/main_logo.gif';
        }
        $this->returnJsonMsg('200', $rs_list, Common::C('code', '200'));
    }

    /**
     * 解除绑定
     * @return array
     */
    public function actionRemoveBind()
    {
        $where['uid'] = RequestHelper::post('uid', '', '');
        if (empty($where['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['bank_card'] = RequestHelper::post('bank_card', '', '');
        if (empty($where['bank_card'])) {
            $this->returnJsonMsg('1101', [], Common::C('code', '1101'));
        }
        $bank_card_model = new UserBankCard();
        $info = $bank_card_model->getInfo($where, true, 'id');
        if (empty($info)) {
            $this->returnJsonMsg('1104', [], Common::C('code', '1104'));
        }
        $update_data['status'] = '2';
        $update_data['update_time'] = date('Y-m-d H:i:s', time());
        $rs = $bank_card_model->updateInfo($update_data, $where);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 删除
     * @return array
     */
    public function actionDel()
    {
        $where['uid'] = RequestHelper::post('uid', '', '');
        if (empty($where['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['bank_card'] = RequestHelper::post('bank_card', '', '');
        if (empty($where['bank_card'])) {
            $this->returnJsonMsg('1101', [], Common::C('code', '1101'));
        }
        $bank_card_model = new UserBankCard();
        $info = $bank_card_model->getInfo($where, true, 'id');
        if (empty($info)) {
            $this->returnJsonMsg('1104', [], Common::C('code', '1104'));
        }
        $rs = $bank_card_model->delOneRecord($where);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
}
