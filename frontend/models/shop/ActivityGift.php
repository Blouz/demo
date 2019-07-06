<?php
/**
 * 活动赠品表
 *
 * PHP Version 5
 * 活动赠品表
 *
 * @category  I500M
 * @package   Member
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      2015-08-28
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\models\shop;

/**
 * 商家活动赠品
 *
 * @category MODEL
 * @package  Social
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class ActivityGift extends ShopBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%shop_activity_gift}}';
    }
    /**
     * 减去库存
     * @param array  $map  查询条件
     * @param string $type 减or 加
     * @return bool
     */
    public function editNumber($map, $type = '-')
    {
        $model = $this->findOne($map);
        if ($type == '-') {
            $model->number = $model->number - 1;
        } elseif ($type == '+') {
            $model->number = $model->number + 1;
        }
        $re = $model->save();
        return $re;
    }
}
