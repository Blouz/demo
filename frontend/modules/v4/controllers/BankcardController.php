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
namespace frontend\modules\v4\controllers;

use frontend\controllers\RestController;
use frontend\models\i500_social\BankCard;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
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
class BankcardController extends RestController
{
    public $modelClass = 'frontend\models\i500_social\BankCard';

    public function actions()
    {
        $actions = parent::actions();
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        unset($actions['delete']);
        return $actions;
    }
    public function prepareDataProvider()
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
        $map = ['mobile'=>$mobile];
        if (!empty($id)) {
            $map['id'] = $id;
            $data = $this->findModel($map);
            $data['image'] = '';
//            if (!empty($data)) {
//                $data['image'] = \common\libs\BankCard::getBankImg($data['bank']);
//            }


        } else {
            $data = $this->findAll($map);
           // var_dump($data);exit();
//            if (!empty($data)) {
//                foreach ($data as $k=>$v) {
//                    $data[$k]['image'] = '';
//                    if (!empty($v['bank'])) {
//                        $data[$k]['image'] = \common\libs\BankCard::getBankImg($v['bank']);
//                    }
//                }
//            }
        }

        $this->result['data'] = $data;
        //var_dump($list->toArray());
        return $this->response();
       // return $list;
    }

    /**
     * 删除
     * @return array
     */
    public function actionDelete()
    {
        $mobile = RequestHelper::get('mobile', '', '');
        $id = RequestHelper::get('id', '', 'intval');
        if (empty($id)) {
            $this->result['code'] = 604;
            $this->result['message'] = '无效的id';
        }
        if (empty($mobile)) {
            $this->result['code'] = 604;
            $this->result['message'] = Common::C('code', '604');
        }
        if (!Common::validateMobile($mobile)) {
            $this->result['code'] = 605;
            $this->result['message'] = Common::C('code', '605');
        }
        $map = ['id'=>$id, 'mobile'=>$mobile];


        $count  = $this->deleteAll($map);
        $this->result['data'] = $count;
        return $this->response();
        //$this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
}
