<?php
/**
 * 帖子点赞表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015-08-11
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */

namespace frontend\models\i500_social;

/**
 * 帖子点赞表
 *
 * @category MODEL
 * @package  Social
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class PostThumbs extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_post_thumbs}}';
    }
	
	public function getUser()
    {
        return $this->hasOne(UserBasicInfo::className(), ['mobile' => 'mobile']);
    }

    public function getPost()
    {
        return $this->hasMany(Post::className(), ['id'=>'post_id']);
    }
}
