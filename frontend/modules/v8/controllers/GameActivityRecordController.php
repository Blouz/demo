<?php
/**
 * 中奖记录
 * User: Administrator
 * Date: 2016/10/18
 * Time: 11:23
 */

namespace frontend\modules\v8\controllers;

use common\helpers\BaseRequestHelps;
use common\helpers\Common;
use frontend\models\Base;
use frontend\models\i500_social\GameActivityRecord;
use frontend\models\i500_social\GameUserDetail;
class GameActivityRecordController extends BaseController
{
     /**
      * 中奖记录列表
      * @param string $mobile 手机号
      * @return array
      * @author huangdekui <huangdekui@i500m.com>
      */
    public function actionGameIndex()
    {
        $mobile = BaseRequestHelps::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $activity_id = BaseRequestHelps::post('activity_id','','');
         if(empty($activity_id)){
             $this->returnJsonMsg('511',[],Common::C('code','511'));
         }
        $gameactivityrecord = GameActivityRecord::find()->select(['id','price_id'])->where(['activity_id'=>$activity_id,'mobile'=>$mobile,'delete_flag'=>0])->andWhere(['>','price_id',0])->with(['gift'=>function($query) {
            $query->select(['id', 'name', 'image']);
        }])->with(['gameUserDetail'=>function($query) {
            $query->select(['record_id','user_name', 'mobile', 'user_address'])->where(['delete_flag'=>0]);
        }])->asArray()->all();
		
		//图片路径
		if(!empty($gameactivityrecord)){
			foreach($gameactivityrecord as $k => $v){
				$gameactivityrecord[$k]['gift']['image'] = \Yii::$app->params['imgHost'].$v['gift']['image']; 
			}
		}
        $this->returnJsonMsg('200',$gameactivityrecord,Common::C('code','200'));
    }

    /**
     * 添加收货地址
     * @param string $mobile 手机号
     * @return array
     * @author huangdekui <huangdekui@i500m.com>
     */
    public function actionGameAddress(){
        $user_name = BaseRequestHelps::post('user_name','','');
        if(empty($user_name)){
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }

        $mobile = BaseRequestHelps::post('mobile','','');
        if(empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $contact_mobile = BaseRequestHelps::post('contact_mobile','','');
        if(empty($contact_mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($contact_mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $user_address = BaseRequestHelps::post('user_address','','');
        if(empty($user_address)){
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        $record_id = BaseRequestHelps::post('record_id','','');
        if(empty($record_id)){
            $this->returnJsonMsg('511',[],Common::C('code','511'));
        }
        $model = new GameUserDetail();
        $model->record_id = $record_id;
        $model->mobile = $mobile;
        $model->contact_mobile = $contact_mobile;
        $model->user_address = $user_address;
        $model->user_name = $user_name;
        $res = $model->save();
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
}