<?php
/**
 * I500_apilog数据库model基类
*
* PHP Version 5
* @category  MODEL
* @package   Social
* @author    wyy <wyy@wyy.com>
* @time      2017/05/12
* @copyright 2017 辽宁爱伍佰科技发展有限公司
* @license   http://www.i500m.com license
* @link      wyy@wyy.com
*/

namespace frontend\models\i500_log;

use frontend\models\Base;

class LogBase extends Base
{
    /**
     * 设置默认数据库连接
     * @return \yii\db\sqlite
     */
    public static function getDB()
    {
        return \Yii::$app->db_i500log;
    }
}
