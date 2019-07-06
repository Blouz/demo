<?php
namespace frontend\modules\v8\controllers;
use Yii;
use common\helpers\Common;
use frontend\models\i500_social\InviteCode;
use common\helpers\RequestHelper;
use frontend\models\i500m\Community;

class ValidateCodeController extends BaseController
{
	/**
     * 根据邀请码查找小区
     *
     * @category social
     * @package  InviteCode
     * @author   yaoxin <yaoxin@i500.com>
     * @license  http://www.i500m.com/ license
     * @link     yaoxin@i500.com
     */
    public function actionRelationCommunity()
    {
        $code = RequestHelper::post('code', '', '');
        if (empty($code)) {
            $this->returnJsonMsg('608', [], Common::C('code', '608'));
        }
        //查找小区ID
        $invitecode = new InviteCode();
        $community = $invitecode::find()->select(['community_id'])->where(['code' => $code])->asArray()->one();
        //根据小区ID查询其他信息
        $commun = new Community();
        $comm = $commun::find()->select(['name', 'id','city'])->where(['id'=> $community['community_id']])->asArray()->one();
        $community['community_id'] = $comm['id'];
        $community['community_name'] = $comm['name'];
        $community['community_city'] = $comm['city'];
        $comn[] = $community;
        $this->returnJsonMsg('200', $comn, Common::C('code', '200'));
    }
}
?>