<?php
/**
 * 店铺分类
 *
 * PHP Version 5
 *
 * @category  I500M
 * @package   Member
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      2015-08-26
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\models\shop;

use frontend\models\i500m\Category;

/**
 * 商家活动
 *
 * @category MODEL
 * @package  Social
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class ShopCategory extends ShopBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%shop_category}}';
    }

    /**
     * 获取分类
     * @param int $shop_id 店铺ID
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getCategory($shop_id = 0)
    {
        $shop_category = $this->getList(['shop_id'=>$shop_id]);
        $category_ids = [];
        foreach ($shop_category as $k => $v) {
            $category_ids[] = $v['cate_first_id'];
        }
        $category = new Category();
        
        $item = $category->getList(['id'=>$category_ids], 'id,name');
        return $item;
    }
}
