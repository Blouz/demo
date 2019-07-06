<?php


namespace frontend\models\i500_social;

use frontend\models\i500_social\IntegralRules;
class Integral extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_integral}}';
    }

    public function getIntegralrules()
    {
        return $this->hasOne(IntegralRules::className(), ['id' => 'rule_id']);
    }
}
