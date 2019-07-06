<?php
/**
 * 地铁聊天室表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    xuxiaoyu <xuxiaoyu@iyangpin.com>
 * @time      2016-11-04
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      xuxiaoyu@iyangpin.com
 */

namespace frontend\models\i500_social;

class GroupStationRoom extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_group_station_room}}';
    }
    
}
