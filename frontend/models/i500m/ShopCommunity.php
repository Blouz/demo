<?php
/**
 * 店铺小区
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    linxinxinliang <linxinxinliang@iyangpin.com>
 * @time      2015-10-23
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinxinliang@iyangpin.com
 */
namespace frontend\models\i500m;

/**
 * 店铺小区
 *
 * @category MODEL
 * @package  Social
 * @author   linxinxinliang <linxinxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinxinliang@iyangpin.com
 */
class ShopCommunity extends I500Base
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%shop_community}}';
    }
}
