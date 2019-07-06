<?php
/**
 * 上传坐标接口
 *
 * PHP Version 10
 *
 * @category  Social
 * @package   Service
 * @author    yaoxin <yaoxin@i500m.com>
 * @time      2017/04/06
 * @license   http://www.i500m.com license
 * @link      yaoxin@i500m.com
 */
namespace frontend\modules\v10\controllers;

use Yii;
use yii\db\Query;
use yii\data\Pagination;
use common\helpers\Common;
use yii\helpers\ArrayHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500m\Community;

class UploadCoorController extends BaseController
{
    /**
     * 用户积分详细
     * @return Array()
    **/
    public function actionIndex()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        
        $lng1 = RequestHelper::post('lng', '', '');
        if (empty($lng1)) {
            $this->returnJsonMsg('801', [], Common::C('code', '801'));
        }
        $lat1 = RequestHelper::post('lat', '', '');
        if (empty($lat1)) {
            $this->returnJsonMsg('802', [], Common::C('code', '902'));
        }

        $cid = UserBasicInfo::find()->select(['last_community_id'])->where(['mobile'=>$mobile])->scalar();
        if(empty($cid)) {
            $this->returnJsonMsg('6008', [], '用户未曾选择小区');
        }
        $coor = Community::find()->select(['lng', 'lat'])->where(['id'=>$cid])->asArray()->one();
        if(empty($coor['lng']) || empty($coor['lat'])) {
            $this->returnJsonMsg('600', [], Common::C('code', '600'));
        }
        $lng2 = $coor['lng'];
        $lat2 = $coor['lat'];
        //计算两个地点距离，单位：m
        $range = $this->_getdistance($lng1,$lat1,$lng2,$lat2);
        //var_dump((int)$range);exit;
        if((int)$range > 2000) {
            $this->returnJsonMsg('6009', [], '用户未在所选小区');
        }else {
            $res = UserBasicInfo::updateAll(['lng'=>$lng1, 'lat'=>$lat1],['mobile'=>$mobile]);
            if(!$res) {
                $this->returnJsonMsg('6007', [], '用户已上传过该经纬度');
            }
        }

        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
    public function _getdistance($lng1,$lat1,$lng2,$lat2){
        //将角度转为狐度
        $radLat1=deg2rad($lat1);//deg2rad()函数将角度转换为弧度
        $radLat2=deg2rad($lat2);
        $radLng1=deg2rad($lng1);
        $radLng2=deg2rad($lng2);
        $a=$radLat1-$radLat2;
        $b=$radLng1-$radLng2;
        $s=2*asin(sqrt(pow(sin($a/2),2)+cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)))*6378.137*1000;
        return $s;
    }
}

?>