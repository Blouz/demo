<?php
/**
 * 配送信息
 *
 * PHP Version 5
 * 可写多行的文件相关说明
 *
 * @category  I500M
 * @package   Member
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      2015-08-28
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\models\i500_social;

use frontend\models\i500m\Shop;

/**
 * 配送表
 *
 * @category MODEL
 * @package  Social
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class Dispatch extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_dispatch}}';
    }

    /**
     * 根据商家id 获取商家配送信息
     * @param int $shop_id 商家id
     * @return array
     */
    public function getDispatchTime($shop_id)
    {
        $model = new Shop();
        $hours = $model->getField('hours', ['id'=>$shop_id]);
        $end_time = 21;
        if (!empty($hours)) {
            $time = explode('~', $hours);
            $end_time = isset($time[1])?$time[1]:21;
        }
        //$end_time = 18;
        $over_time = 21;
        if ($end_time <= 21 && $end_time >= 18) {
            $over_time = $end_time;
        }
        $now = time();
        $hours = date("H", $now);
        $today[] ='立即配送';
        $start = 15;
        if ($hours < 9) {
            $today[] = '10-12';
        }
        if ($hours >= 15) {
            $start = $hours + 1;
        }
        for ($i=$start; $i<18; $i++) {
            $today[] = $i.'-'.($i+1);
        }
        if ($over_time != 18) {
            $today[] = '18-'.$over_time;
        }
        $tomorry[] = '10-12';
        for ($i=15; $i<18; $i++) {
            $tomorry[] = $i.'-'.($i+1);
        }
        $tomorry[] = '18-21';
        $dispatch_time = [$today, $tomorry];
        return $dispatch_time;
    }
}
