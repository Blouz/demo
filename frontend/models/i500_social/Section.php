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

namespace frontend\models\i500_social;

use common\helpers\Common;

class Section extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_section}}';
    }
}