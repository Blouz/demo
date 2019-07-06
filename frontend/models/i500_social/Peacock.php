<?php
/**
 * 开屏页
 *
 * PHP Version 5
 * 
 *
 * @category  I500M
 * @package   Member
 * @author    duzongyan <duzongyan@i500m.com>
 * @time      2017-01-10
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      duzongyan@i500m.com
 */
namespace frontend\models\i500_social;

/**
 * 开屏页表
 *
 * @category MODEL
 * @package  Social
 * @author   duzongyan <duzongyan@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     duzongyan@i500m.com
 */
class Peacock extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_peacock}}';
    }
}
