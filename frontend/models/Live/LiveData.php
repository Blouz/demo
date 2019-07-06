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

use Yii;
use yii\base\Model;
use common\helpers\CurlHelper;
use yii\db\ActiveRecord;
use linslin\yii2\curl;

/**
 * Class City
 * @category  PHP
 * @package   Admin
 * @author    zhoujun <lichenjun@iyangpin.com>
 * @copyright 2015 www
 * @license   http://www.i500m.com/ i500m license
 * @link      http://www.i500m.com/
 */
class LiveData extends LiveBase
{
    public static function tableName()
    {
        return '{{%live_data}}';
    }
}