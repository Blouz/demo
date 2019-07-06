<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\modules\v8\controllers;

use common\helpers\FastDFSHelper;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\UserBasicInfo;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\db\Query;

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
	public function actionEdit()
	{
		$personal_sign = RequestHelper::post('personal_sign', '', ''); 
		$nickname = RequestHelper::post('nickname', '', ''); 
		if(empty($nickname))
		{
		   $this->returnJsonMsg('6000',[], '昵称不能为空');	
		}
		$sex = RequestHelper::post('sex', '', ''); 
		$birthday = RequestHelper::post('birthday', '', ''); 
		$avatar = RequestHelper::post('avatar', '', ''); 
		$mobile = RequestHelper::post('mobile', '', ''); 

		$field['personal_sign'] = $personal_sign;
		$field['nickname'] = $nickname;
		$field['sex'] = $sex;
		$field['birthday'] = $birthday;
		if(!empty($avatar))
		{
			$field['avatar'] = $avatar;
		}
		$res = UserBasicInfo::updateAll($field,['mobile'=>$mobile]);
		$this->returnJsonMsg('200', $res, Common::C('code','200','data','[]'));
	}
}