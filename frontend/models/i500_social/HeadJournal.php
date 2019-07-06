<?php
/**
 * 社区头条表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    yaoxin <yaoxin@i500.com>
 * @time      2017-05-13
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      yaoxin@i500.com
 */

namespace frontend\models\i500_social;
use yii\behaviors\TimestampBehavior;

/**
 * 社区头条表
 *
 * @category MODEL
 * @package  Social
 * @author   yaoxin <yaoxin@i500.com>
 * @license  http://www.i500m.com/ license
 * @link     yaoxin@i500.com
 */
class HeadJournal extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_head_journal}}';
    }
    
}
