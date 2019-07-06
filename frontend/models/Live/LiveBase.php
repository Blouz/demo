<?php

/* 
 * 
 * @category  Social
 * @package   Post
 * @author    wangleilei <wangleilei@i500m.com>
 * @time      2017
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wangleilei@i500m.com
 */



namespace frontend\models\Live;

use frontend\models\Base;

/**
 * I500m数据库model基类
 *
 * @category MODEL
 * @package  Social
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class LiveBase extends \yii\db\ActiveRecord
{
    /**
     * 设置默认数据库连接
     * @return \yii\db\Connection
     */
    public static function getDB()
    {
        return \Yii::$app->db_live;
    }
}
