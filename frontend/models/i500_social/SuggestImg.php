<?php
/**
 * 投诉建议图片表
 *
 * PHP Version 5
 * @category  MODEL
 * @package   Social
 * @author    wyy <wyy@i500m.com>
 * @time      2017/07/31
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wyy@i500m.com
 */

namespace frontend\models\i500_social;

class SuggestImg extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_suggest_img}}';
    }
}
