<?php
/**
 * 服务分类表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015-09-16
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */

namespace frontend\models\i500_social;

/**
 * 服务分类表
 *
 * @category MODEL
 * @package  Social
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class ServiceCategory extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_service_category}}';
    }
    public function getChildList($cate, $name = 'child', $pid = 0){
        $arr = array();
        foreach($cate as $v){
            if($v['pid'] == $pid){

                $v[$name] = $this->getChildList($cate, $name, $v['id']);
                $arr[] = $v;

            }
        }
        return $arr;
    }
    public function fields()
    {
        return ['id', 'name', 'image', 'description'];
    }
	
	/**
     * 获取版块
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(ServiceCategory::className(), ['id'=>'category_id']);
    }
    /**
     * 获取版块下的话题
     * @return \yii\db\ActiveQuery
     */
    public function getCategorytopic()
    {
        return $this->hasMany(ServiceCategory::className(), ['pid'=>'id']);
    }
}
