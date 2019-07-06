<?php
/**
 * 商品分类
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      2015-08-26
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\models\i500m;

/**
 * 商品分类
 *
 * @category MODEL
 * @package  Social
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class Category extends I500Base
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%category}}';
    }



    /**
     * 获取二级分类
     * @return \yii\db\ActiveQuery
     */
    public function getSec()
    {
        return $this->hasMany(Category::className(), ['parent_id'=>'id']);
    }
}
