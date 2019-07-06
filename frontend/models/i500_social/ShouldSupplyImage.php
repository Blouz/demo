<?php
/**
 * ShouldSupplyImage.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/6/21 15:17
 */
namespace frontend\models\i500_social;

class ShouldSupplyImage extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_should_supply_image}}';
    }
}