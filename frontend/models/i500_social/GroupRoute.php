<?php
/**
 * 地铁路线表
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

class GroupRoute extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_group_route}}';
    }
    

    /**
     * 获取线路对应站
     * @return \yii\db\ActiveQuery
     */
    public function getStation()
    {
        return $this->hasMany(GroupStation::className(), ['route_id'=>'id']);
    }
}
