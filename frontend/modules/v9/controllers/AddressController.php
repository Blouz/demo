<?php
/**
 *
 *
 * PHP Version 5
 *
 * @category  PHP
 * @filename  AddressController.php
 * @author    xuxiaoyu <xuxiaoyu@i500m.com>
 * @copyright 2015 www.i500m.com
 * @license   http://www.i500m.com/ i500m license
 * @datetime  17/2/16
 * @version   SVN: 1.0
 * @link      http://www.i500m.com/
 */

namespace frontend\modules\v9\controllers;

use frontend\models\i500m\Province;
use frontend\models\i500m\City;
use frontend\models\i500m\District;
use common\helpers\BaseRequestHelps;
use common\helpers\RequestHelper;
use common\helpers\Common;
use yii;

/**
 * Class AddressController
 * @category  PHP
 * @author    xuxiaoyu <xuxiaoyu@i500m.com>
 * @license   http://www.i500m.com/ i500m license
 * @link      http://www.i500m.com/
 */
class AddressController extends BaseController
{
    //选择省份
    public function actionProvince()
    {   
        $province = Province::find()->select(['id','name as province_name'])->asArray()->all();//status 1显示 0不显示
        $this->returnJsonMsg('200', $province, Common::C('code','200','data','[]'));
    }

    //查询城市
    public function actionCity(){
        $province_id = RequestHelper::post('province_id','','intval');
        $city = City::find()->select(['id as city_id','province_id','name as city_name'])->where(['province_id'=>$province_id])->asArray()->all();
        if (!empty($city)) {
            $this->returnJsonMsg('200', $city, Common::C('code','200','data','[]'));
        }
    }

    //查询县区
    public function actionDistrict(){
        $city_id = RequestHelper::post('city_id','','intval');
        $district = District::find()->select(['id as district_id','city_id','name as district_name'])->where(['city_id'=>$city_id])->asArray()->all();
        if (!empty($district)) {
            $this->returnJsonMsg('200', $district, Common::C('code','200','data','[]'));
        }
    }

    public function actionIndex()
    {
        $cate = new Province();
        $field=array();
        $field[]='id';
        $field[]='name as province_name';
        $result = $cate->find()->select($field)
                               ->where(['<>','id',35])
                               ->with(['city'=>function ($query){$query->select(['id','province_id','name as city_name']) 
                               ->with(['district'=>function ($query){$query->select(['id','city_id','name as district_name']);}]);}])
                               ->asArray()
                               ->all();

        $this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));

    }
}
