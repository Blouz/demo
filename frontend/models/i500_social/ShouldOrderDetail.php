<?php
/**
 * 供需订单明细表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    yaoxin <yaoxin@i500m.com>
 * @time      2017/06/21
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      yaoxin@i500m.com
 */
namespace frontend\models\i500_social;

class ShouldOrderDetail extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_should_order_detail}}';
    }
}