<?php
/**
 * 用户表
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   用户表
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-11 下午1:47
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */

namespace frontend\models\i500m;

/**
 * 用户表
 *
 * @category MODEL
 * @package  Social
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class YpUser extends I500Base
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%yp_user}}';
    }
}