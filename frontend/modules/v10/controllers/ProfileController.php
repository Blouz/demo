<?php

namespace frontend\modules\v10\controllers;


use frontend\models\i500_social\User;
use common\helpers\Common;
use common\helpers\RequestHelper;

/**
 * Profile
 *
 * @category Social
 * @package  Profile
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class ProfileController extends BaseController
{
    public function afterAction($action,$result)
    {
        $this->response();
    }
    /**
     * 设置推送ID
     * @return array
     */
    public function actionSetPushId()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        $push_id = RequestHelper::post('push_id', '', '');
        $dev = RequestHelper::post('dev', '0', 'intval');
        if (empty($mobile) || !Common::validateMobile($mobile) || empty($push_id) || !in_array($dev, [1, 2])) {
            $this->result['code'] = 422;
            $this->result['message'] = '无效的参数';
            return $this->result;
        }
        $model = User::findOne(['mobile'=>$mobile]);
        if (empty($model)) {
            $this->returnJsonMsg('404', [], Common::C('code', '404'));
        }
        $channel_id = $dev.'-'.$push_id;
        $model->xg_channel_id = $channel_id;
        $update_rs = $model->save();
        if (!$update_rs) {
            $this->returnJsonMsg('500', [], '服务器繁忙');
        }
        User::updateAll(['xg_channel_id'=>'0'],['and',['xg_channel_id'=>$channel_id],['<>','mobile',$mobile]]);
    }
}
