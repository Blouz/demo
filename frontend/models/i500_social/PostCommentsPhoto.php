<?php
/**
 * 帖子评论图片表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    duzongyan <duzongyan@duzongyan.com>
 * @time      2017-04-14
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      duzongyan@duzongyan.com
 */

namespace frontend\models\i500_social;

/**
 * 帖子评论图片表
 *
 * @category MODEL
 * @package  Social
 * @author   duzongyan <duzongyan@duzongyan.com>
 * @license  http://www.i500m.com/ license
 * @link     duzongyan@duzongyan.com
 */
class PostCommentsPhoto extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_post_comments_photo}}';
    }
}
