<?php
/**
 * I500_social_rewrite数据库model基类
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      2017-04-13
 * @copyright 辽宁i500科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */

namespace frontend\models\i500_social_rewrite;

use frontend\models\Base;

/**
 * I500_social_rewrite数据库model基类
 *
 * @category MODEL
 * @package  Social
 * @author   liuyanwei <liuyanwei@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     liuyanwei@i500m.com
 */
class SocialRewriteBase extends Base
{
    /**
     * 设置默认数据库连接
     * @return \yii\db\Connection
     */
    public static function getDB()
    {
        return \Yii::$app->db_social_rewrite;
    }
}
