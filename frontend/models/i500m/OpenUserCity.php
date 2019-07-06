<?php

/* 
 * 
 * @category  Social
 * @package   Post
 * @author    wangleilei <wangleilei@i500m.com>
 * @time      2017
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wangleilei@i500m.com
 */

namespace frontend\models\i500m;


class OpenUserCity extends I500Base
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%open_user_city}}';
    }
}