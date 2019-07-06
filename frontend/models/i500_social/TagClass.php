<?php
/**
 * 标签相关类
 *
 * PHP Version 5
 * 标签相关类
 *
 * @category  I500M
 * @package   Member
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      2015-08-25
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\models\i500_social;
use frontend\models\i500_social\Label;

/**
 * 标签类别表
 *
 * @category MODEL
 * @package  Social
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class TagClass extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_tag_class}}';
    }

    public function getLabel()
    {
    	return $this->hasMany(Label::className(),['classify_id'=>'id']);
    }
}