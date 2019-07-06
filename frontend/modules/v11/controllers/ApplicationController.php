<?php

/* 
 * 
 * @category  Social
 * @package   Post
 * @author    wangleilei <wangleilei@i500m.com>
 * @time      2017
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wangleilei@i500m.com
 */

namespace frontend\modules\v11\controllers;


use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500m\OpenUserCity;
use frontend\models\i500m\City;
use frontend\models\i500m\CommunityInfo;
use yii\db\Query;
use common\helpers\CurlHelper;

class ApplicationController extends BaseController
{
    public function actionApplyUserNum()
    {
        $city_id = RequestHelper::post('city_id', '', '');
        if (empty($city_id)) {
            $this->returnJsonMsg('799', [],'城市id不能为空');
        }
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $apply = UserBasicInfo::find()->select(['apply'])->where(['mobile'=>$mobile])->scalar();
        $res = OpenUserCity::find()->select(['num'])->where(['city_id'=>$city_id])->scalar();
        if($res==FALSE)
        {
            $res = '0';
        }
        $result = array('number'=>$res,'apply'=>$apply);
        $json_array = array();
        $json_array[] = $result;
        $this->returnJsonMsg('200', $json_array, Common::C('code', '200'));
    }
    public function actionApply()
    {
        $city_id = RequestHelper::post('city_id', '', '');
        if (empty($city_id)) {
            $this->returnJsonMsg('799', [],'城市id不能为空');
        }
        $district_id = RequestHelper::post('district_id', '', '');
        if(empty($district_id))
        {
            $district_id = 0;
        }
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $res = 0;
        $is_open = OpenUserCity::find()->select(['id','num','is_open'])->where(['city_id'=>$city_id])->asArray()->one();
        
        $province_id = City::find()->select(['province_id'])->where(['id'=>$city_id])->scalar();
        
        if(!empty($is_open))
        {
            if($is_open['is_open']=='0')
            {
                $num = (int)$is_open['num'] + 1;
                $res = OpenUserCity::updateAll(['num'=>$num],['city_id'=>$city_id]);
            }
            else
            {
                $this->returnJsonMsg('899', [],'该城市已开通');
            }
        }
        else
        {
            $connection = \Yii::$app->db_500m;
            $transaction = $connection->beginTransaction();
            $res = $connection->createCommand()->insert('open_user_city', ['num' => 1,'city_id' => $city_id,'province_id'=>$province_id])->execute();
            
            $count = OpenUserCity::find()->select(['id'])->where(['city_id'=>$city_id])->count();
            if($count>1)
            {
               $transaction->rollBack();
            }
            else
            {
               $transaction->commit();              
            }
            
        }
        UserBasicInfo::updateAll(['district_id'=>$district_id,'city_id'=>$city_id,'province_id'=>$province_id,'apply'=>1],['mobile'=>$mobile]);
        User::updateAll(['step'=>10],['mobile'=>$mobile]);
        
        $json_array = array();
        $json_array[]['is_success'] = $res;
        $this->returnJsonMsg('200', $json_array, Common::C('code', '200'));
    }
    public function actionIsOpen()
    {
        $city_id = RequestHelper::post('city_id', '', '');
        if (empty($city_id)) {
            $this->returnJsonMsg('799', [],'城市id不能为空');
        }

        $res = OpenUserCity::find()->select(['is_open'])->where(['city_id'=>$city_id])->scalar();
        if(empty($res)){
            $res = "0";
        }
        $json_array = array();
        $json_array[]['is_open'] = $res;

        $this->returnJsonMsg('200', $json_array, Common::C('code', '200'));
    }
    public function actionCityIsOpen()
    {
        $city_id = RequestHelper::post('city_id', '', '');
        if (empty($city_id)) {
            $this->returnJsonMsg('799', [],'城市id不能为空');
        }
        $lng = RequestHelper::post('lng', '', '');

        $lat = RequestHelper::post('lat', '', '');

        $dis = RequestHelper::post('dis', '3', 'intval');
        $res = OpenUserCity::find()->select(['is_open'])->where(['city_id'=>$city_id])->scalar();
        if(empty($res)){
            $res = "0";
        }
        $json_array = array();
        $json_array[]['is_open'] = $res;
        
        $url = \Yii::$app->params['channelHost'] . 'lbs/near-community?lng='.$lng.'&lat='.$lat.'&dis='.$dis;
        $comm = CurlHelper::get($url);
        if($comm==NULL)
        {
            $comm = array('code'=>'200','data'=>array(),'message'=> '未检索到小区');
        }
        $json_array[]['community'] = $comm;
        $this->returnJsonMsg('200', $json_array, Common::C('code', '200'));
    }
    //判断城市是否开通，开通则返回附近小区列表
    public function actionCityIsOpenNew()
    {
        $city_id = RequestHelper::post('city_id', '', '');
        if (empty($city_id)) {
            $this->returnJsonMsg('799', [],'城市id不能为空');
        }
        $lng = RequestHelper::post('lng', '', '');

        $lat = RequestHelper::post('lat', '', '');

        $dis = RequestHelper::post('dis', '3', 'intval');
        $res = OpenUserCity::find()->select(['is_open'])->where(['city_id'=>$city_id])->scalar();
        if(empty($res)){
            $res = "0";
        }
        $json_array['is_open'] = $res;
        
        $url = \Yii::$app->params['channelHost'] . 'lbs/near-community?lng='.$lng.'&lat='.$lat.'&dis='.$dis;
        $comm = CurlHelper::get($url);
        if($comm==NULL)
        {
            $comm = array('code'=>'200','data'=>array(),'message'=> '未检索到小区');
        }
        $json_array['community'] = $comm;
        $this->returnJsonMsg('200', [$json_array], Common::C('code', '200'));
    }
    public function actionTabSelected()
    {
        $community_id = RequestHelper::post('community_id', '', '');
        if (empty($community_id)) {
            $this->returnJsonMsg('899', [],'社区id不能为空');
        }
        $res = CommunityInfo::find()->select(['tab_selected'])->where(['comm_id'=>$community_id])->scalar();
        if($res==false)
        {
            $result = array(array('tab'=>'1'));
        }
        else
        {
            $result = array(array('tab'=>$res));
        }
        $this->returnJsonMsg('200', $result, Common::C('code', '200'));
    }
}