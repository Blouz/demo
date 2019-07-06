<?php
/**
 * I500_social数据库model基类
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015-08-05
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */

namespace frontend\models\i500_social;

use frontend\models\Base;

/**
 * I500_social数据库model基类
 *
 * @category MODEL
 * @package  Social
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class SocialBase extends Base
{
    /**
     * 设置默认数据库连接
     * @return \yii\db\Connection
     */
    public static function getDB()
    {
        return \Yii::$app->db_social;
    }
}
