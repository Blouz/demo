<?php
/**
 * 需求表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    xuxiaoyu <xuxiaoyu@i500.com>
 * @time      2017-02-16
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      xuxiaoyu@i500.com
 */

namespace frontend\models\i500_social;
use yii\behaviors\TimestampBehavior;

/**
 * 需求表
 *
 * @category MODEL
 * @package  Social
 * @author   xuxiaoyu <xuxiaoyu@i500.com>
 * @license  http://www.i500m.com/ license
 * @link     xuxiaoyu@i500.com
 */
class Guide extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_guide}}';
    }
    
}
