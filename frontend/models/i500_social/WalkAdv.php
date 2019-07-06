<?php
/**
 * 计步广告表
 * PHP Version 5
 * @category  MODEL
 * @package   Social
 * @author    wyy <wyy@iyangpin.com>
 * @time      2017/08/01
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wyy@i500m.com
 */

namespace frontend\models\i500_social;


class WalkAdv extends SocialBase
{
    /**
     * 表连接
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_walk_adv}}";
    }
}