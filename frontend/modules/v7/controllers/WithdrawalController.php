<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\modules\v7\controllers;

///use frontend\controllers\RestController;
use frontend\models\i500_social\Recruit;
use frontend\models\i500m\Community;
use Yii;
use common\helpers\Common;
use common\helpers\SsdbHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserWithdrawal;
use frontend\models\i500_social\ServiceSetting;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\AccountDetail;
use yii\helpers\ArrayHelper;


class WithdrawalController extends BaseController
{
    public function actionAddrawal()
    {
        $connection = \Yii::$app->db_social;
        $mobile=RequestHelper::post('mobile', '', '');
        if(empty($mobile))
        {
            $this->returnJsonMsg('6001',[], '手机号不能为空');
        }
        $realname=RequestHelper::post('real_name', '', '');
        $bankcard=RequestHelper::post('bankcard', '', '');
        $money=RequestHelper::post('money', '', '');
        //$exparrvtime=RequestHelper::post('exparrvtime', '', '');
       // $arrvtime=RequestHelper::post('arrvtime', '', '');

		$withdrawal = new UserWithdrawal;
		
		$uid = User::find()->select(['id'])->where(['mobile'=>$mobile])->asArray()->one();
		$userid=$uid['id'];
		$withdrawal->uid=$userid;
		$withdrawal->mobile=$mobile;
		$withdrawal->real_name=$realname;
		$withdrawal->bank_card=$bankcard;
		$withdrawal->money=$money;
//        $withdrawal->expect_arrival_time=$exparrvtime;
//        $withdrawal->arrival_time=$arrvtime;
//          
		$order_sn = Common::createSn(35, $mobile);
		if(empty($order_sn)) 
		{
			$this->result['code'] = 500;
			$this->result['message'] = 'channel网络繁忙';
			return $this->result;
		}
	   
		$withdrawal->order_sn = $order_sn;
		$transaction = $connection->beginTransaction();
		$res=$withdrawal->save();
		$result=$withdrawal->primaryKey;
		if($result>0)
		{
			$balance = UserBasicInfo::find()->select(['can_amount'])->where(['mobile'=>$mobile])->asArray()->one();
			$total = $balance['can_amount']-$money;
			
			$accDetail = new AccountDetail();
			$accDetail->mobile = $mobile;
			$accDetail['type'] = 6;
			$accDetail['price'] = -$money;
			$accDetail['amount'] = $total;
			$accDetail['remark'] = 'i500账户提现';
			$accDetail['create_time'] = date("Y-m-d H:i:s");
			$accDetail['order_sn'] =   $order_sn;
			$accDetail['status'] = 1;
			$accDetail['pay_method'] = 3;
//                //$accDetail['extra_info'] = "";
			$re = $accDetail->save();
			$prid = $accDetail->primaryKey;
			if($prid>0)
			{
				$upd = $connection->createCommand("update i500_user_basic_info set can_amount='$total' where mobile='$mobile'")->execute();
				if($upd>0)
				{
					$transaction->commit();
					$this->returnJsonMsg('200', $upd, Common::C('code','200','data','[]'));
				}
				else 
				{
					$transaction->rollBack();
				}
			}
			else
			{
				$transaction->rollBack();
			}
		}
		else
		{
			$this->returnJsonMsg('6002',[], '提交请求失败');
		}
            
//        }   
    }
}