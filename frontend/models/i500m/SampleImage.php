<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-6 下午12:54
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\models\i500m;

use common\helpers\Common;
use common\helpers\CurlHelper;

class SampleImage extends I500Base
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%sample_image}}';
    }
}
