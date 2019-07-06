<?php
/**
 * 小区幸福指数统计
 * PHP Version 5
 * @category  PHP
 * @package   Admin
 * @filename  Comments.php
 * @author    wyy <wyy@iyangpin.com>
 * @copyright 2017 www.i500m.com
 * @license   http://www.i500m.com/ i500m license
 * @datetime  2017/5/27
 * @version   SVN: 1.0
 * @link      http://www.i500m.com/
 */
namespace frontend\models\i500_social;
class Statistical extends SocialBase {
    /**
     * 简介：表名
     * @author  wyy@iyangpin.com。
     * @return string
     */
    public static function tableName() {
        return "{{%i500_statistical}}";
    }
}
