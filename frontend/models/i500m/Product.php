<?php
/**
 * 商品表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      2015-08-25
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\models\i500m;

use yii\data\Pagination;

/**
 * 商品表
 *
 * @category MODEL
 * @package  Social
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class Product extends I500Base
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%product}}';
    }

    /**
     * 带分页的商品列表
     * @param array  $map      查询条件
     * @param string $field    查询字段
     * @param int    $pageSize 每页多少条
     * @param string $order    排序
     * @param int    $sort     升序降序
     * @return array
     */
    public function getGoodsList($map = [], $field = '*', $pageSize = 20, $order = 'id', $sort = SORT_DESC)
    {
        $query = $this->find()->where($map);
        $count = $query->count();
        $query->select($field);
        $pages = new Pagination(['totalCount' =>$count, 'pageSize' => $pageSize]);
        $list = $query->orderBy([$order=>$sort])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
        //var_dump($list);
        return ['list'=>$list, 'count'=>$count, 'pageCount'=>$pages->pageCount];
    }
}
