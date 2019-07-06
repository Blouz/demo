<?php
/**
 * 露脸(http://www.v5.cn)
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   loulian
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      2015/8/12
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */
namespace frontend\modules\v10\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use common\helpers\Common;
use common\helpers\RequestHelper;
use common\helpers\LoulianHelper;
use common\helpers\CurlHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserToken;

/**
 * Class GuideController
 * @category  PHP
 * @author    yaoxin <yaoxin@i500m.com>
 * @license   http://www.i500m.com/ i500m license
 * @link      http://www.i500m.com/
 */
class LouLianController extends BaseController
{
	public function actionLoulianLogin()
	{
		$mobile = RequestHelper::post('mobile', '', '');
		if(empty($mobile)) {
			$this->returnJsonMsg('604', [], Common::C('code', '604'));
		}
		if(!Common::validateMobile($mobile)) {
			$this->returnJsonMsg('605', [], Common::C('code', '605'));
		}

		$user_info = UserBasicInfo::find()->select(['mobile', 'nickname', 'avatar'])->where(['mobile' => $mobile])->asArray()->one();
		//var_dump($user_info);exit;
		$loulian_re = LouLianHelper::llRegister ($user_info['mobile'], $user_info[ 'nickname' ] , $user_info[ 'avatar' ]);
		if(!empty($loulian_re) && $loulian_re[ 'error_code' ] == 2000) {
			$info['nickname'] = $user_info['nickname'];
			$info['avatar'] = $user_info['avatar'];
			$info['loulian'] = $loulian_re;
			$ress[] = $info;
			$this->returnJsonMsg('200', $ress, Common::C('code', '200'));
		}else{
			$this->returnJsonMsg('400', [], Common::C('code', '400'));
		}
	}
}

?>