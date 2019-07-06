<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-24 上午11:47
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\models\i500m;

use frontend\models\i500_social\UserBasicInfo;

/**
 * 小区
 *
 * @category MODEL
 * @package  Social
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class Community extends I500Base
{
    /**
     * 小区表后缀
     *
     * @var string 小区表后缀，形如 _beijing
     */
    private static $_table_suffix = '_beijing';

    /**
     * 表名
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%community' . Community::$_table_suffix . '}}';
    }

    /**
     * 设置后缀
     *
     * Author zhengyu@iyangpin.com
     *
     * @param string $str 后缀
     *
     * @return void
     */
    public static function setSuffix($str)
    {
        Community::$_table_suffix = $str;
        return;
    }

    public function getUser(){
        return $this->hasOne(UserBasicInfo::className(),['last_community_id'=>'id']);
    }

    public function getProvince(){
        return $this->hasOne(Province::className(),['id'=>'province']);
    }

    public function getCity(){
        return $this->hasOne(City::className(),['id'=>'city']);
    }

    public function getDistrict(){
        return $this->hasOne(District::className(),['id'=>'district']);
    }
}

