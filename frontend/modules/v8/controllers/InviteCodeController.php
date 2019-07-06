<?php
namespace frontend\modules\v8\controllers;
use Yii;
use common\helpers\Common;
use frontend\models\i500_social\InviteCode;
use frontend\models\i500_social\VerificationCode;
use common\helpers\RequestHelper;

class InviteCodeController extends BaseController
{
	/**
	 * 邀请码
	 *
	 * @category social
	 * @package  InviteCode
	 * @author   duzongyan <duzongyan@i500.com>
	 * @license  http://www.i500m.com/ license
	 * @link     duzongyan@i500.com
	 */
	public function actionIndex()
    {	
    	$mobile = RequestHelper::post('mobile', '', '');
    	// $mobile = 13464267232;
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $community_id = RequestHelper::post('community_id', 0, 'intval');
        // $community_id = 11240;
        if (empty($community_id)) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
    	$inviteCode = InviteCode::find()->select(['code','create_time'])->where(['mobile'=>$mobile,'community_id'=>$community_id])->asArray()->one();
        $verificationCode = VerificationCode::find()->select(['valid_days'])->asArray()->one();
    	if (empty($inviteCode)) {
    		$code = $this->generate_password();
    		$invite = new InviteCode();
    		$invite -> mobile = $mobile;
    		$invite -> community_id = $community_id;
            $invite -> code = $code;
    		$result = $invite -> save();
    		if ($result) {
    			$inviteCode = InviteCode::find()->select(['code'])->where(['mobile'=>$mobile,'community_id'=>$community_id])->asArray()->one();
    		}
    	} else if((time() - strtotime($inviteCode['create_time'])) > $verificationCode['valid_days']*3600*24) {
            $code = $this->generate_password();
            $create_time = date('Y-m-d H:i:s',time());
            $invite = new InviteCode();
            $result = $invite->updateAll(['code'=>$code,'create_time'=> $create_time],['mobile'=>$mobile,'community_id'=>$community_id]);
            if ($result) {
                $inviteCode = InviteCode::find()->select(['code'])->where(['mobile'=>$mobile,'community_id'=>$community_id])->asArray()->one();
            }    
        }

        $this->returnJsonMsg('200', $inviteCode['code'], Common::C('code', '200'));

    }

    //生成邀请码
	public function generate_password( $length = 6 ) {

	    // 密码字符集，可任意添加你需要的字符
	    $chars = 'abcdefghijklmnopqrstuvwxyz';
	    $password = '';
	    for ( $i = 0; $i < $length; $i++ )
	    {
	        // 这里提供两种字符获取方式
	        // 第一种是使用 substr 截取$chars中的任意一位字符；
	        // 第二种是取字符数组 $chars 的任意元素
	        // $password .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
	        $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
	    }

	    $res = InviteCode::find()->select(['id'])->where(['code'=>$password])->asArray()->one();
		if (!empty($res)) {
			$this->generate_password();
		}
	    return $password;
	}
}
?>