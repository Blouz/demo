<?php
/**
 * 样品商家位置表
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-6 下午1:11
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\models\i500m;

/**
 * 商家位置信息
 *
 * @category MODEL
 * @package  Social
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class SupplierLocation extends I500Base
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%yp_supplier_location}}';
    }
}