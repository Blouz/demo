<?php
namespace frontend\modules\v8\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\UserAddress;

/**
 * Address
 *
 * @category Social
 * @package  Address
 * @time      16/12/22
 * @author   xuxiaoyu <xuxiaoyu@i500m.com>
 * @license  http://www.i500m.com license
 * @link     xuxiaoyu@i500m.com
 */
class UserAddressController extends BaseController
{
    /**
     * 用户的地址信息集合
     * @return array
     */
    public function actionShowaddress()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('4005', [], '手机号不能为空');
        }
        $res = UserAddress::find()->select(['id', 'consignee', 'sex', 'consignee_mobile', 'search_address', 'details_address', 'create_time', 'is_default'])
                                  ->where(['mobile' => $mobile, 'is_deleted' => 2])
                                  ->orderBy('create_time DESC')
                                  ->asArray()
                                  ->all();
        if ($res) {
            $this->returnJsonMsg('200', $res, Common::C('code', '200', 'data', '[]'));
        } else {
            $this->returnJsonMsg('200', [], Common::C('code', '200', 'data', '[]'));
        }
    }
	

    /**
     * 设置默认地址
     * @return array
     */
    public function actionAddress()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('4005', [], '手机号不能为空');
        }
        $id = RequestHelper::post('id', '', '');
        if (empty($id)) {
            $this->returnJsonMsg('4006', [], '地址id不能为空');
        }
        $res = UserAddress::find()->select(['id', 'consignee', 'sex', 'consignee_mobile', 'search_address', 'details_address', 'create_time', 'is_default'])
                                  ->where(['mobile' => $mobile, 'is_deleted' => 2])
                                  ->orderBy('create_time DESC')
                                  ->asArray()
                                  ->all();
        if ($res) {
            $result = UserAddress::updateAll(['is_default' => 0], ['mobile' => $mobile]);
            $re = UserAddress::updateAll(['is_default' => 1], ['mobile' => $mobile, 'id' => $id]);
            $this->returnJsonMsg('200', [], Common::C('code', '200', 'data', '[]'));
        } else {
            $this->returnJsonMsg('4007', [], '没有查询到地址');
        }
    }
}

?>