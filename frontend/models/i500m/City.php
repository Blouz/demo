<?php
/**
 * 简介：已开通省市管理model
 *
 * PHP Version 5
 * 文件介绍2
 *
 * @category  PHP
 * @package   I500
 * @filename  City.php
 * @author    zhoujun <lichenjun@iyangpin.com>
 * @copyright 2015 www.i500m.com
 * @license   http://www.i500m.com/ i500m
 * @datetime  15/4/20 下午16:30
 * @version   SVN: 1.0
 * @link      http://www.i500m.com/
 */


namespace frontend\models\i500m;

use Yii;
use yii\base\Model;
use common\helpers\CurlHelper;
use yii\db\ActiveRecord;
use linslin\yii2\curl;

/**
 * Class City
 * @category  PHP
 * @package   Admin
 * @author    zhoujun <lichenjun@iyangpin.com>
 * @copyright 2015 www
 * @license   http://www.i500m.com/ i500m license
 * @link      http://www.i500m.com/
 */
class City extends I500Base
{
    /**
     * 简介：
     * @author  zhoujun@iyangpin.com
     * @return array
     */
    public function attributeLabels()
    {
        return array(
            'city_name' => '开通城市名称',
            'status' =>'状态',
            'province'=>'省份',
            'city'=>'城市'
        );
    }
    /**
     * 简介：连接数据库
     * @author  lichenjun@iyangpin.com。
     * @return string
     */
    public static function tableName()
    {
        return '{{%city}}';
    }

    /**
     *  简介：rules
     * @return array
     */
    public function rules()
    {
        return [
            //不可为空的字段
            [['city_name','status'],'required'],
        ];
    }

    /**
     * 简介：查询出所有市
     * @param string $pid pid
     * @return array|\yii\db\ActiveRecord[]
     */
    public function city($pid)
    {
        $list = $this->find()
            ->select('id,name')
            ->where("province_id = $pid")
            ->asArray()
            ->all();
        return $list;
    }

    /**
     * 简介：查询出所有市
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all_city()
    {
        $list = $this->find()
            ->select('id,name')
            ->asArray()
            ->all();
        return $list;
    }
    /**
     * 简介：城市的信息
     * @param string $city_id city_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function city_one($city_id = null)
    {
        if ($city_id) {
            $list = $this->find()
                ->select('id,name')
                ->where("id = $city_id")
                ->asArray()
                ->one();
            return $list;
        } else {
            $city_id = 1;
            $list = $this->find()
                ->select('id,name')
                ->where("id = $city_id")
                ->asArray()
                ->one();
            return $list;
        }
    }
    /**
     * 简介：查询出所有市
     * @param string $id id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function city_all($id)
    {
        $list = $this->find()
            ->select('id,name')
            ->where("id=$id")
            ->asArray()
            ->all();
        return $list;
    }
    public function getDistrict(){
        return $this->hasMany(District::className(),['city_id'=>'id']);
    }
}
