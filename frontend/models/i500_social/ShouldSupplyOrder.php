<?php
/**
 * ShouldSupplyOrder.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/6/21 15:19
 */
namespace frontend\models\i500_social;

class ShouldSupplyOrder extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_should_supply_order}}';
    }
    /**
     * 服务订单列表数量
     * @param $where
     * @param $orWhere
     * @param array $andwhere
     * @return array|null|\yii\db\ActiveRecord|\yii\db\ActiveRecord[]
     */
    public function SupplyorderCount($where = [],$orWhere = [],$andwhere=[]){
        $data = [];
        if($where){
            $data = $this->find()->select(['id'])
                        ->where($where)
                        ->orWhere($orWhere)
                        ->andWhere($andwhere)
                        ->count();
        }
        return $data;
    }
}