<?php
/**
 * 社区头条表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    yaoxin <yaoxin@i500.com>
 * @time      2017-05-13
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      yaoxin@i500.com
 */

namespace frontend\models\i500_social;
use yii\behaviors\TimestampBehavior;

/**
 * 社区头条表
 *
 * @category MODEL
 * @package  Social
 * @author   yaoxin <yaoxin@i500.com>
 * @license  http://www.i500m.com/ license
 * @link     yaoxin@i500.com
 */
class Headlines extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_headlines}}';
    }
    
    /**
     * 获取信息 一条
     * @param array  $cond      条件
     * @param bool   $asArray   是否作为数组返回
     * @param string $field     字段
     * @param string $and_where 字段
     * @param string $order     排序
     * @return array|null|ActiveRecord
     */
    public function getInfo($cond = array(), $asArray = true, $field = '*', $and_where = '', $order = '')
    {
        $info = [];
        if ($cond) {
            if ($asArray) {
                $info = $this->find()->select($field)->where($cond)->andWhere($and_where)->orderBy($order)->asArray()->one();
            } else {
                $info = $this->find()->select($field)->where($cond)->andWhere($and_where)->orderBy($order)->one();
            }

        }
        return $info;

    }

    /**
     * 更新信息
     * @param array $data 数据
     * @param array $cond 条件
     * @return bool
     */
    public function updateInfo($data = array(), $cond = array())
    {
        $re = false;
        if ($cond && $data) {
            $re = $this->updateAll($data, $cond);
        }
        return $re !== false;
    }

    public function getHeadcomments()
    {
        return $this->hasMany(HeadlinesComments::className(), ['head_id' => 'id']);
    }
    public function getHeadphoto()
    {
        return $this->hasMany(HeadlinesPhoto::className(), ['head_id' => 'id']);
    }
    public function getHeadspecial()
    {
        return $this->hasOne(HeadSpecial::className(), ['id' => 'special_id']);
    }
}
