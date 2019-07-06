<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-20 上午10:44
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v6\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\controllers\RestController;
use frontend\models\i500_social\Recruit;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserCommunity;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

class RecruitController extends BaseController
{
	
    public function actionRecruit(){
        $mobile=RequestHelper::post('mobile','','');
        if(empty($mobile)){
            $this->returnJsonMsg('604',[],Common::C('code','604'));
        }
        if(!Common::validateMobile($mobile)){
            $this->returnJsonMsg('605',[],Common::C('code','605'));
        }
        $recruit=UserBasicInfo::find()->select(['mobile','avatar','community_name','create_time','is_recruit'])->where(['mobile'=>$mobile])->with(['recruit'=>function($query){
            $query->select(['mobile','true_name','identity_card','identity_image','identity_photo_front','identity_photo_back','reason','status'])->orderBy('create_time desc')->one();;
        }])->with(['usercommunity'=>function($query){
            $query->select(['mobile','community_name']);
        }])->asArray()->one();
		
        if($recruit['is_recruit'] && $recruit['recruit']['status']==1){
            $data['is_rec']=1;
            $data['status']=$recruit['recruit']['status'];
			$data['avatar']=$recruit['avatar']; 
			$data['identity_card']=$recruit['recruit']['identity_card'];
			$data['true_name']=$recruit['recruit']['true_name'];
        }else{
            $data['is_rec']=0;
            $data['status']='';//默认为空
            if(!empty($recruit['recruit'])){
                if($recruit['recruit']['status'] == 0){
                    $data['status']=$recruit['recruit']['status'];
                    $data['true_name']=$recruit['recruit']['true_name'];
                    $data['mobile']=$recruit['mobile'];
                    $data['identity_card']=$recruit['recruit']['identity_card'];
                    $data['identity_image']=$recruit['recruit']['identity_image'];
                    $data['identity_photo_front']=$recruit['recruit']['identity_photo_front'];
                    $data['identity_photo_back']=$recruit['recruit']['identity_photo_back'];
                    $data['community_name']=$recruit['usercommunity']['community_name'];
                    $data['avatar']=$recruit['avatar']; 
                }
                if($recruit['recruit']['status']==2){
                    $data['status']=$recruit['recruit']['status'];
                    $data['reason']=$recruit['recruit']['reason'];
                }
            }
        }
        $this->returnJsonMsg('200',$data,Common::C('code','200'));
    }
}