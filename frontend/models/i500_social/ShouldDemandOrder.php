<?php
/**
 * 需求订单表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    yaoxin <yaoxin@i500m.com>
 * @time      2017/06/21
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      yaoxin@i500m.com
 */
namespace frontend\models\i500_social;

class ShouldDemandOrder extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_should_demand_order}}';
    }
    /**
     * 获取求助者用户信息
     */
    public function getCallers()
    {
        return $this->hasOne(UserBasicInfo::className(), ['mobile' => 'dmobile']);
    }
    /**
     * 获取帮助者用户信息
     */
    public function getHelpers()
    {
        return $this->hasOne(UserBasicInfo::className(), ['mobile' => 'mobile']);
    }
    /**
     * 需求订单列表 or 详情
     * @param $where
     * @param array $orwhere
     * @param array $andwhere2
     * @param int $page
     * @param int $size
     * @param string $fileds
     * @param int $limit
     * @param int $type
     * @return array|null|\yii\db\ActiveRecord|\yii\db\ActiveRecord[]
     */
    public function DorderList($where,$orwhere=[],$andwhere=[],$page=1,$size=10,$fileds = '*', $type = 1){
        $data = [];
        if($where){
            $model = $this->find()->select($fileds)
                    ->where($where)
                    ->orWhere($orwhere)
                    ->andWhere($andwhere);

            switch ($type)
            {
                case 1:
                    $data = $model->orderBy('create_time Desc')->offset(($page-1)*$size)->limit($size)->asArray()->all();
                    break;
                case 2:
                    $data = $model->asArray()->one();
                    break;
            }
        }
        return $data;
    }
    /**
     * 需求订单列表 or 详情
     * @param $where
     * @param array $orwhere
     * @param array $andwhere2
     * @return array|null|\yii\db\ActiveRecord|\yii\db\ActiveRecord[]
     */
    public function DorderCount($where,$orwhere=[],$andwhere=[]){
        $data = [];
        if($where){
            $data = $this->find()->select(['*'])
                    ->where($where)
                    ->orWhere($orwhere)
                    ->andWhere($andwhere)
                    ->count();
        }
        return $data;
    }
}